@extends('console.layout')

@section('titre', $mariage->nom)
@section('chapeau', ($mariage->dateLocale()?->translatedFormat('j F Y') ?? 'Date à définir') . ' · /' . $mariage->slug)

@section('contenu')

@if ($identifiants = session('identifiants'))
    <div class="carte" style="border-color:var(--accent)">
        <h2>Identifiants des mariés</h2>
        <p class="carte-aide">Transmettez-les maintenant : le mot de passe ne sera plus jamais affiché.</p>
        <div class="liste">
            <div class="ligne">
                <span class="icone"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                <div class="corps"><strong id="id-email">{{ $identifiants['email'] }}</strong><span>adresse de connexion</span></div>
                <div class="outils"><button class="mini" data-copier="id-email">Copier</button></div>
            </div>
            <div class="ligne">
                <span class="icone"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
                <div class="corps"><strong id="id-mdp">{{ $identifiants['motDePasse'] }}</strong><span>mot de passe provisoire</span></div>
                <div class="outils"><button class="mini" data-copier="id-mdp">Copier</button></div>
            </div>
        </div>
    </div>
@endif

<div class="tuiles">
    @foreach ($detail as $chiffre)
        <div class="tuile">
            <div class="nombre">{{ $chiffre['nombre'] }}</div>
            <div class="quoi">{{ $chiffre['quoi'] }}</div>
        </div>
    @endforeach
</div>

<div class="carte">
    <h2>Accès</h2>
    <div class="liste">
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
            <div class="corps">
                <strong id="url-site">{{ route('landing', $mariage->slug) }}</strong>
                <span>le site des invités</span>
            </div>
            <div class="outils">
                <button class="mini" data-copier="url-site">Copier</button>
                <a href="{{ route('landing', $mariage->slug) }}" target="_blank" rel="noopener" class="mini">Ouvrir</a>
            </div>
        </div>
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-sliders" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>Espace des mariés</strong>
                <span>{{ $modulesActifs }} module(s) actif(s) sur {{ $modulesTotal }} rattaché(s)</span>
            </div>
            <div class="outils">
                <a href="{{ route('espace.index', $mariage->slug) }}" class="mini">Ouvrir</a>
                <a href="{{ route('espace.modules', $mariage->slug) }}" class="mini">Modules</a>
            </div>
        </div>
        <div class="ligne">
            <span class="icone"><i class="fa-solid fa-timeline" aria-hidden="true"></i></span>
            <div class="corps">
                <strong>Programme du jour J</strong>
                <span>{{ $mariage->parts()->count() }} moment(s) dans la journée</span>
            </div>
            <div class="outils">
                <a href="{{ route('espace.programme', $mariage->slug) }}" class="mini">Modifier</a>
                <a href="{{ route('espace.jeux.index', $mariage->slug) }}" class="mini">Jeux</a>
                <a href="{{ route('admin.qrcodes.index', $mariage->slug) }}" class="mini">QR et impressions</a>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────── Accompagnement ── --}}
