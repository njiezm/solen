<?php

/*
|--------------------------------------------------------------------------
| Solen — catalogue produit
|--------------------------------------------------------------------------
|
| Source de vérité unique de l'offre : marque, modules, formules, thèmes.
| La landing page lit ce fichier. En Phase 0, les clés 'modules', 'plans'
| et 'themes' seront reversées dans les tables du même nom par un seeder,
| ce fichier restant la définition de référence.
|
*/

return [

    /*
    | Domaine racine sous lequel les mariages sont servis en sous-domaine
    | (maeva-gilles.solen.app). Laisser vide en local : le middleware
    | ResolveEvent retombe alors sur l'hôte de APP_URL puis sur l'événement
    | par défaut.
    */
    'domaine_racine' => env('SOLEN_DOMAINE_RACINE'),

    /*
    | L'entreprise qui vend Solen. Ces informations figurent sur chaque
    | facture, devis et document légal : elles sont obligatoires en France.
    | Tant qu'un champ requis manque, la console le signale et refuse
    | d'émettre une facture plutôt que d'en produire une invalide.
    */
    'entreprise' => [
        'nom_commercial' => env('SOLEN_ENTREPRISE_NOM_COMMERCIAL', 'Solen by NJIEZM.FR'),
        'raison_sociale' => env('SOLEN_ENTREPRISE_RAISON_SOCIALE', 'NJIEZM.FR'),
        'forme'          => env('SOLEN_ENTREPRISE_FORME', 'Entreprise individuelle'),
        'dirigeant'      => env('SOLEN_ENTREPRISE_DIRIGEANT'),
        'adresse'        => env('SOLEN_ENTREPRISE_ADRESSE'),
        'siret'          => env('SOLEN_ENTREPRISE_SIRET'),
        'tva'            => env('SOLEN_ENTREPRISE_TVA'),          // numéro intracommunautaire, vide en franchise
        'mention_tva'    => env('SOLEN_ENTREPRISE_MENTION_TVA', 'TVA non applicable, art. 293 B du CGI'),
        'email'          => env('SOLEN_ENTREPRISE_EMAIL', 'contact@solen.app'),
        'telephone'      => env('SOLEN_ENTREPRISE_TELEPHONE'),
        'site'           => env('SOLEN_ENTREPRISE_SITE', 'https://solen.app'),
        'iban'           => env('SOLEN_ENTREPRISE_IBAN'),
        'bic'            => env('SOLEN_ENTREPRISE_BIC'),
        'hebergeur'      => env('SOLEN_HEBERGEUR', 'à compléter : nom, adresse et téléphone de l’hébergeur'),
        // Obligatoire pour vendre à des particuliers (art. L612-1 du Code de la consommation).
        'mediateur'      => env('SOLEN_MEDIATEUR'),
        'delai_paiement_jours' => (int) env('SOLEN_DELAI_PAIEMENT', 15),
    ],

    'brand' => [
        'name'     => 'Solen',
        'baseline' => 'Chaque instant du jour J, entre vos mains.',
        'pitch'    => "Les autres vous vendent un site de mariage. Solen fait vivre la journée.",
        'email'    => 'contact@solen.app',
    ],

    /*
    | Chiffres affichés en preuve sociale sur la landing.
    | TODO : remplacer par un export de la base de production du mariage
    | Maëva & Gilles (26.12.2025). La base locale ne contient que des
    | données de développement.
    */
    'showcase' => [
        'verified' => false, // passer à true une fois les chiffres de prod injectés
        'couple'   => 'Maëva & Gilles',
        'date'     => '26 décembre 2025',
        'lieu'     => 'Le Lamentin, Martinique',
        'stats'    => [
            ['value' => '120', 'label' => 'invités connectés'],
            ['value' => '340', 'label' => 'photos partagées'],
            ['value' => '86',  'label' => 'messages au livre d’or'],
            ['value' => '4',   'label' => 'jeux joués en direct'],
        ],
    ],

    /*
    | Modules du catalogue.
    | statut : 'live' = déjà codé · 'build' = à construire · 'next' = V2
    | phase  : socle | avant | pendant | apres | pro
    */
    /*
    | Catalogue réduit à ce qui est réellement livré. Les briques encore à
    | construire ne sont plus proposées : mieux vaut une offre courte et
    | honnête qu'une liste d'écrans marqués « en cours ». Ce qui viendra
    | plus tard est annoncé dans 'a_venir', sans être vendu.
    */
    'modules' => [

        // ---------------------------------------------------------- SOCLE
        ['key' => 'site',        'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-globe',            'nom' => 'Mini-site invité',        'desc' => 'Votre univers à votre adresse, sur mobile comme sur ordinateur.'],
        ['key' => 'compte',      'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-hourglass-half',   'nom' => 'Compte à rebours',        'desc' => 'Plusieurs jalons : cérémonie, réception, ouverture du bal.'],
        ['key' => 'histoire',    'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-book-open',        'nom' => 'Notre histoire',          'desc' => 'Une frise de votre rencontre, photos à l’appui.'],
        ['key' => 'rsvp',        'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-envelope-open-text', 'nom' => 'RSVP et liste d’invités', 'desc' => 'Chaque foyer répond depuis son lien personnel : présence, moments, allergies.'],
        ['key' => 'pratique',    'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-map-location-dot', 'nom' => 'Infos pratiques',         'desc' => 'Hébergement, transport, parking, dress code, contacts.'],
        ['key' => 'qr',          'phase' => 'socle',   'statut' => 'live', 'icon' => 'fa-qrcode',           'nom' => 'QR codes & statistiques', 'desc' => 'Un QR par table, par lieu, par support — et le compte des scans.'],

        // ---------------------------------------------------------- AVANT
        ['key' => 'menu',        'phase' => 'avant',   'statut' => 'live', 'icon' => 'fa-utensils',         'nom' => 'Menu & allergies',        'desc' => 'Le menu en ligne, et les régimes que vos invités vous signalent.'],
        ['key' => 'cagnotte',    'phase' => 'avant',   'statut' => 'live', 'icon' => 'fa-gift',             'nom' => 'Cagnotte & liste',        'desc' => 'Versée sur votre compte, avec jauge d’objectif. 0 % de commission.'],

        // -------------------------------------------------------- PENDANT
        // Le déroulé en direct est archivé : il demandait quelqu'un pour le
        // piloter pendant la cérémonie. Le livret PDF prend le relais.
        ['key' => 'livret',      'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-book-bible',       'nom' => 'Livret de cérémonie',     'desc' => 'Votre livret en PDF, feuilleté page par page sur le téléphone.', 'star' => true],
        ['key' => 'photobooth',  'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-camera-retro',     'nom' => 'Photobooth',              'desc' => 'Sur le téléphone des invités, ou en borne fixe sur tablette.',      'star' => true],
        ['key' => 'mur',         'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-images',           'nom' => 'Mur photo live',          'desc' => 'Toutes les photos des invités, au même endroit.'],
        ['key' => 'livredor',    'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-feather-pointed',  'nom' => 'Livre d’or',              'desc' => 'Les mots de vos invités, gardés pour toujours.'],
        ['key' => 'jeux',        'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-dice',             'nom' => 'Jeux & animations',       'desc' => 'Qui de nous 2, chasse photo, mots croisés, memory.'],
        ['key' => 'hommage',     'phase' => 'pendant', 'statut' => 'live', 'icon' => 'fa-dove',             'nom' => 'Une pensée pour',         'desc' => 'Une page pour celles et ceux qui manquent à l’appel.'],

        // ---------------------------------------------------------- APRÈS
        ['key' => 'pdf',         'phase' => 'apres',   'statut' => 'live', 'icon' => 'fa-book',             'nom' => 'Livre souvenir imprimable', 'desc' => 'Vos messages et vos photos réunis en un PDF prêt à faire relier.', 'star' => true],
    ],

    /*
    | Annoncé sur la vitrine, pas encore vendu ni activable.
    */
    'a_venir' => [
        ['icon' => 'fa-chair',              'nom' => 'Plan de table interactif'],
        ['icon' => 'fa-display',            'nom' => 'Écran de salle projeté'],
        ['icon' => 'fa-mobile-screen',      'nom' => 'Mode hors-ligne'],
        ['icon' => 'fa-music',              'nom' => 'Playlist collaborative'],
        ['icon' => 'fa-video',              'nom' => 'Livre d’or vocal et vidéo'],
    ],

    'phases' => [
        'socle'   => ['nom' => 'Dans toutes les formules', 'desc' => 'Le socle commun, quel que soit votre choix.'],
        'avant'   => ['nom' => 'Avant',     'desc' => 'Préparer et rassembler.'],
        'pendant' => ['nom' => 'Le jour J', 'desc' => 'Là où Solen change tout.'],
        'apres'   => ['nom' => 'Après',     'desc' => 'Garder la journée, pas seulement s’en souvenir.'],
    ],

    /*
    | Aucune formule gratuite : chaque mariage est un mariage payé. La plus
    | petite formule reprend tout ce que l'ancienne « Découverte » offrait.
    */
    'plans' => [
        [
            'key'      => 'essentiel',
            'nom'      => 'Essentiel',
            'prix'     => 99,
            'accroche' => 'L’organisation',
            'desc'     => 'Tout ce qu’il faut pour préparer et coordonner votre mariage.',
            'features' => [
                'Mini-site, compte à rebours, notre histoire',
                'RSVP et liste d’invités',
                'Infos pratiques et livre d’or',
                'Menu et allergies',
                'Cagnotte versée sur votre compte, 0 % de commission',
                'QR codes et statistiques de scan',
                'Mur photo et photobooth, 500 photos',
                'Archive 6 mois',
            ],
            'limite'   => null,
        ],
        [
            'key'      => 'celebration',
            'nom'      => 'Célébration',
            'prix'     => 249,
            'accroche' => 'Le jour J',
            'desc'     => 'La formule complète pour faire vivre la journée à vos invités.',
            'populaire' => true,
            'features' => [
                'Tout Essentiel, sans limite de photos',
                'Livret de cérémonie à feuilleter',
                'Les 4 jeux et le classement',
                'Page « Une pensée pour »',
                'Photobooth simple, sans limite',
                'Archive 2 ans',
            ],
            'limite'   => null,
        ],
        [
            'key'      => 'signature',
            'nom'      => 'Signature',
            'prix'     => 549,
            'accroche' => 'Sans compromis',
            'desc'     => 'Tout Célébration, plus le photobooth en borne et le livre souvenir imprimable.',
            'features' => [
                'Tout Célébration',
                'Photobooth en borne fixe sur tablette',
                'Compte à rebours, poses multiples, cadre à vos couleurs',
                'Mode plein écran verrouillé pour la salle',
                'Archive à vie',
                'Assistance prioritaire le jour J',
            ],
            'limite'   => null,
        ],
    ],

    'options' => [
        ['nom' => 'Module supplémentaire',          'prix' => '29 €'],
        ['nom' => 'Assistance sur place le jour J', 'prix' => 'dès 149 €'],
        ['nom' => 'Nom de domaine',                 'prix' => '25 €/an'],
        ['nom' => 'Année d’archive supplémentaire', 'prix' => '19 €'],
        ['nom' => 'Thème sur mesure',               'prix' => '89 €'],
    ],

    /*
    | Thèmes visuels. Ces jetons alimentent les variables CSS du site invité.
    | 'sapin' est le thème du mariage Maëva & Gilles, conservé tel quel.
    */
    /*
    | Chaque thème est un univers, pas une palette : les formes, le caractère
    | typographique et la densité changent aussi. Deux mariages aux mêmes
    | couleurs mais l'un anguleux et sobre, l'autre arrondi et festif, ne se
    | ressemblent pas.
    */
    'themes' => [
        [
            'key' => 'ivoire', 'nom' => 'Ivoire & Encre',
            'ink' => '#1B1B2F', 'surface' => '#FCFAF7', 'accent' => '#C99B63', 'secondaire' => '#8A7A63',
            'forme' => 'doux', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'naturel',
            'font_display' => "'Fraunces', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],
        [
            'key' => 'sapin', 'nom' => 'Sapin & Champagne',
            'ink' => '#013220', 'surface' => '#F9F8F4', 'accent' => '#B55239', 'secondaire' => '#0F3B26',
            'forme' => 'doux', 'caractere' => 'classique', 'densite' => 'confortable', 'traitement' => 'chaud',
            'font_display' => "'Great Vibes', cursive", 'font_body' => "'Montserrat', sans-serif",
        ],
        [
            'key' => 'terracotta', 'nom' => 'Terracotta',
            'ink' => '#5C2E22', 'surface' => '#FAF3EC', 'accent' => '#C97B4A', 'secondaire' => '#9C5B3C',
            'forme' => 'rond', 'caractere' => 'classique', 'densite' => 'confortable', 'traitement' => 'argentique',
            'font_display' => "'Lora', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],
        [
            'key' => 'nuit', 'nom' => 'Bleu Nuit & Or',
            'ink' => '#10203F', 'surface' => '#F7F5F0', 'accent' => '#C9A227', 'secondaire' => '#1D3557',
            'forme' => 'droit', 'caractere' => 'festif', 'densite' => 'aeree', 'traitement' => 'noir_blanc',
            'font_display' => "'Cormorant Garamond', Georgia, serif", 'font_body' => "'Montserrat', sans-serif",
        ],
        [
            'key' => 'tropical', 'nom' => 'Tropical',
            'ink' => '#0B3B36', 'surface' => '#FFFDF7', 'accent' => '#E9743F', 'secondaire' => '#12776A',
            'forme' => 'pilule', 'caractere' => 'festif', 'densite' => 'compacte', 'traitement' => 'chaud',
            'font_display' => "'Fraunces', Georgia, serif", 'font_body' => "'Montserrat', sans-serif",
        ],
        [
            'key' => 'blush', 'nom' => 'Blush & Craie',
            'ink' => '#4A3038', 'surface' => '#FDF8F7', 'accent' => '#D9909A', 'secondaire' => '#B2707C',
            'forme' => 'rond', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'doux',
            'font_display' => "'Cormorant Garamond', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],

        [
            'key' => 'olivier', 'nom' => 'Olivier & Lin',
            'ink' => '#2F3A2C', 'surface' => '#F6F4EC', 'accent' => '#7C8B5A', 'secondaire' => '#A68A64',
            'forme' => 'doux', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'doux',
            'font_display' => "'Cormorant Garamond', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],
        [
            'key' => 'bordeaux', 'nom' => 'Bordeaux & Velours',
            'ink' => '#3B0F1E', 'surface' => '#FBF6F2', 'accent' => '#8E2A3F', 'secondaire' => '#B8875A',
            'forme' => 'droit', 'caractere' => 'classique', 'densite' => 'confortable', 'traitement' => 'argentique',
            'font_display' => "'Playfair Display', Georgia, serif", 'font_body' => "'Lora', Georgia, serif",
        ],
        [
            'key' => 'lagune', 'nom' => 'Lagune',
            'ink' => '#0E3A4A', 'surface' => '#F4FAFA', 'accent' => '#1C9AA5', 'secondaire' => '#E8B04B',
            'forme' => 'rond', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'naturel',
            'font_display' => "'Fraunces', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],
        [
            'key' => 'madras', 'nom' => 'Madras',
            'ink' => '#3A1A12', 'surface' => '#FFF9EE', 'accent' => '#C8312B', 'secondaire' => '#E0A526',
            'forme' => 'pilule', 'caractere' => 'festif', 'densite' => 'confortable', 'traitement' => 'chaud',
            'font_display' => "'Playfair Display', Georgia, serif", 'font_body' => "'Montserrat', sans-serif",
        ],
        [
            'key' => 'sable', 'nom' => 'Sable & Corail',
            'ink' => '#3D2E26', 'surface' => '#FCF7F1', 'accent' => '#E07A5F', 'secondaire' => '#81B29A',
            'forme' => 'doux', 'caractere' => 'classique', 'densite' => 'confortable', 'traitement' => 'chaud',
            'font_display' => "'Lora', Georgia, serif", 'font_body' => "'Montserrat', sans-serif",
        ],
        [
            'key' => 'lavande', 'nom' => 'Lavande & Perle',
            'ink' => '#2E2A47', 'surface' => '#F8F7FC', 'accent' => '#8A7DBE', 'secondaire' => '#C4A9C9',
            'forme' => 'rond', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'doux',
            'font_display' => "'Great Vibes', cursive", 'font_body' => "'Inter', sans-serif",
        ],

        /*
        | Le seul thème sombre : fond profond, texte clair. Pour une soirée,
        | un mariage d'hiver, ou un couple qui ne veut pas d'ivoire. Les
        | photos y ressortent nettement plus.
        */
        [
            'key' => 'minuit', 'nom' => 'Minuit',
            'ink' => '#EDE9E3', 'surface' => '#14141C', 'fond' => '#08080D',
            'accent' => '#C9A227', 'secondaire' => '#6D6A8A',
            'forme' => 'droit', 'caractere' => 'epure', 'densite' => 'aeree', 'traitement' => 'argentique',
            'font_display' => "'Cormorant Garamond', Georgia, serif", 'font_body' => "'Inter', sans-serif",
        ],
    ],

    'faq' => [
        ['q' => 'Nos invités doivent-ils installer une application ?',
         'r' => 'Non. Un QR code ou un lien suffit, tout se passe dans le navigateur. Ceux qui le souhaitent peuvent ajouter Solen à leur écran d’accueil en un geste.'],
        ['q' => 'Et si le réseau ne passe pas dans la salle ?',
         'r' => 'Le livret se charge en entier dès son ouverture : une fois affiché, il se feuillette même si le réseau décroche. Prévoyez simplement de l’ouvrir à l’entrée, là où ça capte.'],
        ['q' => 'Faut-il quelqu’un pour faire fonctionner Solen le jour J ?',
         'r' => 'Non. Tout est prêt d’avance : programme, livret, jeux et photobooth fonctionnent seuls. Vous profitez de votre journée.'],
        ['q' => 'Où va l’argent de la cagnotte ?',
         'r' => 'Directement sur votre compte bancaire, via Stripe. Solen ne prend aucune commission : vous ne payez que les frais bancaires de Stripe.'],
        ['q' => 'Notre mariage n’est pas religieux, est-ce adapté ?',
         'r' => 'Oui. Solen couvre le civil, le laïque, le catholique et l’évangélique, et vous composez votre journée librement, moment par moment.'],
        ['q' => 'Que deviennent les photos et les messages après le mariage ?',
         'r' => 'Ils restent accessibles pendant toute la durée d’archive de votre formule, et vous pouvez à tout moment tout exporter en haute définition ou générer un livre prêt à imprimer.'],
    ],
];
