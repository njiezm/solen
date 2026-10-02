<x-courriel::layout
    :titre="'Votre photo — ' . $event->nom"
    preheader="Votre cliché du photobooth est en pièce jointe.">

<p style="margin:0 0 18px;font-size:20px;color:#1B1B2F;font-weight:600;">
    @if ($prenom) {{ $prenom }}, voici votre photo. @else Voici votre photo. @endif
</p>

<p style="margin:0 0 20px;">
    Elle est en pièce jointe de ce message, et elle rejoint aussi le mur
    photo de <strong>{{ $event->nom }}</strong>, avec celles des autres invités.
</p>

<x-courriel::partials.bouton :url="$urlSite" libelle="Voir le mur photo" />

<p style="margin:0;font-size:12.5px;color:#6E6E85;">
    Vous recevez cet e-mail parce que vous avez laissé votre adresse au
    photobooth. Elle ne sert qu’à cet envoi.
</p>

</x-courriel::layout>