<div class="carte" id="accompagnement">
    <h2>Accompagnement</h2>
    <p class="carte-aide">
        Cochez ce que Solen prend en charge : le couple voit « Solen s’en occupe » sur ces étapes.
        Préparation à {{ $avancement['pourcentage'] }} % ({{ $avancement['faites'] }}/{{ $avancement['total'] }}).
    </p>

    <div class="liste" style="margin-bottom:1.2rem">
        @foreach ($avancement['etapes'] as $etape)
            <div class="ligne {{ $etape['fait'] ? 'inactive' : '' }}">
                <span class="icone"><i class="fa-{{ $etape['fait'] ? 'solid fa-check' : 'regular fa-circle' }}" aria-hidden="true"></i></span>
                <div class="corps">
                    <strong>{{ $etape['titre'] }}</strong>
                    <span>{{ $etape['fait'] ? 'Fait' : ($etape['solen'] ? 'À faire par Solen' : 'À faire par le couple') }}</span>
                </div>
                @if (! $etape['fait'] && Route::has($etape['route']))
                    <div class="outils"><a href="{{ route($etape['route'], $mariage->slug) }}" class="mini">{{ $etape['solen'] ? 'Le faire' : 'Voir' }}</a></div>
                @endif
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('console.mariage.accompagnement', $mariage->slug) }}" enctype="multipart/form-data">
        @csrf
        <div class="champ-cases" style="grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); display:grid">
            @foreach (\App\Models\Event::ACCOMPAGNEMENTS as $cle => $libelle)
                <label class="champ-bascule">
                    <input type="checkbox" name="accompagnement[]" value="{{ $cle }}" @checked($mariage->accompagne($cle))> {{ $libelle }}
                </label>
            @endforeach
        </div>

        <div class="grille-champs" style="margin-top:1.2rem">
            <div class="champ" style="grid-column:1 / -1">
                <label for="notes">Carnet de l’équipe <span class="champ-aide" style="display:inline">(jamais montré au couple)</span></label>
                <textarea id="notes" name="notes_internes" rows="4" placeholder="Appel du 3 mars : veulent un livret bilingue, envoi des textes avant le 15.">{{ $mariage->notes_internes }}</textarea>
            </div>
            <div class="champ">
                <label for="logo">Logo ou monogramme des mariés</label>
                @if ($mariage->logo)
                    <div class="champ-apercu"><img src="{{ Storage::url($mariage->logo) }}" alt="" style="max-height:80px"></div>
                    <label class="champ-bascule"><input type="checkbox" name="retirer_logo" value="1"> Retirer le logo</label>
                @endif
                <input type="file" id="logo" name="logo" accept="image/*">
                <p class="champ-aide">Posé au centre des QR codes et sur les supports imprimés. PNG transparent conseillé.</p>
            </div>
        </div>

        <div class="actions"><button class="btn btn-primary">Enregistrer l’accompagnement</button></div>
    </form>
</div>

{{-- ───────────────────────────────────── Remise des clés et documents ── --}}
@php
    $kit = \App\Solen\Documents::KIT;
    $proprio = $membres->firstWhere('pivot.role', 'proprietaire');
    $factures = \App\Models\DocumentCommercial::where('event_id', $mariage->id)->whereNotNull('numero')->orderByDesc('id')->get();
@endphp

@if ($remise = session('remise'))
    <div class="carte" style="border-color:var(--live)">
        <h2>Les clés sont prêtes</h2>
        <p class="carte-aide">Lien d’accès à usage unique, valable {{ \App\Solen\Invitations::VALIDITE_JOURS }} jours :</p>
        <div class="ligne"><div class="corps"><strong id="lien-remise" style="white-space:normal; word-break:break-all; font-weight:500">{{ $remise['lien'] }}</strong></div>
            <div class="outils"><button class="mini" data-copier="lien-remise">Copier</button></div></div>
        @if ($remise['whatsapp'])
            <div class="actions"><a href="{{ $remise['whatsapp'] }}" target="_blank" rel="noopener" class="btn" style="background:#1A7F45;border-color:#1A7F45;color:#fff"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Envoyer aussi sur WhatsApp</a></div>
        @endif
    </div>
@endif

