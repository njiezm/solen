<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
    @include('pdf.partials.papier')
</head>
<body>

@include('pdf.partials.entete', ['titreDoc' => $titre, 'sousTitreDoc' => 'Version du ' . now()->translatedFormat('j F Y')])

<div class="corps">
    @include("legal.contenu.{$piece}")
</div>

</body>
</html>
