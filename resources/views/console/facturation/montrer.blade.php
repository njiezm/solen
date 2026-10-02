@extends('console.layout')

@php use App\Models\DocumentCommercial as D; @endphp

@section('titre', $doc->libelle())
@section('chapeau', $doc->client_nom . ' · ' . D::euros($doc->total_centimes) . ($doc->remise_centimes ? ' (' . $doc->libelleRemise() . ')' : '') . ' · ' . (D::STATUTS[$doc->statut] ?? $doc->statut))

@section('contenu')

@if (! $doc->estEmis() && $doc->type !== D::DEVIS)
    @include('console.facturation._mentions')
@endif

<div class="grille-doc">
    <div>
        {{-- L'aperçu est le vrai PDF : ce qui s'affiche ici est ce que reçoit le client. --}}
        <div class="carte" style="padding:0; overflow:hidden">
            <iframe src="{{ route('console.facturation.pdf', [$doc, 'voir' => 1]) }}#toolbar=0&view=FitH" title="Aperçu du document"
                    style="width:100%; height:880px; border:0; display:block; background:#E9E7E3"></iframe>
        </div>
    </div>

    <div>
        {{-- ────────────────────────────────────────────── Actions ── --}}
        <div class="carte">
            <h2>Actions</h2>
            <div style="display:grid; gap:.5rem">
                @if (! $doc->estEmis() || $doc->type === D::DEVIS && in_array($doc->statut, ['brouillon', 'emis']))
                    <a href="{{ route('console.facturation.editer', $doc) }}" class="btn">Modifier</a>
                @endif

                @unless ($doc->estEmis())
                    <form method="POST" action="{{ route('console.facturation.emettre', $doc) }}"
                          onsubmit="return confirm('Émettre ce document ? Il recevra son numéro définitif{{ $doc->type === D::DEVIS ? '' : ' et ne pourra plus être modifié' }}.')">
                        @csrf
                        <button class="btn btn-primary" style="width:100%">Émettre {{ $doc->type === D::DEVIS ? 'le devis' : 'la facture' }}</button>
                    </form>
                    <form method="POST" action="{{ route('console.facturation.supprimer', $doc) }}" onsubmit="return confirm('Supprimer ce brouillon ?')">
                        @csrf @method('DELETE')
                        <button class="mini mini--danger" style="width:100%">Supprimer le brouillon</button>
                    </form>
                @endunless

                <a href="{{ route('console.facturation.pdf', $doc) }}" class="btn"><i class="fa-solid fa-download" aria-hidden="true"></i> Télécharger le PDF</a>

                @if ($doc->type === D::DEVIS && $doc->estEmis() && ! in_array($doc->statut, ['converti', 'refuse']))
                    @php
                        $acomptes = $doc->acomptes();
                        $verse = (int) $acomptes->sum('total_centimes');
                        $reste = $doc->total_centimes - $verse;
                    @endphp

                    {{-- Acomptes : un clic, aucun calcul à faire. --}}
                    <div class="carte" style="margin:0; padding:.9rem; background:var(--surface-warm)">
                        <strong style="display:block; margin-bottom:.3rem">Acomptes</strong>
                        <p class="carte-aide" style="margin:0 0 .6rem">
                            @if ($acomptes->isEmpty()) Aucun acompte facturé.
                            @else {{ $acomptes->count() }} acompte{{ $acomptes->count() > 1 ? 's' : '' }} : {{ D::euros($verse) }} · reste {{ D::euros($reste) }}.
                            @endif
                        </p>
                        @if ($reste > 0)
                            <div style="display:flex; gap:.4rem; flex-wrap:wrap">
                                @foreach ([30, 40, 50] as $pct)
                                    @if ((int) round($doc->total_centimes * $pct / 100) <= $reste)
                                        <form method="POST" action="{{ route('console.facturation.acompte', $doc) }}">
                                            @csrf <input type="hidden" name="mode" value="pourcentage"><input type="hidden" name="valeur" value="{{ $pct }}">
                                            <button class="mini">{{ $pct }} % · {{ D::euros((int) round($doc->total_centimes * $pct / 100)) }}</button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('console.facturation.acompte', $doc) }}" style="display:flex; gap:.4rem; margin-top:.5rem">
                                @csrf <input type="hidden" name="mode" value="montant">
                                <select name="valeur" style="flex:1; min-height:36px">
                                    @foreach ([50, 100, 150, 200, 250, 300, 500] as $m)
                                        @if ($m * 100 <= $reste)<option value="{{ $m }}">Acompte de {{ $m }} €</option>@endif
                                    @endforeach
                                    <option value="{{ $reste / 100 }}">Tout le reste · {{ D::euros($reste) }}</option>
                                </select>
                                <button class="mini">Facturer</button>
                            </form>
                        @endif
                    </div>

                    @if ($acomptes->isNotEmpty())
                        <form method="POST" action="{{ route('console.facturation.solde', $doc) }}">
                            @csrf <button class="btn btn-primary" style="width:100%">Facture de solde · {{ D::euros($reste) }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('console.facturation.convertir', $doc) }}">
                            @csrf <button class="btn btn-primary" style="width:100%">Devis accepté : facturer en totalité</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('console.facturation.devis', $doc) }}">
                        @csrf <input type="hidden" name="statut" value="refuse">
                        <button class="mini" style="width:100%">Marquer refusé</button>
                    </form>
                @endif

                @if ($doc->type === D::FACTURE && $doc->estEmis() && $doc->statut === 'emis')
                    <form method="POST" action="{{ route('console.facturation.payer', $doc) }}" class="carte" style="margin:0; padding:.9rem; background:var(--surface-warm)">
                        @csrf
                        <strong style="display:block; margin-bottom:.5rem">Enregistrer le paiement</strong>
                        <div style="display:flex; gap:.4rem; flex-wrap:wrap">
                            <select name="mode_paiement" style="flex:1; min-height:38px">
                                @foreach (D::MODES_PAIEMENT as $cle => $libelle)<option value="{{ $cle }}">{{ $libelle }}</option>@endforeach
                            </select>
                            <input type="date" name="paye_le" value="{{ now()->format('Y-m-d') }}" style="min-height:38px">
                            <button class="mini choisi">Payée</button>
                        </div>
                    </form>
                @endif

                @if ($doc->type === D::FACTURE && $doc->estEmis() && $doc->statut !== 'annule')
                    <details>
                        <summary class="mini mini--danger" style="cursor:pointer; list-style:none; text-align:center">Annuler par un avoir…</summary>
                        <form method="POST" action="{{ route('console.facturation.avoir', $doc) }}" style="margin-top:.5rem; display:grid; gap:.4rem"
                              onsubmit="return confirm('Créer un avoir du montant total de cette facture ?')">
                            @csrf
                            <input type="text" name="motif" placeholder="Motif (imprimé sur l’avoir)">
                            <button class="mini mini--danger">Créer l’avoir</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        {{-- ────────────────────────────────────────────── Envoi ── --}}
        @if ($doc->estEmis())
            <div class="carte">
                <h2>Transmettre</h2>
                <p class="carte-aide">
                    @if ($doc->envoye_email_le) Envoyé par e-mail le {{ $doc->envoye_email_le->translatedFormat('j M Y à H\hi') }}.<br>@endif
                    @if ($doc->envoye_whatsapp_le) Partagé sur WhatsApp le {{ $doc->envoye_whatsapp_le->translatedFormat('j M Y à H\hi') }}.@endif
                    @if (! $doc->envoye_email_le && ! $doc->envoye_whatsapp_le) Pas encore transmis. @endif
                </p>

                <form method="POST" action="{{ route('console.facturation.envoyer', $doc) }}" style="display:grid; gap:.5rem">
                    @csrf
                    <input type="email" name="email" value="{{ old('email', $doc->client_email) }}" placeholder="E-mail du client" required>
                    <textarea name="message" rows="5" required>{{ old('message', "Bonjour {$doc->client_nom},\n\nVous trouverez ci-joint votre " . mb_strtolower($doc->libelle()) . ' d’un montant de ' . D::euros($doc->total_centimes) . ".\n\nMerci de votre confiance,") }}</textarea>
                    @if ($doc->event)
                        <label class="champ-bascule"><input type="checkbox" name="joindre_cgv" value="1" checked> Joindre les CGV en PDF</label>
                    @endif
                    <button class="btn btn-primary"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer par e-mail</button>
                </form>

                <div style="margin-top:.8rem; display:grid; gap:.5rem">
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn" style="background:#1A7F45; border-color:#1A7F45; color:#fff">
                            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Envoyer sur WhatsApp
                        </a>
                    @else
                        <p class="champ-aide" style="margin:0">Ajoutez un téléphone au client pour l’envoi WhatsApp.</p>
                    @endif
                    <button type="button" class="mini" data-copier-texte="{{ $lien }}">Copier le lien de téléchargement ({{ \App\Solen\Documents::VALIDITE_JOURS }} jours)</button>
                </div>
            </div>
        @endif

        {{-- ────────────────────────────────────────── Historique ── --}}
        <div class="carte">
            <h2>Rattachements</h2>
            <div class="liste">
                @if ($doc->event)
                    <a href="{{ route('console.mariage', $doc->event->slug) }}" class="ligne" style="color:inherit; text-decoration:none">
                        <span class="icone"><i class="fa-solid fa-heart" aria-hidden="true"></i></span>
                        <div class="corps"><strong>{{ $doc->event->nom }}</strong><span>Mariage</span></div>
                    </a>
                @endif
                @if ($doc->origine)
                    <a href="{{ route('console.facturation.montrer', $doc->origine) }}" class="ligne" style="color:inherit; text-decoration:none">
                        <span class="icone"><i class="fa-solid fa-arrow-turn-up" aria-hidden="true"></i></span>
                        <div class="corps"><strong>{{ $doc->origine->libelle() }}</strong><span>Document d’origine</span></div>
                    </a>
                @endif
                @foreach ($doc->derives as $derive)
                    <a href="{{ route('console.facturation.montrer', $derive) }}" class="ligne" style="color:inherit; text-decoration:none">
                        <span class="icone"><i class="fa-solid fa-arrow-turn-down" aria-hidden="true"></i></span>
                        <div class="corps"><strong>{{ $derive->libelle() }}</strong><span>{{ D::euros($derive->total_centimes) }}</span></div>
                    </a>
                @endforeach
                @if (! $doc->event && ! $doc->origine && $doc->derives->isEmpty())
                    <p class="carte-aide" style="margin:0">Aucun.</p>
                @endif
            </div>
            @if ($doc->notes_internes)
                <p class="carte-aide" style="margin:1rem 0 0"><strong>Notes internes :</strong> {{ $doc->notes_internes }}</p>
            @endif
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-copier-texte]').forEach((b) => b.addEventListener('click', async () => {
        await navigator.clipboard.writeText(b.dataset.copierTexte);
        const t = b.textContent; b.textContent = 'Lien copié'; setTimeout(() => (b.textContent = t), 1800);
    }));
</script>

@endsection
