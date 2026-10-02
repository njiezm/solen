@extends('solen.layout')

@section('content')
<section class="legal">
    <div class="wrap legal-wrap">
        <p class="eyebrow">{{ config('solen.brand.name') }} by NJIEZM.FR</p>
        <h1>{{ $titre }}</h1>
        <div class="legal-corps">
            @include("legal.contenu.{$contenu}")
        </div>

        {{-- L'entreprise qui édite Solen, signée discrètement. --}}
        <p class="legal-signature">
            {{ config('solen.brand.name') }} est édité par
            <img src="{{ asset('images/njiezm/logo.svg') }}" alt="NJIEZM.FR" height="26">
        </p>
    </div>
</section>
@endsection
