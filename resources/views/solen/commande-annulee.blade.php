@extends('solen.layout')

@section('title', 'Commande abandonnée — Solen')

@section('content')

<section style="padding-block: clamp(3rem, 7vw, 5.5rem)">
    <div class="wrap" style="max-width: 560px; text-align: center">
        <p class="eyebrow">Commande</p>
        <h1 style="font-size: clamp(1.9rem, 4.5vw, 2.6rem)">Vous avez interrompu le paiement.</h1>
        <p class="lead">
            Aucun montant n’a été prélevé et rien n’a été créé. Vos réponses ne
            sont pas perdues : reprenez quand vous voulez.
        </p>

        <div class="hero-cta" style="justify-content:center">
            <a href="{{ route('commander', $commande->plan) }}" class="btn btn-primary">
                Reprendre ma commande
            </a>
            <a href="{{ route('solen.landing') }}" class="btn btn-ghost">Revoir les formules</a>
        </div>

        <p style="font-size:.85rem; color:var(--ink-mute); margin-top:2rem">
            Une question avant de vous décider ?
            <a href="mailto:{{ config('solen.brand.email') }}">{{ config('solen.brand.email') }}</a>
        </p>
    </div>
</section>

@endsection
