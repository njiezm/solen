@extends('espace.layout')

@section('titre', 'Pages et contenu')
@section('chapeau', 'Le texte, les photos et les listes de votre site. Rien ici ne demande de connaissances techniques.')

@section('contenu')

<div class="carte">
    <div class="liste">
        @foreach ($pages as $cle => $page)
            @php $total = $comptes[$cle] ?? 0; @endphp

            <div class="ligne">
                <span class="icone"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>

                <div class="corps">
                    <strong>{{ $page['nom'] }}</strong>
                    <span>
                        {{ $total === 0
                            ? 'Aucun contenu pour l’instant'
                            : $total . ' élément' . ($total > 1 ? 's' : '') }}
                    </span>
                </div>

                <div class="outils">
                    <a href="{{ route('espace.page', $cle) }}" class="mini">Modifier</a>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection
