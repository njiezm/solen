@extends('layout')
@section('titre', $titre)
@section('content')

<header class="page-tete">
    <p class="page-tete-sur">En mémoire</p>
    <h1>{{ $titre }}</h1>
    <p>
        {{ $introduction ?: 'En souvenir de celles et ceux qui nous ont quittés, et qui resteront pour toujours dans nos cœurs.' }}
    </p>
</header>

@if ($decedents->isEmpty())
    <div class="vide">
        <i class="fa-solid fa-dove" aria-hidden="true"></i>
        Cette page sera complétée prochainement.
    </div>
@else
    <div class="pensees">
        @foreach ($decedents as $personne)
            <article class="bloc pensee">
                @if ($personne->photo)
                    <img src="{{ Str::startsWith($personne->photo, ['http', '/']) ? $personne->photo : Storage::url($personne->photo) }}"
                         alt="{{ $personne->name }}" loading="lazy">
                @endif
                <div class="pensee-corps">
                    <h2>{{ $personne->name }}</h2>
                    @if ($personne->dates)
                        <p class="doux">{{ $personne->dates }}</p>
                    @endif
                    @if ($personne->message)
                        <p><em>{{ $personne->message }}</em></p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

@endsection
