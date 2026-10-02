<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\DocumentCommercial;
use App\Models\Event;
use App\Solen\Documents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Les documents remis à un couple : kit de bienvenue, planche de QR,
 * documents légaux, et ses factures. Par e-mail (pièces jointes) ou par
 * WhatsApp (liens de téléchargement signés).
 */
class DocumentsController extends Controller
{
    public function __construct(private readonly Documents $documents)
    {
    }

    private function mariage(string $slug): Event
    {
        return Event::where('slug', $slug)->firstOrFail();
    }

    public function voir(string $slug, string $piece): Response
    {
        abort_unless(array_key_exists($piece, Documents::KIT), 404);
        $event = $this->mariage($slug);

        return $this->pdf($this->documents->pdfKit($event, $piece), $this->nom($event, $piece), true);
    }

    public function envoyer(Request $request, string $slug): RedirectResponse
    {
        $event = $this->mariage($slug);
        $donnees = $this->valider($request, $event);

        $pieces = [];
        foreach ($donnees['pieces'] ?? [] as $piece) {
            $pieces[$this->nom($event, $piece)] = $this->documents->pdfKit($event, $piece);
        }
        foreach ($this->factures($event, $donnees['factures'] ?? []) as $doc) {
            $pieces[$this->documents->nomFichier($doc)] = $this->documents->pdfCommercial($doc);
        }

        try {
            $this->documents->envoyer($donnees['email'], $donnees['sujet'], $donnees['message'], $pieces, $event);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('erreur', 'L’e-mail n’a pas pu partir : ' . $e->getMessage());
        }

        $this->factures($event, $donnees['factures'] ?? [])->each->update(['envoye_email_le' => now()]);

        return back()->with('ok', count($pieces) . " document(s) envoyé(s) à {$donnees['email']}.");
    }

    public function whatsapp(Request $request, string $slug): RedirectResponse
    {
        $event = $this->mariage($slug);
        $donnees = $this->valider($request, $event, whatsapp: true);

        $liens = collect($donnees['pieces'] ?? [])
            ->map(fn ($p) => '• ' . Documents::KIT[$p] . ' : ' . $this->documents->lienKit($event, $p));

        $factures = $this->factures($event, $donnees['factures'] ?? []);
        $liens = $liens->concat($factures->map(fn ($d) => '• ' . $d->libelle() . ' : ' . $this->documents->lienCommercial($d)));
        $factures->each->update(['envoye_whatsapp_le' => now()]);

        $texte = trim($donnees['message']) . "\n\n" . $liens->implode("\n")
            . "\n\nLes liens restent valables " . Documents::VALIDITE_JOURS . " jours.\n" . config('solen.entreprise.nom_commercial');

        return redirect()->away($this->documents->whatsapp($donnees['telephone'], $texte));
    }

    // --- Liens publics signés --------------------------------------------

    public function commercialPublic(string $uuid): Response
    {
        $doc = DocumentCommercial::where('uuid', $uuid)->whereNotNull('numero')->firstOrFail();

        return $this->pdf($this->documents->pdfCommercial($doc), $this->documents->nomFichier($doc), true);
    }

    public function kitPublic(string $slug, string $piece): Response
    {
        abort_unless(array_key_exists($piece, Documents::KIT), 404);
        $event = $this->mariage($slug);

        return $this->pdf($this->documents->pdfKit($event, $piece), $this->nom($event, $piece), true);
    }

    // --- Outils -------------------------------------------------------------

    private function valider(Request $request, Event $event, bool $whatsapp = false): array
    {
        $donnees = $request->validate([
            'pieces'    => ['nullable', 'array'],
            'pieces.*'  => [Rule::in(array_keys(Documents::KIT))],
            'factures'  => ['nullable', 'array'],
            'factures.*'=> ['integer'],
            'email'     => [$whatsapp ? 'nullable' : 'required', 'email'],
            'telephone' => [$whatsapp ? 'required' : 'nullable', 'string', 'max:40'],
            'sujet'     => ['nullable', 'string', 'max:160'],
            'message'   => ['required', 'string', 'max:3000'],
        ], ['telephone.required' => 'Indiquez le numéro WhatsApp du couple.']);

        $donnees['sujet'] = ($donnees['sujet'] ?? null) ?: 'Vos documents — ' . $event->nom;

        return $donnees;
    }

    /** Seules les factures émises de CE mariage peuvent être jointes. */
    private function factures(Event $event, array $ids)
    {
        return DocumentCommercial::where('event_id', $event->id)->whereNotNull('numero')->whereIn('id', $ids)->get();
    }

    private function nom(Event $event, string $piece): string
    {
        return Str::slug(Documents::KIT[$piece] . ' ' . $event->nom) . '.pdf';
    }

    private function pdf(string $contenu, string $nom, bool $inline): Response
    {
        return response($contenu, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $nom . '"',
        ]);
    }
}