<div class="carte" id="remise">
    <h2>Remise des clés</h2>
    <p class="carte-aide">
        Vous avez préparé le mariage ? Confiez-le au couple : son compte est créé, il reçoit un lien pour choisir
        son mot de passe et les documents cochés en pièces jointes. Vous gardez la main depuis la console.
    </p>

    <form method="POST" action="{{ route('console.mariage.remise', $mariage->slug) }}">
        @csrf
        <div class="grille-champs">
            <div class="champ"><label for="r-nom">Nom</label>
                <input type="text" id="r-nom" name="name" value="{{ old('name', $proprio?->name ?? $mariage->nom) }}" required></div>
            <div class="champ"><label for="r-email">E-mail</label>
                <input type="email" id="r-email" name="email" value="{{ old('email', $proprio?->email) }}" required></div>
            <div class="champ"><label for="r-tel">WhatsApp <span class="champ-aide" style="display:inline">(facultatif)</span></label>
                <input type="tel" id="r-tel" name="telephone" value="{{ old('telephone') }}" placeholder="0696 12 34 56"></div>
            <div class="champ" style="grid-column:1 / -1"><label for="r-msg">Message</label>
                <textarea id="r-msg" name="message" rows="5">{{ old('message', "Bonjour " . ($mariage->partenaire_1 && $mariage->partenaire_2 ? $mariage->partenaire_1 . ' et ' . $mariage->partenaire_2 : '') . ",\n\nVotre site de mariage est prêt ! Nous l’avons préparé pour vous : il ne vous reste qu’à le parcourir et à ajuster ce que vous souhaitez.\n\nNous restons à vos côtés jusqu’au jour J.") }}</textarea></div>
        </div>
        <p class="carte-aide" style="margin:.4rem 0">Documents joints :</p>
        <div style="display:flex; flex-wrap:wrap; gap:.4rem 1.2rem">
            @foreach ($kit as $cle => $nom)
                <label class="champ-bascule"><input type="checkbox" name="pieces[]" value="{{ $cle }}" @checked(in_array($cle, ['bienvenue', 'qr', 'cgv']))> {{ $nom }}</label>
            @endforeach
        </div>
        <label class="champ-bascule" style="margin-top:.8rem"><input type="checkbox" name="publier" value="1" @checked(! $mariage->estPublie())> Publier le site en même temps</label>
        <div class="actions"><button class="btn btn-primary"><i class="fa-solid fa-key" aria-hidden="true"></i> Remettre les clés</button></div>
    </form>
</div>

<div class="carte" id="documents">
    <h2>Documents à transmettre</h2>
    <p class="carte-aide">Aperçu de chaque document, puis envoi groupé par e-mail ou par WhatsApp (liens de téléchargement valables {{ \App\Solen\Documents::VALIDITE_JOURS }} jours).</p>

    <form method="POST" action="{{ route('console.mariage.documents.envoyer', $mariage->slug) }}" id="form-documents">
        @csrf
        <div class="liste">
            @foreach ($kit as $cle => $nom)
                <div class="ligne">
                    <input type="checkbox" name="pieces[]" value="{{ $cle }}" aria-label="{{ $nom }}" @checked(in_array($cle, ['bienvenue', 'qr']))
                           style="width:18px; height:18px; accent-color:var(--accent-deep)">
                    <div class="corps"><strong>{{ $nom }}</strong></div>
                    <div class="outils"><a href="{{ route('console.mariage.document', [$mariage->slug, $cle]) }}" target="_blank" rel="noopener" class="mini">Aperçu PDF</a></div>
                </div>
            @endforeach
            @foreach ($factures as $f)
                <div class="ligne">
                    <input type="checkbox" name="factures[]" value="{{ $f->id }}" aria-label="{{ $f->libelle() }}" style="width:18px; height:18px; accent-color:var(--accent-deep)">
                    <div class="corps"><strong>{{ $f->libelle() }}</strong><span>{{ \App\Models\DocumentCommercial::euros($f->total_centimes) }} · {{ \App\Models\DocumentCommercial::STATUTS[$f->statut] ?? $f->statut }}</span></div>
                    <div class="outils"><a href="{{ route('console.facturation.montrer', $f) }}" class="mini">Ouvrir</a></div>
                </div>
            @endforeach
        </div>

        <div class="grille-champs" style="margin-top:1rem">
            <div class="champ"><label for="d-email">E-mail</label><input type="email" id="d-email" name="email" value="{{ $proprio?->email }}"></div>
            <div class="champ"><label for="d-tel">WhatsApp</label><input type="tel" id="d-tel" name="telephone" placeholder="0696 12 34 56"></div>
            <div class="champ" style="grid-column:1 / -1"><label for="d-msg">Message</label>
                <textarea id="d-msg" name="message" rows="3">Bonjour,

Voici les documents de votre mariage.</textarea></div>
        </div>
        <div class="actions">
            <button class="btn btn-primary"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer par e-mail</button>
            <button class="btn" formaction="{{ route('console.mariage.documents.whatsapp', $mariage->slug) }}" formtarget="_blank"
                    style="background:#1A7F45;border-color:#1A7F45;color:#fff"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Préparer sur WhatsApp</button>
            <a href="{{ route('console.facturation.creer', ['type' => 'facture', 'mariage' => $mariage->slug]) }}" class="btn">Facturer ce mariage</a>
            <a href="{{ route('console.facturation.creer', ['type' => 'devis', 'mariage' => $mariage->slug]) }}" class="btn">Faire un devis</a>
        </div>
    </form>
