@extends('espace.layout')

@section('titre', 'Tableau de bord')
@section('chapeau', 'Tout ce qui concerne votre mariage se pilote depuis ici.')

@section('contenu')

{{-- La progression : voir un pourcentage donne envie de le compléter,
     là où une liste de tâches sans fin décourage. --}}
<div class="carte progression">
    <div class="progression-tete">
        <div>
            <h2 style="margin-bottom:.2rem">
                @if ($avancement['pourcentage'] === 100)
                    Votre mariage est prêt.
                @else
                    Votre mariage est prêt à {{ $avancement['pourcentage'] }} %
                @endif
            </h2>
            <p class="carte-aide" style="margin:0">
                {{ $avancement['faites'] }} étape{{ $avancement['faites'] > 1 ? 's' : '' }}
                sur {{ $avancement['total'] }}
                @if ($avancement['pourcentage'] < 100) — continuez, il en reste peu. @endif
            </p>
        </div>
        <div class="progression-chiffre">{{ $avancement['pourcentage'] }}<small>%</small></div>
    </div>

    <div class="progression-piste" role="progressbar"
         aria-valuenow="{{ $avancement['pourcentage'] }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="Avancement de la préparation">
        <div class="progression-barre" style="width: {{ $avancement['pourcentage'] }}%"></div>
    </div>

    <div class="liste" style="margin-top:1.4rem">
        @foreach ($avancement['etapes'] as $etape)
            <div class="ligne etape-prep {{ $etape['fait'] ? 'faite' : '' }}">
                <span class="icone">
                    <i class="fa-{{ $etape['fait'] ? 'solid fa-check' : 'regular fa-circle' }}" aria-hidden="true"></i>
                </span>

                <div class="corps">
                    <strong>{{ $etape['titre'] }}</strong>
                    <span>{{ $etape['aide'] }}</span>
                </div>

                <div class="outils">
                    @if ($etape['solen'] && ! $etape['fait'])
                        <span class="chip chip-live" style="position:static" title="L’équipe Solen prépare cette partie pour vous">Solen s’en occupe</span>
                    @endif
                    @if (Route::has($etape['route']))
                        <a href="{{ route($etape['route']) }}" class="mini">
                            {{ $etape['fait'] ? 'Modifier' : 'Y aller' }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="tuiles">
    @foreach ($chiffres as $chiffre)
        <div class="tuile">
            <div class="nombre">{{ $chiffre['nombre'] }}</div>
            <div class="quoi">{{ $chiffre['quoi'] }}</div>
        </div>
    @endforeach
</div>

<div class="carte">
    <h2>L’adresse de votre site</h2>
    <p class="carte-aide">C’est le lien à partager à vos invités, et celui que portent vos QR codes.</p>

    <div class="ligne">
        <span class="icone"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
        <div class="corps">
            <strong id="adresse-site">{{ route('landing') }}</strong>
            <span>
                {{ $event->estPublie()
                    ? 'Site publié'
                    : 'Site en brouillon — vos invités ne peuvent pas encore y accéder' }}
            </span>
        </div>
        <div class="outils">
            <button type="button" class="mini" data-copier="adresse-site">Copier</button>
            <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="mini">Ouvrir</a>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-copier]').forEach((bouton) => {
        bouton.addEventListener('click', async () => {
            const source = document.getElementById(bouton.dataset.copier);
            await navigator.clipboard.writeText(source.textContent.trim());
            const initial = bouton.textContent;
            bouton.textContent = 'Copié';
            setTimeout(() => (bouton.textContent = initial), 1800);
        });
    });
</script>

@endsection
