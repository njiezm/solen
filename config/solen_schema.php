<?php

/*
|--------------------------------------------------------------------------
| Solen — schéma déclaratif
|--------------------------------------------------------------------------
|
| Décrit les CHAMPS, pas les écrans. L'administration lit ce fichier et
| génère les formulaires, la validation et l'affichage toute seule.
|
| Conséquence : ajouter un réglage à un module ou un nouveau type de bloc
| ne demande aucune vue, aucun contrôleur, aucune migration — trois lignes
| ici suffisent.
|
| Types de champ reconnus :
|   texte · texte_long · riche · nombre · montant · date · heure · datetime
|   image · images · fichier · couleur · choix · choix_multiple · booleen
|   lien · email · telephone · liste
|
| Clés communes à tous les champs :
|   cle (obligatoire) · type (obligatoire) · label · aide · requis
|   defaut · options (pour choix) · min · max · placeholder
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Réglages des modules
    |----------------------------------------------------------------------
    | Indexés par la clé du module telle que déclarée dans config/solen.php.
    | Un module absent d'ici n'a simplement aucun réglage.
    */
    'modules' => [

        'site' => [
            ['cle' => 'message_accueil', 'type' => 'texte_long', 'label' => 'Message d’accueil',
             'aide' => 'Affiché en haut de la page d’accueil, sous vos prénoms.'],
            ['cle' => 'photo_couverture', 'type' => 'image', 'label' => 'Photo de couverture'],
            ['cle' => 'image_partage', 'type' => 'image', 'label' => 'Image d’aperçu du lien',
             'aide' => 'Ce qui s’affiche quand le lien est partagé sur WhatsApp, Messenger ou par SMS. Idéalement 1200 × 630 px. À défaut, la photo de couverture est utilisée.'],
            ['cle' => 'dress_code', 'type' => 'texte', 'label' => 'Code vestimentaire',
             'placeholder' => 'Chic & champêtre, vert sapin et rouille'],
            ['cle' => 'mot_de_passe', 'type' => 'texte', 'label' => 'Code d’accès au site',
             'aide' => 'Laissez vide pour un site librement accessible.'],
        ],

        'rsvp' => [
            ['cle' => 'date_limite', 'type' => 'date', 'label' => 'Répondre avant le',
             'aide' => 'Affichée aux invités. Après cette date, le formulaire reste ouvert mais l’annonce disparaît.'],
            ['cle' => 'ouvert_a_tous', 'type' => 'booleen', 'label' => 'Accepter les réponses de personnes hors liste',
             'aide' => 'Sinon, seuls les foyers de votre liste répondent, chacun avec son lien personnel.', 'defaut' => false],
            ['cle' => 'demander_moments', 'type' => 'booleen', 'label' => 'Demander à quels moments ils viennent', 'defaut' => true],
            ['cle' => 'demander_regimes', 'type' => 'booleen', 'label' => 'Demander allergies et régimes', 'defaut' => true],
            ['cle' => 'message_merci', 'type' => 'texte_long', 'label' => 'Message après la réponse',
             'defaut' => 'Merci ! Votre réponse est bien enregistrée.'],
        ],

        'compte' => [
            ['cle' => 'afficher', 'type' => 'booleen', 'label' => 'Afficher le compte à rebours', 'defaut' => true],
            ['cle' => 'message_apres', 'type' => 'texte', 'label' => 'Message une fois la fête lancée',
             'defaut' => 'La fête est lancée ! Profitez de ce moment.'],
        ],

        'livredor' => [
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Texte d’introduction',
             'defaut' => 'Laissez-nous un mot, nous le lirons avec émotion.'],
            ['cle' => 'moderation', 'type' => 'choix', 'label' => 'Modération',
             'options' => ['aucune' => 'Publier immédiatement', 'apres' => 'Valider avant publication'],
             'defaut' => 'aucune'],
            ['cle' => 'longueur_max', 'type' => 'nombre', 'label' => 'Longueur maximale d’un message',
             'defaut' => 1000, 'min' => 100, 'max' => 5000],
        ],

        'mur' => [
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Consigne aux invités',
             'defaut' => 'Partagez vos plus belles photos de la journée.'],
            ['cle' => 'moderation', 'type' => 'choix', 'label' => 'Modération',
             'options' => ['aucune' => 'Publier immédiatement', 'apres' => 'Valider avant publication'],
             'defaut' => 'aucune'],
            ['cle' => 'taille_max_mo', 'type' => 'nombre', 'label' => 'Poids maximal par photo (Mo)',
             'defaut' => 10, 'min' => 1, 'max' => 25],
            ['cle' => 'telechargement', 'type' => 'booleen', 'label' => 'Les invités peuvent télécharger les photos',
             'defaut' => true],
        ],

        'cagnotte' => [
            ['cle' => 'titre', 'type' => 'texte', 'label' => 'Titre de la cagnotte',
             'defaut' => 'Notre liste de mariage'],
            ['cle' => 'lien_externe', 'type' => 'lien', 'label' => 'Cagnotte en ligne existante (Leetchi, Lydia…)',
             'placeholder' => 'https://www.leetchi.com/c/…',
             'aide' => 'Si vous avez déjà une cagnotte ailleurs, collez son adresse : vos invités y seront envoyés, et le paiement par Solen est masqué.'],
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Message aux invités'],
            ['cle' => 'objectif', 'type' => 'montant', 'label' => 'Objectif',
             'aide' => 'Laissez vide pour ne pas afficher de jauge.'],
            ['cle' => 'montants_suggeres', 'type' => 'texte', 'label' => 'Montants proposés',
             'defaut' => '20, 50, 100, 200', 'aide' => 'Séparés par des virgules.'],
            ['cle' => 'afficher_participants', 'type' => 'booleen', 'label' => 'Afficher la liste des participants'],
            ['cle' => 'afficher_montants', 'type' => 'booleen', 'label' => 'Afficher les montants versés'],
        ],

        'deroule' => [
            ['cle' => 'notifications', 'type' => 'booleen', 'label' => 'Prévenir les invités au changement d’étape',
             'defaut' => true],
            ['cle' => 'preavis_minutes', 'type' => 'nombre', 'label' => 'Prévenir combien de minutes à l’avance',
             'defaut' => 10, 'min' => 0, 'max' => 60],
            ['cle' => 'afficher_horaires', 'type' => 'booleen', 'label' => 'Afficher les horaires prévus',
             'defaut' => true],
            ['cle' => 'afficher_a_venir', 'type' => 'booleen', 'label' => 'Afficher les étapes à venir',
             'defaut' => true],
        ],

        /*
        | Le livret est un PDF déposé par les mariés, lu dans une liseuse :
        | chacun tourne les pages à son rythme, sans que personne n'ait à
        | piloter quoi que ce soit pendant la cérémonie.
        */
        'livret' => [
            ['cle' => 'pdf', 'type' => 'fichier', 'label' => 'Livret de cérémonie (PDF)',
             'formats' => 'pdf', 'poids_max' => 20480,
             'aide' => 'Le livret tel que vous l’auriez imprimé. 20 Mo au plus. Vos invités le liront page par page sur leur téléphone.'],
            ['cle' => 'titre', 'type' => 'texte', 'label' => 'Titre affiché',
             'defaut' => 'Livret de cérémonie'],
            ['cle' => 'telechargeable', 'type' => 'booleen', 'label' => 'Permettre de télécharger le PDF', 'defaut' => true],
        ],

        // Le mode (simple ou borne) est imposé par la formule, il n'est donc
        // pas réglable ici : voir Event::photoboothEnBorne().
        'photobooth' => [
            ['cle' => 'consigne', 'type' => 'texte', 'label' => 'Consigne affichée',
             'defaut' => 'Souriez, c’est pour les mariés !'],
            ['cle' => 'nb_poses', 'type' => 'nombre', 'label' => 'Nombre de poses',
             'defaut' => 1, 'min' => 1, 'max' => 4,
             'aide' => 'Au-delà d’une pose, les clichés sont assemblés en bande façon photomaton.'],
            ['cle' => 'compte_a_rebours', 'type' => 'nombre', 'label' => 'Compte à rebours (secondes)',
             'defaut' => 3, 'min' => 0, 'max' => 10],
            ['cle' => 'cadre', 'type' => 'image', 'label' => 'Cadre personnalisé',
             'aide' => 'Une image PNG superposée à la photo. Si elle a une zone transparente (un visuel avec vos prénoms et la date), la photo s’y loge et le cadre garde son format.'],
            ['cle' => 'filigrane', 'type' => 'booleen', 'label' => 'Inscrire vos prénoms et la date sur la photo',
             'defaut' => true],
            ['cle' => 'message_apres', 'type' => 'texte', 'label' => 'Message après la photo',
             'defaut' => 'Merci ! Votre photo rejoint le mur.'],
        ],

        'plantable' => [
            ['cle' => 'texte_intro', 'type' => 'texte', 'label' => 'Consigne',
             'defaut' => 'Tapez votre nom pour trouver votre place.'],
            ['cle' => 'plan', 'type' => 'image', 'label' => 'Plan de la salle'],
            ['cle' => 'afficher_voisins', 'type' => 'booleen', 'label' => 'Afficher les autres convives de la table'],
        ],

        'jeux' => [
            ['cle' => 'actifs', 'type' => 'choix_multiple', 'label' => 'Jeux proposés',
             'options' => [
                 'quiz'         => 'Quiz des mariés',
                 'qui_deux'     => 'Qui de nous 2 ?',
                 'chasse_photo' => 'Chasse photo',
                 'mots_croises' => 'Mots croisés',
                 'memory'       => 'Memory',
                 'puzzle'       => 'Puzzle',
             ],
             'defaut' => ['qui_deux', 'chasse_photo', 'mots_croises', 'memory', 'puzzle']],
            ['cle' => 'classement', 'type' => 'booleen', 'label' => 'Afficher un classement général', 'defaut' => true],
            ['cle' => 'lot', 'type' => 'texte', 'label' => 'Lot annoncé au gagnant',
             'placeholder' => 'Une bouteille de champagne'],
        ],

        'hommage' => [
            ['cle' => 'titre', 'type' => 'texte', 'label' => 'Titre de la page', 'defaut' => 'Une pensée pour…'],
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Texte d’introduction'],
            ['cle' => 'discret', 'type' => 'booleen', 'label' => 'Ne pas afficher sur la page d’accueil',
             'aide' => 'La page reste accessible par son lien direct.'],
        ],

        'menu' => [
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Introduction'],
            ['cle' => 'collecte_allergies', 'type' => 'booleen', 'label' => 'Demander les allergies aux invités',
             'defaut' => true],
            ['cle' => 'date_limite', 'type' => 'date', 'label' => 'Date limite de réponse'],
        ],

        'pdf' => [
            ['cle' => 'titre', 'type' => 'texte', 'label' => 'Titre du livre',
             'defaut' => 'Notre livre souvenir'],
            ['cle' => 'dedicace', 'type' => 'texte_long', 'label' => 'Dédicace',
             'aide' => 'Quelques lignes en ouverture du livre.'],
            ['cle' => 'couverture', 'type' => 'image', 'label' => 'Photo de couverture'],
            ['cle' => 'inclure_photos', 'type' => 'booleen', 'label' => 'Inclure les photos des invités', 'defaut' => true],
            ['cle' => 'inclure_deroule', 'type' => 'booleen', 'label' => 'Inclure le déroulé de la journée', 'defaut' => true],
            ['cle' => 'format', 'type' => 'choix', 'label' => 'Format',
             'options' => ['a4' => 'A4 — 21 × 29,7 cm', 'a5' => 'A5 — 14,8 × 21 cm'],
             'defaut' => 'a5'],
        ],

        'qr' => [
            ['cle' => 'statistiques', 'type' => 'booleen', 'label' => 'Compter les scans', 'defaut' => true],
            ['cle' => 'logo', 'type' => 'image', 'label' => 'Logo au centre des QR codes'],
        ],

        'ecran' => [
            ['cle' => 'duree_photo', 'type' => 'nombre', 'label' => 'Durée d’affichage d’une photo (secondes)',
             'defaut' => 6, 'min' => 2, 'max' => 30],
            ['cle' => 'afficher_deroule', 'type' => 'booleen', 'label' => 'Afficher l’étape en cours', 'defaut' => true],
            ['cle' => 'afficher_classement', 'type' => 'booleen', 'label' => 'Afficher le classement des jeux'],
            ['cle' => 'afficher_messages', 'type' => 'booleen', 'label' => 'Faire défiler les messages du livre d’or'],
        ],

        'pratique' => [
            ['cle' => 'texte_intro', 'type' => 'texte_long', 'label' => 'Introduction'],
            ['cle' => 'contact_nom', 'type' => 'texte', 'label' => 'Personne à contacter le jour J'],
            ['cle' => 'contact_telephone', 'type' => 'telephone', 'label' => 'Son téléphone'],
            ['cle' => 'contact_email', 'type' => 'email', 'label' => 'Son e-mail'],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Types de blocs de contenu
    |----------------------------------------------------------------------
    | Remplacent le contenu écrit en dur dans les contrôleurs. Le client en
    | ajoute, en retire et en réordonne autant qu'il veut depuis son espace.
    |
    | `titre_depuis` désigne le champ qui sert d'étiquette dans la liste.
    */
    'blocs' => [

        'etape_histoire' => [
            'nom'          => 'Étape de votre histoire',
            'page'         => 'histoire',
            'icone'        => 'fa-heart',
            'titre_depuis' => 'titre',
            'champs'       => [
                ['cle' => 'titre',  'type' => 'texte', 'label' => 'Titre', 'requis' => true,
                 'placeholder' => 'Notre rencontre'],
                ['cle' => 'date',   'type' => 'texte', 'label' => 'Date', 'placeholder' => 'Avril 2016'],
                ['cle' => 'texte',  'type' => 'texte_long', 'label' => 'Récit'],
                ['cle' => 'image',  'type' => 'image', 'label' => 'Photo'],
            ],
        ],

        'hebergement' => [
            'nom'          => 'Hébergement',
            'page'         => 'pratique',
            'icone'        => 'fa-bed',
            'titre_depuis' => 'nom',
            'champs'       => [
                ['cle' => 'nom',        'type' => 'texte', 'label' => 'Nom', 'requis' => true],
                ['cle' => 'adresse',    'type' => 'texte', 'label' => 'Adresse'],
                ['cle' => 'telephone',  'type' => 'telephone', 'label' => 'Téléphone'],
                ['cle' => 'lien',       'type' => 'lien', 'label' => 'Site ou réservation'],
                ['cle' => 'prix',       'type' => 'texte', 'label' => 'Prix indicatif', 'placeholder' => 'à partir de 90 €'],
                ['cle' => 'distance',   'type' => 'texte', 'label' => 'Distance du lieu', 'placeholder' => '10 min en voiture'],
                ['cle' => 'code_promo', 'type' => 'texte', 'label' => 'Code de réduction négocié'],
            ],
        ],

        'transport' => [
            'nom'          => 'Transport',
            'page'         => 'pratique',
            'icone'        => 'fa-car-side',
            'titre_depuis' => 'titre',
            'champs'       => [
                ['cle' => 'titre',       'type' => 'texte', 'label' => 'Titre', 'requis' => true],
                ['cle' => 'mode',        'type' => 'choix', 'label' => 'Mode',
                 'options' => ['voiture' => 'Voiture', 'navette' => 'Navette', 'avion' => 'Avion',
                               'taxi' => 'Taxi', 'train' => 'Train', 'autre' => 'Autre']],
                ['cle' => 'description', 'type' => 'texte_long', 'label' => 'Précisions'],
                ['cle' => 'telephone',   'type' => 'telephone', 'label' => 'Téléphone'],
                ['cle' => 'whatsapp',    'type' => 'telephone', 'label' => 'WhatsApp',
                 'aide' => 'Au format international sans espaces, ex. 596596636362.'],
                ['cle' => 'lien',        'type' => 'lien', 'label' => 'Lien'],
            ],
        ],

        'contact' => [
            'nom'          => 'Contact',
            'page'         => 'pratique',
            'icone'        => 'fa-address-card',
            'titre_depuis' => 'nom',
            'champs'       => [
                ['cle' => 'nom',       'type' => 'texte', 'label' => 'Nom', 'requis' => true],
                ['cle' => 'role',      'type' => 'texte', 'label' => 'Rôle', 'placeholder' => 'Témoin, organisatrice…'],
                ['cle' => 'telephone', 'type' => 'telephone', 'label' => 'Téléphone'],
                ['cle' => 'whatsapp',  'type' => 'telephone', 'label' => 'WhatsApp',
                 'aide' => 'Au format international sans espaces, ex. 596696388072.'],
                ['cle' => 'email',     'type' => 'email', 'label' => 'E-mail'],
            ],
        ],

        'defunt' => [
            'nom'          => 'Personne à qui rendre hommage',
            'page'         => 'hommage',
            'icone'        => 'fa-dove',
            'titre_depuis' => 'nom',
            'champs'       => [
                ['cle' => 'nom',     'type' => 'texte', 'label' => 'Nom', 'requis' => true],
                ['cle' => 'dates',   'type' => 'texte', 'label' => 'Dates', 'placeholder' => '1941 – 2017'],
                ['cle' => 'photo',   'type' => 'image', 'label' => 'Photo'],
                ['cle' => 'message', 'type' => 'texte_long', 'label' => 'Message'],
            ],
        ],

        'plat' => [
            'nom'          => 'Plat du menu',
            'page'         => 'menu',
            'icone'        => 'fa-utensils',
            'titre_depuis' => 'nom',
            'champs'       => [
                ['cle' => 'categorie',   'type' => 'choix', 'label' => 'Moment', 'requis' => true,
                 'options' => ['cocktail' => 'Cocktail', 'entree' => 'Entrée', 'plat' => 'Plat',
                               'fromage' => 'Fromage', 'dessert' => 'Dessert', 'boisson' => 'Boisson']],
                ['cle' => 'nom',         'type' => 'texte', 'label' => 'Nom du plat', 'requis' => true],
                ['cle' => 'description', 'type' => 'texte_long', 'label' => 'Description'],
                ['cle' => 'allergenes',  'type' => 'texte', 'label' => 'Allergènes',
                 'placeholder' => 'gluten, fruits à coque'],
            ],
        ],

        'texte' => [
            'nom'          => 'Bloc de texte',
            'page'         => null,   // utilisable sur n'importe quelle page
            'icone'        => 'fa-align-left',
            'titre_depuis' => 'titre',
            'champs'       => [
                ['cle' => 'titre',   'type' => 'texte', 'label' => 'Titre'],
                ['cle' => 'contenu', 'type' => 'texte_long', 'label' => 'Contenu', 'requis' => true],
            ],
        ],

        'info' => [
            'nom'          => 'Information pratique',
            'page'         => null,
            'icone'        => 'fa-circle-info',
            'titre_depuis' => 'titre',
            'champs'       => [
                ['cle' => 'icone',   'type' => 'texte', 'label' => 'Icône', 'placeholder' => 'fa-parking',
                 'aide' => 'Nom d’une icône Font Awesome.'],
                ['cle' => 'titre',   'type' => 'texte', 'label' => 'Titre', 'requis' => true],
                ['cle' => 'contenu', 'type' => 'texte_long', 'label' => 'Détail'],
            ],
        ],

        'faq' => [
            'nom'          => 'Question fréquente',
            'page'         => null,
            'icone'        => 'fa-circle-question',
            'titre_depuis' => 'question',
            'champs'       => [
                ['cle' => 'question', 'type' => 'texte', 'label' => 'Question', 'requis' => true],
                ['cle' => 'reponse',  'type' => 'texte_long', 'label' => 'Réponse', 'requis' => true],
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Trames de déroulé
    |----------------------------------------------------------------------
    | Pré-remplissage proposé par l'assistant selon le type de cérémonie
    | choisi. Ce ne sont pas des modèles imposés : le couple réordonne,
    | renomme et supprime ce qu'il veut ensuite.
    */
    'deroules' => [

        'civil' => [
            ['titre' => 'Accueil des invités',            'icone' => 'fa-hand-sparkles'],
            ['titre' => 'Entrée des mariés',              'icone' => 'fa-shoe-prints'],
            ['titre' => 'Mot de l’officier d’état civil', 'icone' => 'fa-microphone'],
            ['titre' => 'Lecture des articles du Code civil', 'icone' => 'fa-scale-balanced'],
            ['titre' => 'Échange des consentements',      'icone' => 'fa-heart'],
            ['titre' => 'Échange des alliances',          'icone' => 'fa-ring'],
            ['titre' => 'Signature des registres',        'icone' => 'fa-pen-nib'],
            ['titre' => 'Sortie des mariés',              'icone' => 'fa-door-open'],
        ],

        'catholique' => [
            ['titre' => 'Accueil des invités',        'icone' => 'fa-hand-sparkles'],
            ['titre' => 'Entrée des mariés',          'icone' => 'fa-shoe-prints'],
            ['titre' => 'Rite d’ouverture',           'icone' => 'fa-cross'],
            ['titre' => 'Première lecture',           'icone' => 'fa-book-open'],
            ['titre' => 'Psaume',                     'icone' => 'fa-music'],
            ['titre' => 'Évangile',                   'icone' => 'fa-book-bible'],
            ['titre' => 'Homélie',                    'icone' => 'fa-microphone'],
            ['titre' => 'Échange des consentements',  'icone' => 'fa-heart'],
            ['titre' => 'Bénédiction des alliances',  'icone' => 'fa-ring'],
            ['titre' => 'Prière universelle',         'icone' => 'fa-hands-praying'],
            ['titre' => 'Offertoire',                 'icone' => 'fa-basket-shopping'],
            ['titre' => 'Notre Père',                 'icone' => 'fa-hands-praying'],
            ['titre' => 'Communion',                  'icone' => 'fa-wine-glass'],
            ['titre' => 'Signature des registres',    'icone' => 'fa-pen-nib'],
            ['titre' => 'Bénédiction finale',         'icone' => 'fa-dove'],
            ['titre' => 'Sortie des mariés',          'icone' => 'fa-door-open'],
        ],

        'laique' => [
            ['titre' => 'Accueil musical',            'icone' => 'fa-music'],
            ['titre' => 'Entrée des mariés',          'icone' => 'fa-shoe-prints'],
            ['titre' => 'Mot de l’officiant',         'icone' => 'fa-microphone'],
            ['titre' => 'Discours des proches',       'icone' => 'fa-users'],
            ['titre' => 'Rituel symbolique',          'icone' => 'fa-candle-holder'],
            ['titre' => 'Vœux des mariés',            'icone' => 'fa-heart'],
            ['titre' => 'Échange des alliances',      'icone' => 'fa-ring'],
            ['titre' => 'Signature symbolique',       'icone' => 'fa-pen-nib'],
            ['titre' => 'Sortie des mariés',          'icone' => 'fa-door-open'],
        ],

        'evangelique' => [
            ['titre' => 'Temps de louange',           'icone' => 'fa-music'],
            ['titre' => 'Entrée des mariés',          'icone' => 'fa-shoe-prints'],
            ['titre' => 'Prière d’ouverture',         'icone' => 'fa-hands-praying'],
            ['titre' => 'Lecture biblique',           'icone' => 'fa-book-bible'],
            ['titre' => 'Prédication',                'icone' => 'fa-microphone'],
            ['titre' => 'Échange des vœux',           'icone' => 'fa-heart'],
            ['titre' => 'Échange des alliances',      'icone' => 'fa-ring'],
            ['titre' => 'Prière de bénédiction',      'icone' => 'fa-dove'],
            ['titre' => 'Cantique final',             'icone' => 'fa-music'],
            ['titre' => 'Sortie des mariés',          'icone' => 'fa-door-open'],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Parties proposées à la création
    |----------------------------------------------------------------------
    */
    'parties' => [
        'mairie'      => ['nom' => 'Mairie',              'icone' => 'fa-gavel',              'ceremonie' => true],
        'ceremonie'   => ['nom' => 'Cérémonie',           'icone' => 'fa-church',             'ceremonie' => true],
        'vin-honneur' => ['nom' => 'Vin d’honneur',       'icone' => 'fa-champagne-glasses',  'ceremonie' => false],
        'diner'       => ['nom' => 'Dîner',               'icone' => 'fa-utensils',           'ceremonie' => false],
        'soiree'      => ['nom' => 'Soirée',              'icone' => 'fa-music',              'ceremonie' => false],
        'brunch'      => ['nom' => 'Brunch du lendemain', 'icone' => 'fa-mug-saucer',         'ceremonie' => false],
    ],

    /*
    |----------------------------------------------------------------------
    | Navigation du site invité
    |----------------------------------------------------------------------
    | Quelle tuile afficher sur la page d'accueil pour quel module. Un module
    | désactivé disparaît du menu sans qu'on touche à la vue.
    |
    | 'ordre' pilote la position ; 'route' est un nom de route, le slug du
    | mariage étant injecté automatiquement par le middleware.
    */
    'navigation' => [
        'histoire'   => ['route' => 'histoire',          'groupe' => 'infos',    'nom' => 'Notre histoire',   'icone' => 'fa-book-open',        'desc' => 'Notre rencontre, en quelques dates.',      'ordre' => 10],
        'livret'     => ['route' => 'livret',            'groupe' => 'jour',     'nom' => 'Livret',           'icone' => 'fa-book-bible',       'desc' => 'Le livret de la cérémonie, à feuilleter.', 'ordre' => 35],
        'rsvp'       => ['route' => 'rsvp',              'groupe' => 'infos',    'nom' => 'Répondre',         'icone' => 'fa-envelope-open-text', 'desc' => 'Confirmez votre présence.',               'ordre' => 5],
        'pratique'   => ['route' => 'details.pratiques', 'groupe' => 'infos',    'nom' => 'Infos pratiques',  'icone' => 'fa-map-location-dot', 'desc' => 'Hébergement, transport, contacts.',        'ordre' => 40],
        'menu'       => ['route' => 'menu',              'groupe' => 'infos',    'nom' => 'Menu',             'icone' => 'fa-utensils',         'desc' => 'Le repas, et vos allergies.',              'ordre' => 50],
        'cagnotte'   => ['route' => 'urne.index',        'groupe' => 'partager', 'nom' => 'Liste de mariage', 'icone' => 'fa-gift',             'desc' => 'Participer à notre projet.',               'ordre' => 60],
        'mur'        => ['route' => 'galerie.index',     'groupe' => 'partager', 'nom' => 'Galerie',          'icone' => 'fa-images',           'desc' => 'Vos photos de la journée.',                'ordre' => 70],
        'photobooth' => ['route' => 'photobooth',        'groupe' => 'partager', 'nom' => 'Photobooth',       'icone' => 'fa-camera-retro',     'desc' => 'Prenez la pose.',                          'ordre' => 75],
        'livredor'   => ['route' => 'livreOr.index',     'groupe' => 'partager', 'nom' => 'Livre d’or',       'icone' => 'fa-feather-pointed',  'desc' => 'Laissez-nous un mot.',                     'ordre' => 80],
        'hommage'    => ['route' => 'pensee.pour',       'groupe' => 'infos',    'nom' => 'Une pensée pour',  'icone' => 'fa-dove',             'desc' => 'Pour celles et ceux qui nous manquent.',   'ordre' => 200],
    ],

    /*
    | Les groupes du menu, dans l'ordre d'affichage.
    */
    /*
    | Questions du « Qui de nous 2 » créées à l'achat. Les mariés n'ont qu'à
    | désigner lequel des deux, d'un geste, pour qu'elles soient posées.
    */
    'questions_defaut' => [
        'Qui a fait le premier pas ?',
        'Qui a dit « je t’aime » en premier ?',
        'Qui est toujours en retard ?',
        'Qui cuisine le mieux ?',
        'Qui est le plus dépensier ?',
        'Qui s’endort devant les films ?',
        'Qui est de mauvaise humeur au réveil ?',
        'Qui est le plus romantique ?',
        'Qui conduit le mieux ?',
        'Qui a fait la demande ?',
        'Qui passe le plus de temps dans la salle de bain ?',
        'Qui danse le mieux ?',
    ],

    /*
    | Mots croisés par défaut : le vocabulaire du mariage. Les mariés peuvent
    | ajouter les leurs (prénoms, lieux, souvenirs) depuis l'écran Jeux.
    */
    'mots_croises_defaut' => [
        'ALLIANCE : Elle se porte à l’annulaire',
        'BOUQUET : On le lance aux célibataires',
        'TEMOIN : Il signe le registre',
        'VOEUX : On les échange devant tous',
        'GATEAU : On le coupe à deux',
        'DRAGEES : Douceurs offertes aux invités',
        'MAIRIE : Le passage obligé avant la fête',
        'VOILE : Il couvre la mariée',
        'CHAMPAGNE : On trinque avec',
        'VALSE : Danse d’ouverture traditionnelle',
        'BAL : On l’ouvre ensemble',
        'LUNE : Elle est de miel après la noce',
    ],

    /*
    | Missions proposées à la chasse photo tant que les mariés n'ont pas
    | écrit les leurs. Volontairement sans prénom ni lieu.
    */
    'missions_defaut' => [
        'Un selfie avec les mariés',
        'L’objet le plus ancien de la salle',
        'Un selfie avec quelqu’un que vous venez de rencontrer',
        'Le plus beau fou rire de la journée',
        'Le détail de décoration que vous préférez',
        'Les alliances',
    ],

    'navigation_groupes' => [
        'jour'     => 'Le jour J',
        'infos'    => 'Infos',
        'partager' => 'Partager',
        'jouer'    => 'Jouer',
    ],

    /*
    |----------------------------------------------------------------------
    | Jeux
    |----------------------------------------------------------------------
    | Affichés seulement si le module « jeux » est actif ET si le jeu est
    | coché dans ses réglages.
    */
    'jeux' => [
        'quiz'         => ['route' => 'jeux.quiz',        'nom' => 'Quiz des mariés', 'icone' => 'fa-circle-question', 'desc' => 'Trois propositions, une seule bonne : qui connaît le mieux les mariés ?'],
        'qui_deux'     => ['route' => 'jeux.quiDeux',     'nom' => 'Qui de nous 2 ?', 'icone' => 'fa-people-arrows',  'desc' => 'Qui de nous deux… ? À vous de deviner.'],
        'mots_croises' => ['route' => 'jeux.motsCroises', 'nom' => 'Mots croisés',    'icone' => 'fa-table-cells',    'desc' => 'Une grille sur le thème du mariage.'],
        'memory'       => ['route' => 'jeux.memory',      'nom' => 'Memory',          'icone' => 'fa-clone',          'desc' => 'Retrouvez les paires, le plus vite possible.'],
        'puzzle'       => ['route' => 'jeux.puzzle',      'nom' => 'Puzzle',          'icone' => 'fa-puzzle-piece',   'desc' => 'Reconstituez la photo des mariés.',
                           // Réservé aux formules hautes, quelle que soit la composition du module.
                           'formules' => ['celebration', 'signature']],
        'chasse_photo' => ['route' => 'jeux.chassePhoto', 'nom' => 'Chasse photo',    'icone' => 'fa-camera',         'desc' => 'Des missions photo pour toute la journée.'],
    ],

    /*
    |----------------------------------------------------------------------
    | Composition des formules
    |----------------------------------------------------------------------
    | Quels modules chaque formule débloque. La liste est CUMULATIVE :
    | « celebration » contient tout « essentiel », et ainsi de suite.
    | C'est ce tableau que lit le seeder pour remplir plan_module.
    */
    'formule_modules' => [

        'essentiel' => [
            'site', 'compte', 'histoire', 'pratique', 'livredor', 'mur', 'photobooth',
            'menu', 'cagnotte', 'qr', 'rsvp',
        ],

        'celebration' => [
            'livret', 'jeux', 'hommage',
        ],

        // Signature débride le photobooth en mode borne, lève toutes les
        // limites, et ouvre le livre souvenir imprimable.
        'signature' => ['pdf'],
    ],

    /*
    |----------------------------------------------------------------------
    | Pages du site invité
    |----------------------------------------------------------------------
    | Quels types de blocs sont proposés sur quelle page.
    */
    'pages' => [
        'accueil'   => ['nom' => 'Accueil',            'blocs' => ['texte', 'info']],
        'histoire'  => ['nom' => 'Notre histoire',     'blocs' => ['etape_histoire', 'texte']],
        'ceremonie' => ['nom' => 'Cérémonie',          'blocs' => ['texte', 'info']],
        'pratique'  => ['nom' => 'Infos pratiques',    'blocs' => ['hebergement', 'transport', 'contact', 'info', 'faq', 'texte']],
        'menu'      => ['nom' => 'Menu',               'blocs' => ['plat', 'texte']],
        'hommage'   => ['nom' => 'Une pensée pour',    'blocs' => ['defunt', 'texte']],
    ],
];
