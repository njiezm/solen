@extends('layout')
@section('titre', 'Notre histoire')
@section('content')

{{-- Frise entièrement pilotée par les blocs « étape de votre histoire ». --}}

<header class="page-tete">
    <p class="page-tete-sur">Il était une fois</p>
    <h1>Notre histoire</h1>
    @if ($introduction)
        <p>{{ $introduction }}</p>
    @endif
</header>

@if ($etapes->isEmpty())
    <div class="vide">
        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
        Notre histoire s’écrira bientôt ici.
    </div>
@else
    <ol class="frise">
        @foreach ($etapes as $etape)
            <li class="frise-item">
                <article class="bloc">
                    @if ($etape->date)
                        <span class="frise-date">{{ $etape->date }}</span>
                    @endif

                    <h2>{{ $etape->titre }}</h2>

                    @if ($etape->texte)
                        <p>{!! nl2br(e($etape->texte)) !!}</p>
                    @endif

                    @if ($etape->image)
                        <img src="{{ Str::startsWith($etape->image, ['http', '/']) ? $etape->image : Storage::url($etape->image) }}"
                             alt="{{ $etape->titre }}" loading="lazy">
                    @endif
                </article>
            </li>
        @endforeach
    </ol>
@endif

@endsection