</div>

{{-- ─────────────────────────────────────────────────────── Équipe ── --}}
<div class="carte">
    <h2>Équipe du mariage</h2>
    <div class="liste">
        @forelse ($membres as $membre)
            <div class="ligne">
                <span class="icone"><i class="fa-solid {{ $membre->pivot->role === 'proprietaire' ? 'fa-heart' : 'fa-user' }}" aria-hidden="true"></i></span>
                <div class="corps"><strong>{{ $membre->name }}</strong><span>{{ $membre->email }} · {{ $membre->pivot->role === 'proprietaire' ? 'marié·e' : 'aide' }}</span></div>
            </div>
        @empty
            <p class="carte-aide" style="margin:0">Aucun compte rattaché.</p>
        @endforelse
    </div>
    <div class="actions"><a href="{{ route('espace.equipe', $mariage->slug) }}" class="btn">Gérer l’équipe</a></div>
</div>

<form method="POST" action="{{ route('console.mariage.maj', $mariage->slug) }}">
    @csrf
    <div class="carte">
        <h2>Commercial</h2>
        <p class="carte-aide">Monter de formule débloque les modules manquants sans rien écraser.</p>

        <div class="grille-champs">
            <x-champ :champ="[
                        'cle'=>'plan','type'=>'choix','label'=>'Formule','requis'=>true,
                        'options'=>$formules->mapWithKeys(fn($f) => [$f->cle => $f->nom . ' — ' . ($f->estGratuit() ? 'gratuit' : $f->prix . ' €')])->all(),
                     ]" :valeur="$mariage->plan" prefixe="" />

            <x-champ :champ="[
                        'cle'=>'statut','type'=>'choix','label'=>'État','requis'=>true,
                        'options'=>['brouillon'=>'Brouillon','publie'=>'En ligne','archive'=>'Archivé'],
                     ]" :valeur="$mariage->statut" prefixe="" />

            <x-champ :champ="[
                        'cle'=>'commission_bps','type'=>'nombre','label'=>'Commission sur la cagnotte','requis'=>true,
                        'min'=>0,'max'=>2000,
                        'aide'=>'En centièmes de pour cent : 0 = aucune commission, 100 = 1 %, 250 = 2,5 %.',
                     ]" :valeur="$mariage->commission_bps" prefixe="" />
        </div>

        <div style="margin-top:1rem">
            <x-champ :champ="['cle'=>'est_demo','type'=>'booleen','label'=>'Mariage de démonstration']"
                     :valeur="$mariage->est_demo" prefixe="" />
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('console.index') }}" class="btn btn-ghost">Retour à la liste</a>
    </div>
</form>

<div class="carte" style="margin-top:2rem; border-color:#F0D3D0">
    <h2>Archiver ce mariage</h2>
    <p class="carte-aide">
        Le site devient inaccessible aux invités. Les données restent en base et
        peuvent être restaurées. Recopiez <code>{{ $mariage->slug }}</code> pour confirmer.
    </p>

    <form method="POST" action="{{ route('console.mariage.supprimer', $mariage->slug) }}"
          style="display:flex; gap:.6rem; align-items:flex-start">
        @csrf
        @method('DELETE')
        <div style="flex:1; max-width:280px">
            <input type="text" name="confirmation" placeholder="{{ $mariage->slug }}" autocomplete="off"
                   style="width:100%; font:inherit; font-size:.92rem; padding:.6rem .85rem;
                          border:1px solid var(--line); border-radius:var(--r-sm); background:var(--surface)">
            @error('confirmation') <p class="champ-erreur">{{ $message }}</p> @enderror
        </div>
        <button class="mini mini--danger" style="padding:.62rem 1.1rem">Archiver</button>
    </form>
</div>

<script>
    document.querySelectorAll('[data-copier]').forEach((b) => {
        b.addEventListener('click', async () => {
            await navigator.clipboard.writeText(document.getElementById(b.dataset.copier).textContent.trim());
            const t = b.textContent; b.textContent = 'Copié';
            setTimeout(() => (b.textContent = t), 1800);
        });
    });
</script>

@endsection
