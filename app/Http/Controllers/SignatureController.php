<?php

namespace App\Http\Controllers;

use App\Mail\CodeSignature;
use App\Models\DocumentCommercial;
use App\Solen\Documents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Signature électronique d'un devis, depuis le lien transmis au client.
 *
 * Le lien seul ne suffit pas : un code à six chiffres est envoyé par
 * e-mail, et c'est sa saisie qui rattache la signature à la maîtrise d'une
 * boîte mail. Quand le devis porte l'adresse du client, le code part là et
 * nulle part ailleurs — sinon n'importe qui ayant le lien signerait sous
 * son propre nom.
 *
 * La preuve est figée avec le document : signataire, adresse vérifiée,
 * horodatage, IP, navigateur, signature manuscrite et empreinte du contenu.
 */
class SignatureController extends Controller
{
    public const CODE_MINUTES = 15;

    public const CODE_ESSAIS = 5;

    public function __construct(private readonly Documents $documents)
    {
    }

    private function devis(string $token): DocumentCommercial
    {
        return DocumentCommercial::where('signature_token', $token)
            ->where('type', DocumentCommercial::DEVIS)
            ->whereNotNull('numero')
            ->firstOrFail();
    }

    public function page(string $token): View
    {
        $devis = $this->devis($token);

        if ($devis->estSigne()) {
            return view('signature.signe', ['devis' => $devis]);
        }

        $code = $devis->signature_code;

        return view('signature.devis', [
            'devis'       => $devis,
            'emailImpose' => $devis->client_email,
            'codeEnvoye'  => $code && Carbon::parse($code['expire_le'])->isFuture(),
            'envoyeA'     => $code ? self::masquer($code['envoye_a']) : null,
            'signable'    => $devis->signable(),
        ]);
    }

    public function envoyerCode(Request $request, string $token): RedirectResponse
    {
        $devis = $this->devis($token);

        abort_unless($devis->signable(), 410);

        $request->validate(
            ['email' => $devis->client_email ? ['nullable'] : ['required', 'email', 'max:255']],
            ['email.required' => 'Indiquez votre adresse e-mail pour recevoir le code.', 'email.email' => 'Cette adresse e-mail n’est pas valide.']
        );

        $email = $devis->client_email ?: $request->input('email');

        // Un code par minute au plus : on n'inonde pas une boîte mail.
        $dernier = $devis->signature_code['envoye_le'] ?? null;
        if ($dernier && Carbon::parse($dernier)->gt(now()->subMinute())) {
            return back()->withInput()->with('erreur', 'Un code vient de partir. Patientez une minute avant d’en redemander un.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $devis->forceFill(['signature_code' => [
            'hash'      => Hash::make($code),
            'envoye_a'  => $email,
            'envoye_le' => now()->toIso8601String(),
            'expire_le' => now()->addMinutes(self::CODE_MINUTES)->toIso8601String(),
            'essais'    => 0,
        ]])->saveQuietly();

        try {
            Mail::to($email)->send(new CodeSignature($devis, $code, self::CODE_MINUTES));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('erreur', 'Le code n’a pas pu être envoyé. Écrivez-nous à ' . config('solen.entreprise.email') . '.');
        }

        return back()->withInput()->with('ok', 'Code envoyé à ' . self::masquer($email) . ', valable ' . self::CODE_MINUTES . ' minutes.');
    }

    public function signer(Request $request, string $token): View|RedirectResponse
    {
        $devis = $this->devis($token);

        if ($devis->estSigne()) {
            return view('signature.signe', ['devis' => $devis]);
        }

        abort_unless($devis->signable(), 410);

        $request->validate([
            'nom'       => ['required', 'string', 'max:120'],
            'code'      => ['required', 'digits:6'],
            'trace'     => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
            'accord'    => ['accepted'],
        ], [
            'nom.required'   => 'Indiquez votre nom complet.',
            'code.required'  => 'Saisissez le code reçu par e-mail.',
            'code.digits'    => 'Le code comporte 6 chiffres.',
            'trace.required' => 'Signez dans le cadre prévu.',
            'accord.accepted' => 'Cochez « Bon pour accord » pour signer.',
        ]);

        if ($erreur = $this->verifierCode($devis, $request->input('code'))) {
            return back()->withInput()->withErrors(['code' => $erreur]);
        }

        $code = $devis->signature_code;

        $devis->forceFill([
            'signe_le'       => now(),
            'statut'         => 'accepte',
            'signature_code' => null,
            'signature'      => [
                'nom'          => $request->input('nom'),
                'email'        => $code['envoye_a'],
                'methode'      => 'code à usage unique envoyé par e-mail',
                'code_envoye'  => $code['envoye_le'],
                'ip'           => $request->ip(),
                'navigateur'   => substr((string) $request->userAgent(), 0, 255),
                'trace'        => $request->input('trace'),
                'contenu'      => $devis->contenuSigne(),
            ],
        ])->saveQuietly();

        return redirect()->route('signature.devis', $token);
    }

    /** Le devis signé, en PDF : la preuve que le client garde. */
    public function pdf(string $token): Response
    {
        $devis = $this->devis($token);

        abort_unless($devis->estSigne(), 404);

        return response($this->documents->pdfCommercial($devis), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . str_replace('.pdf', '-signe.pdf', $this->documents->nomFichier($devis)) . '"',
        ]);
    }

    private function verifierCode(DocumentCommercial $devis, ?string $saisi): ?string
    {
        $code = $devis->signature_code;

        if (! $code) {
            return 'Demandez d’abord votre code, avec le bouton « Recevoir mon code ».';
        }

        if (Carbon::parse($code['expire_le'])->isPast()) {
            return 'Ce code a expiré. Demandez-en un nouveau.';
        }

        if (($code['essais'] ?? 0) >= self::CODE_ESSAIS) {
            return 'Trop de tentatives. Demandez un nouveau code.';
        }

        if (! Hash::check((string) $saisi, $code['hash'])) {
            $code['essais'] = ($code['essais'] ?? 0) + 1;
            $devis->forceFill(['signature_code' => $code])->saveQuietly();
            $reste = self::CODE_ESSAIS - $code['essais'];

            return $reste > 0
                ? "Code incorrect. Il vous reste {$reste} essai" . ($reste > 1 ? 's' : '') . '.'
                : 'Code incorrect. Demandez un nouveau code.';
        }

        return null;
    }

    /** j***n@exemple.fr */
    public static function masquer(string $email): string
    {
        [$local, $domaine] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1) . str_repeat('•', max(1, mb_strlen($local) - 2))
            . (mb_strlen($local) > 2 ? mb_substr($local, -1) : '') . '@' . $domaine;
    }
}
