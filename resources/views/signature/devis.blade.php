@extends('solen.layout')

@section('title', 'Signer le ' . mb_strtolower($devis->libelle()))

@section('content')
@php
    use App\Models\DocumentCommercial as D;
    $etape = old('nom') || $codeEnvoye || session('ok') ? 'signer' : 'code';
@endphp

{{--
    Le client voit ce qu'il signe avant de signer : les lignes, la remise,
    le total, les conditions. Puis deux temps : recevoir le code par e-mail,
    signer avec ce code.
--}}
<section class="signature">
    <div class="wrap signature-wrap">
        <p class="eyebrow">{{ config('solen.entreprise.nom_commercial') }}</p>
        <h1>{{ $devis->libelle() }}</h1>
        @if ($devis->objet)<p class="signature-objet">{{ $devis->objet }}</p>@endif

        <div class="signature-carte">
            <table class="signature-lignes">
                @foreach (collect($devis->lignes)->reject(fn ($l) => ! empty($l['deduction'])) as $l)
                    @php $m = (int) round(((float) ($l['quantite'] ?? 1)) * (int) $l['prix_unitaire_centimes']); @endphp
                    <tr>
                        <td>
                            <strong>{{ $l['designation'] }}</strong>
                            @if (! empty($l['detail']))<small>{{ $l['detail'] }}</small>@endif
                        </td>
                        <td class="num">{{ $m === 0 ? 'Offert' : D::euros($m) }}</td>
                    </tr>
                @endforeach
                @if ($devis->remise_centimes)
                    <tr class="signature-remise"><td>{{ $devis->libelleRemise() }}</td><td class="num">− {{ D::euros($devis->remise_centimes) }}</td></tr>
                @endif
                <tr class="signature-total"><td>Total à payer</td><td class="num">{{ D::euros($devis->total_centimes) }}</td></tr>
            </table>
            @if ($devis->notes)<p class="signature-notes">{!! nl2br(e($devis->notes)) !!}</p>@endif
            <p class="signature-notes">
                Valable jusqu’au {{ $devis->valide_jusqu_au?->translatedFormat('j F Y') ?? '—' }} ·
                <a href="{{ route('legal.cgv') }}" target="_blank" rel="noopener">Conditions générales de vente</a>
            </p>
        </div>

        @if (! $signable)
            <div class="signature-carte signature-fermee">
                Ce devis ne peut plus être signé en ligne (validité dépassée ou devis clos).
                Écrivez-nous à <a href="mailto:{{ config('solen.entreprise.email') }}">{{ config('solen.entreprise.email') }}</a>.
            </div>
        @else
            @if (session('erreur'))<p class="signature-alerte">{{ session('erreur') }}</p>@endif
            @if (session('ok'))<p class="signature-info">{{ session('ok') }}</p>@endif

            {{-- 1. Le code --}}
            <form method="POST" action="{{ route('signature.code', $devis->signature_token) }}" class="signature-carte">
                @csrf
                <h2><span>1</span> Recevoir mon code</h2>
                @if ($emailImpose)
                    <p>Un code à 6 chiffres sera envoyé à <strong>{{ \App\Http\Controllers\SignatureController::masquer($emailImpose) }}</strong>, l’adresse indiquée sur le devis.</p>
                @else
                    <label class="signature-champ">
                        <span>Votre adresse e-mail</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email">
                    </label>
                    @error('email')<p class="signature-erreur">{{ $message }}</p>@enderror
                @endif
                <button class="btn {{ $etape === 'code' ? 'btn-primary' : 'btn-ghost' }}">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $codeEnvoye ? 'Renvoyer un code' : 'Recevoir mon code' }}
                </button>
                @if ($codeEnvoye && $envoyeA)<p class="signature-aide">Dernier code envoyé à {{ $envoyeA }}.</p>@endif
            </form>

            {{-- 2. La signature --}}
            <form method="POST" action="{{ route('signature.signer', $devis->signature_token) }}" class="signature-carte" id="form-signature">
                @csrf
                <h2><span>2</span> Signer</h2>

                <label class="signature-champ">
                    <span>Nom et prénom du signataire</span>
                    <input type="text" name="nom" value="{{ old('nom', $devis->client_nom) }}" required autocomplete="name">
                </label>
                @error('nom')<p class="signature-erreur">{{ $message }}</p>@enderror

                <label class="signature-champ">
                    <span>Code reçu par e-mail</span>
                    <input type="text" name="code" value="{{ old('code') }}" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" class="signature-code">
                </label>
                @error('code')<p class="signature-erreur">{{ $message }}</p>@enderror

                <div class="signature-champ">
                    <span>Votre signature <button type="button" class="signature-effacer" id="effacer">Effacer</button></span>
                    <canvas id="trace" class="signature-trace" aria-label="Zone de signature : signez avec le doigt ou la souris"></canvas>
                    <input type="hidden" name="trace" id="trace-donnees">
                </div>
                @error('trace')<p class="signature-erreur">{{ $message }}</p>@enderror

                <label class="signature-accord">
                    <input type="checkbox" name="accord" value="1" @checked(old('accord')) required>
                    <span>Bon pour accord : j’ai lu et j’approuve ce devis et les conditions générales de vente.</span>
                </label>
                @error('accord')<p class="signature-erreur">{{ $message }}</p>@enderror

                <button class="btn btn-primary signature-valider"><i class="fa-solid fa-signature" aria-hidden="true"></i> Signer le devis</button>
            </form>
        @endif
    </div>
