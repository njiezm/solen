@extends('layout')
@section('content')

<h2 class="text-center mairie-heading">
    Mairie du Lamentin 🏛️
</h2>

<div class="anniversary-banner">
    <i class="fa-solid fa-calendar-check me-2"></i> 
    Nous célébrons l'anniversaire de notre union civile qui a eu lieu il y a 
    <strong class="text-gold-accent">1 an jour pour jour</strong> à la Mairie du Lamentin !
</div>

<div class="row">
    <div class="col-md-9 mx-auto">
        
        <div class="mairie-story">
            
            <p class="lead fw-bold" style="color:#e8e8e8; /*var(--vert-sapin);*/">
                C'était le 26 décembre 2024. Le début officiel de notre histoire conjugale,
                un moment d'émotion simple et sincère.
            </p>

            <p style="color:#e8e8e8; /*var(--vert-sapin);*/">
                Sous le soleil du Lamentin, nous nous sommes dit "Oui" pour la première fois. Entourés de nos témoins et de notre famille proche, l'émotion était palpable. La robe de Maëva, le costume de Gilles, et le cadre majestueux de la mairie rendaient cet instant inoubliable.
            </p>
            
            <div class="mairie-photo text-center my-4">
    <img 
        src="{{ asset('images/mairie/photo-1.png') }}" 
        alt="Arrivée à la Mairie du Lamentin"
        class="img-fluid rounded shadow"
    >
    <p style="color:#e8e8e8; /*var(--vert-sapin);*/" class="photo-caption mt-2">📸 L’arrivée à la Mairie</p>
</div>

            
            <p style="color:#e8e8e8; /*var(--vert-sapin);*/">
                Le discours de l'Adjointe au Maire Claire TUNORFÉ, à la fois solennel et plein d'humour, restera gravé dans nos mémoires. Chaque mot prononcé, chaque signature apposée sur le registre, confirmait notre engagement l'un envers l'autre.
            </p>

            <p style="color:#e8e8e8; /*var(--vert-sapin);*/">
                Ce jour-là n'était que le premier acte. Aujourd'hui, un an plus tard, nous sommes impatients de célébrer avec vous l'acte deux : la cérémonie religieuse et la fête !
            </p>
            
           <div class="mairie-photo text-center my-4">
    <img 
        src="{{ asset('images/mairie/photo-2.png') }}" 
        alt="Le OUI officiel à la mairie"
        class="img-fluid rounded shadow"
    >
    <p style="color:#e8e8e8; /*var(--vert-sapin);*/" class="photo-caption mt-2">💍 Le OUI officiel</p>
</div>

            <p style="color:#e8e8e8; /*var(--vert-sapin);*/">
                Merci d'avoir été là (pour ceux qui y étaient) et merci d'être là maintenant. Notre amour s'est construit étape par étape, et la Mairie du Lamentin en est la fondation officielle.
            </p>
            
            <div class="mairie-photo text-center my-4">
    <img 
        src="{{ asset('images/mairie/photo-3.png') }}" 
        alt="Sortie des mariés"
        class="img-fluid rounded shadow"
    >
    <p style="color:#e8e8e8; /*var(--vert-sapin);*/" class="photo-caption mt-2">💖 La sortie des Mariés</p>
</div>

            
            <hr class="my-4" style="border-top: 2px solid var(--dore-accent);">
            
            <div class="info-bloc text-center">
                <h4 class="fw-bold" style="color:var(--vert-sapin);">Souvenir d'un jour parfait</h4>
                <p class="mb-1"><i class="fa-solid fa-calendar-alt me-2"></i> Date Officielle : 26 Décembre 2024</p>
                <p class="mb-0"><i class="fa-solid fa-location-dot me-2"></i> Lieu : Mairie du Lamentin, Martinique</p>
            </div>

        </div>
    </div>
</div>

@endsection