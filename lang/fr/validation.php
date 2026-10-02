<?php

/*
|--------------------------------------------------------------------------
| Messages de validation
|--------------------------------------------------------------------------
|
| Solen s'adresse à des couples, pas à des développeurs : les messages
| doivent être en français et compréhensibles sans jargon.
|
| Seules les règles réellement utilisées par le schéma déclaratif et les
| formulaires de l'application sont traduites.
|
*/

return [

    'accepted'        => 'Vous devez accepter :attribute.',
    'after'           => ':Attribute doit être une date postérieure au :date.',
    'after_or_equal'  => ':Attribute doit être une date égale ou postérieure au :date.',
    'array'           => ':Attribute doit être une liste.',
    'before'          => ':Attribute doit être une date antérieure au :date.',
    'boolean'         => ':Attribute doit être coché ou décoché.',
    'confirmed'       => 'La confirmation de :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date'            => ':Attribute n’est pas une date valide.',
    'date_format'     => ':Attribute ne respecte pas le format :format.',
    'different'       => ':Attribute et :other doivent être différents.',
    'email'           => ':Attribute doit être une adresse e-mail valide.',
    'exists'          => 'La valeur choisie pour :attribute n’existe pas.',
    'file'            => ':Attribute doit être un fichier.',
    'filled'          => ':Attribute doit être renseigné.',
    'image'           => ':Attribute doit être une image.',
    'in'              => 'La valeur choisie pour :attribute n’est pas valide.',
    'integer'         => ':Attribute doit être un nombre entier.',
    'mimes'           => ':Attribute doit être un fichier de type : :values.',
    'numeric'         => ':Attribute doit être un nombre.',
    'present'         => ':Attribute doit être présent.',
    'prohibited'      => ':Attribute n’est pas autorisé.',
    'regex'           => 'Le format de :attribute n’est pas valide.',
    'required'        => 'Le champ :attribute est obligatoire.',
    'required_if'     => ':Attribute est nécessaire lorsque :other vaut :value.',
    'same'            => ':Attribute et :other doivent être identiques.',
    'string'          => ':Attribute doit être du texte.',
    'unique'          => ':Attribute est déjà utilisé.',
    'uploaded'        => 'Le téléversement de :attribute a échoué.',
    'url'             => ':Attribute doit être un lien valide, commençant par https://.',

    'max' => [
        'array'   => ':Attribute ne peut pas contenir plus de :max éléments.',
        'file'    => ':Attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => ':Attribute ne peut pas être supérieur à :max.',
        'string'  => ':Attribute ne doit pas dépasser :max caractères.',
    ],

    'min' => [
        'array'   => ':Attribute doit contenir au moins :min éléments.',
        'file'    => ':Attribute doit peser au moins :min kilo-octets.',
        'numeric' => ':Attribute doit être au moins égal à :min.',
        'string'  => ':Attribute doit contenir au moins :min caractères.',
    ],

    'between' => [
        'array'   => ':Attribute doit contenir entre :min et :max éléments.',
        'file'    => ':Attribute doit peser entre :min et :max kilo-octets.',
        'numeric' => ':Attribute doit être compris entre :min et :max.',
        'string'  => ':Attribute doit contenir entre :min et :max caractères.',
    ],

    /*
    | Les intitulés proviennent du schéma déclaratif : le service Champs
    | transmet le libellé de chaque champ à la validation, il n'y a donc
    | rien à maintenir ici.
    */
    'attributes' => [],
];