</section>

<style>
    .signature { padding: 7rem 0 4rem; }
    .signature-wrap { max-width: 640px; }
    .signature h1 { margin: .3rem 0 .2rem; font-size: clamp(1.8rem, 6vw, 2.4rem); }
    .signature-objet { margin: 0 0 1.4rem; color: var(--ink-mute); }
    .signature-carte { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1.3rem 1.3rem 1.4rem; margin-bottom: 1rem; }
    .signature-carte h2 { display: flex; align-items: center; gap: .6rem; margin: 0 0 .8rem; font-size: 1.15rem; }
    .signature-carte h2 span { display: inline-grid; place-items: center; width: 1.8rem; height: 1.8rem; border-radius: 50%; background: var(--ink); color: #fff; font-size: .85rem; }
    .signature-lignes { width: 100%; border-collapse: collapse; }
    .signature-lignes td { padding: .55rem 0; border-bottom: 1px solid var(--line); vertical-align: top; }
    .signature-lignes small { display: block; color: var(--ink-mute); font-size: .8rem; margin-top: .15rem; }
    .signature-lignes .num { text-align: right; white-space: nowrap; padding-left: 1rem; }
    .signature-remise td { color: #A87C46; }
    .signature-total td { border-bottom: 0; padding-top: .8rem; font-weight: 700; font-size: 1.1rem; }
    .signature-notes { margin: .8rem 0 0; font-size: .85rem; color: var(--ink-mute); }
    .signature-champ { display: grid; gap: .35rem; margin: 0 0 .9rem; }
    .signature-champ > span { display: flex; justify-content: space-between; align-items: center; font-weight: 600; font-size: .9rem; }
    .signature-champ input { font: inherit; font-size: 16px; padding: .7rem .85rem; border: 1px solid var(--line); border-radius: 10px; }
    .signature-code { letter-spacing: .4em; font-size: 1.3rem !important; text-align: center; }
    .signature-trace { width: 100%; height: 170px; border: 2px dashed #C9C2B4; border-radius: 12px; background: #FCFAF7; touch-action: none; cursor: crosshair; }
    .signature-effacer { border: 0; background: none; color: var(--ink-mute); font-weight: 500; font-size: .85rem; text-decoration: underline; cursor: pointer; }
    .signature-accord { display: flex; gap: .6rem; align-items: flex-start; margin: .2rem 0 1rem; font-size: .92rem; }
    .signature-accord input { width: 1.2rem; height: 1.2rem; margin-top: .15rem; flex: none; }
    .signature-valider { width: 100%; justify-content: center; }
    .signature-erreur { margin: -.5rem 0 .8rem; color: #B3261E; font-size: .85rem; }
    .signature-alerte, .signature-info { padding: .8rem 1rem; border-radius: 12px; margin: 0 0 1rem; font-size: .92rem; }
    .signature-alerte { background: #FBEAE8; color: #8C1D16; }
    .signature-info { background: #E7F3EC; color: #1E5E3E; }
    .signature-aide { margin: .6rem 0 0; font-size: .82rem; color: var(--ink-mute); }
    .signature-fermee { color: var(--ink-soft); }
</style>

@if ($signable)
<script>
(() => {
    const canvas = document.getElementById('trace');
    const champ  = document.getElementById('trace-donnees');
    const ctx    = canvas.getContext('2d');
    let dessine = false, encre = false;

    // Le canvas dessine à sa taille affichée, pour un trait net et à la
    // bonne place sous le doigt.
    const ajuster = () => {
        const r = canvas.getBoundingClientRect(), dpr = window.devicePixelRatio || 1;
        const avant = encre ? canvas.toDataURL() : null;
        canvas.width = Math.round(r.width * dpr); canvas.height = Math.round(r.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#1B1B2F';
        if (avant) { const i = new Image(); i.onload = () => ctx.drawImage(i, 0, 0, r.width, r.height); i.src = avant; }
    };
    ajuster();
    addEventListener('resize', ajuster);

    const point = (e) => { const r = canvas.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };

    canvas.addEventListener('pointerdown', (e) => { dessine = true; canvas.setPointerCapture(e.pointerId); ctx.beginPath(); ctx.moveTo(...point(e)); });
    canvas.addEventListener('pointermove', (e) => { if (!dessine) return; ctx.lineTo(...point(e)); ctx.stroke(); encre = true; });
    ['pointerup', 'pointercancel'].forEach((t) => canvas.addEventListener(t, () => dessine = false));

    document.getElementById('effacer').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height); encre = false;
    });

    // Un cadre vide ne vaut pas signature : le champ reste vide, et le
    // serveur le refuse.
    document.getElementById('form-signature').addEventListener('submit', () => {
        champ.value = encre ? canvas.toDataURL('image/png') : '';
    });
})();
</script>
@endif
@endsection
