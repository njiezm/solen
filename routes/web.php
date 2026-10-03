<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MotDePasseController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DirectController;
use App\Http\Controllers\Console\ConsoleController;
use App\Http\Controllers\Console\MariageController;
use App\Http\Controllers\Console\ThemeController;
use App\Http\Controllers\Console\FacturationController;
use App\Http\Controllers\Console\DocumentsController;
use App\Http\Controllers\Espace\EspaceController;
use App\Http\Controllers\Espace\JeuxController as EspaceJeuxController;
use App\Http\Controllers\Espace\ProgrammeController;
use App\Http\Controllers\Espace\EquipeController;
use App\Http\Controllers\Espace\InvitesController;
use App\Http\Controllers\Espace\FormuleController;
use App\Http\Controllers\Espace\LivreSouvenirController;
use App\Http\Controllers\Espace\ModulesController;
use App\Http\Controllers\Espace\PaiementController;
use App\Http\Controllers\Espace\PagesController;
use App\Http\Controllers\GalerieController;
use App\Http\Controllers\JeuxController;
use App\Http\Controllers\LivreOrController;
use App\Http\Controllers\LivretController;
use App\Http\Controllers\MarieController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PartieController;
use App\Http\Controllers\PhotoboothController;
use App\Http\Controllers\PratiqueController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\UrneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vitrine Solen
|--------------------------------------------------------------------------
*/
Route::view('/', 'solen.landing')->name('solen.landing');
Route::redirect('/solen', '/');

// Documents légaux : obligatoires pour vendre, partagés avec les PDF transmis.
foreach ([
    'mentions-legales' => ['mentions',        'Mentions légales',             'legal.mentions'],
    'cgv'              => ['cgv',             'Conditions générales de vente', 'legal.cgv'],
    'confidentialite'  => ['confidentialite', 'Politique de confidentialité',  'legal.confidentialite'],
] as $chemin => [$contenu, $titre, $nom]) {
    Route::get("/{$chemin}", fn () => view('legal.page', ['contenu' => $contenu, 'titre' => $titre]))->name($nom);
}

/*
|--------------------------------------------------------------------------
| Achat d'une formule
|--------------------------------------------------------------------------
| Le mariage n'est créé qu'une fois le paiement encaissé — sauf formule
| gratuite, qui saute naturellement l'étape Stripe.
*/
Route::get('/commander/code-promo', [CommandeController::class, 'verifierCode'])->middleware('throttle:20,1')->name('commande.code');
Route::get('/commander/{plan}',  [CommandeController::class, 'formulaire'])->name('commander');
Route::post('/commander',        [CommandeController::class, 'payer'])->name('commande.payer');
Route::get('/commande/{uuid}/merci',   [CommandeController::class, 'merci'])->name('commande.merci');
Route::get('/commande/{uuid}/annulee', [CommandeController::class, 'annulee'])->name('commande.annulee');

/*
|--------------------------------------------------------------------------
| Authentification des organisateurs
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion',  [LoginController::class, 'show'])->name('auth.login');

    // La console a sa propre porte, réservée à l'équipe Solen.
    Route::get('/admin/connexion',  [LoginController::class, 'showAdmin'])->name('admin.connexion');
    Route::post('/admin/connexion', [LoginController::class, 'loginAdmin']);
    Route::post('/connexion', [LoginController::class, 'login']);

    // Mot de passe oublié : sans ce parcours, un couple qui perd le mot de
    // passe reçu à l'achat n'a d'autre recours que d'écrire à Solen.
    Route::get('/mot-de-passe',  [MotDePasseController::class, 'demande'])->name('mot-de-passe.demande');
    Route::post('/mot-de-passe', [MotDePasseController::class, 'envoyer'])->name('mot-de-passe.envoyer');
    Route::get('/mot-de-passe/{token}',  [MotDePasseController::class, 'formulaire'])->name('password.reset');
    Route::post('/mot-de-passe/nouveau', [MotDePasseController::class, 'reinitialiser'])->name('mot-de-passe.reinitialiser');
});

// Invitation : remise des clés aux mariés, ou accès d'un témoin.
// Lien signé, à usage unique (voir App\Solen\Invitations).
Route::middleware(['signed', 'throttle:20,1'])->group(function () {
    Route::get('/invitation/{user}/{slug}/{cle}', [InvitationController::class, 'formulaire'])->whereNumber('user')->name('invitation');
    Route::post('/invitation/{user}/{slug}/{cle}', [InvitationController::class, 'accepter'])->whereNumber('user');
});

Route::get('/admin', fn () => redirect()->route(auth()->user()?->estSuperAdmin() ? 'console.index' : 'admin.connexion'));

Route::post('/deconnexion', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Hors mariage
|--------------------------------------------------------------------------
| Un QR est identifié par son uuid, quelle que soit l'adresse par laquelle
| on l'atteint. Stripe, lui, n'a ni cookie ni contexte de mariage : c'est
| la métadonnée du paiement qui porte l'information.
*/
Route::get('/qr/track/{uuid}', [QrCodeController::class, 'track'])->name('qr.track');

// Téléchargement d'un document transmis par WhatsApp : lien signé et
// temporaire, sans connexion. Toute modification du lien l'invalide.
Route::middleware('signed')->group(function () {
    Route::get('/documents/{uuid}', [DocumentsController::class, 'commercialPublic'])->name('documents.commercial');
    Route::get('/documents/mariage/{slug}/{piece}', [DocumentsController::class, 'kitPublic'])->name('documents.kit');
});

Route::post('/webhook/stripe', [UrneController::class, 'stripeWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Console Solen — /console/…
|--------------------------------------------------------------------------
| Hors locataire : c'est ici qu'on voit tous les mariages à la fois.
| Réservée à l'équipe Solen.
*/
Route::prefix('console')
    ->middleware(['auth', 'super-admin'])
    ->name('console.')
    ->group(function () {
        Route::get('/', [ConsoleController::class, 'index'])->name('index');

        // Mariages
        Route::get('/mariages/nouveau',  [MariageController::class, 'creer'])->name('mariage.creer');
        Route::post('/mariages',         [MariageController::class, 'stocker'])->name('mariage.stocker');
        Route::get('/mariages/slug',     [MariageController::class, 'slug'])->name('mariage.slug');
        Route::get('/mariages/{slug}',   [ConsoleController::class, 'montrer'])->name('mariage');
        Route::post('/mariages/{slug}',  [MariageController::class, 'maj'])->name('mariage.maj');
        Route::post('/mariages/{slug}/accompagnement', [MariageController::class, 'accompagnement'])->name('mariage.accompagnement');
        Route::post('/mariages/{slug}/remise', [MariageController::class, 'remettre'])->name('mariage.remise');
        Route::delete('/mariages/{slug}', [MariageController::class, 'supprimer'])->name('mariage.supprimer');

        // Facturation Solen by NJIEZM.FR : devis, factures, avoirs.
        Route::prefix('facturation')->name('facturation')->group(function () {
            Route::get('/', [FacturationController::class, 'index']);
            Route::get('/codes-promo', [\App\Http\Controllers\Console\CodesPromoController::class, 'index'])->name('.codes');
            Route::post('/codes-promo', [\App\Http\Controllers\Console\CodesPromoController::class, 'enregistrer'])->name('.codes.enregistrer');
            Route::post('/codes-promo/{code}/basculer', [\App\Http\Controllers\Console\CodesPromoController::class, 'basculer'])->whereNumber('code')->name('.codes.basculer');
            Route::get('/nouveau', [FacturationController::class, 'creer'])->name('.creer');
            Route::post('/', [FacturationController::class, 'stocker'])->name('.stocker');
            Route::get('/{document}', [FacturationController::class, 'montrer'])->whereNumber('document')->name('.montrer');
            Route::get('/{document}/modifier', [FacturationController::class, 'editer'])->whereNumber('document')->name('.editer');
            Route::put('/{document}', [FacturationController::class, 'maj'])->whereNumber('document')->name('.maj');
            Route::delete('/{document}', [FacturationController::class, 'supprimer'])->whereNumber('document')->name('.supprimer');
            Route::post('/{document}/emettre', [FacturationController::class, 'emettre'])->whereNumber('document')->name('.emettre');
            Route::post('/{document}/payer', [FacturationController::class, 'payer'])->whereNumber('document')->name('.payer');
            Route::post('/{document}/avoir', [FacturationController::class, 'avoir'])->whereNumber('document')->name('.avoir');
            Route::post('/{document}/convertir', [FacturationController::class, 'convertir'])->whereNumber('document')->name('.convertir');
            Route::post('/{document}/acompte', [FacturationController::class, 'acompte'])->whereNumber('document')->name('.acompte');
            Route::post('/{document}/solde', [FacturationController::class, 'solde'])->whereNumber('document')->name('.solde');
            Route::post('/{document}/devis', [FacturationController::class, 'statutDevis'])->whereNumber('document')->name('.devis');
            Route::get('/{document}/pdf', [FacturationController::class, 'pdf'])->whereNumber('document')->name('.pdf');
            Route::post('/{document}/envoyer', [FacturationController::class, 'envoyer'])->whereNumber('document')->name('.envoyer');
            Route::get('/{document}/whatsapp', [FacturationController::class, 'whatsapp'])->whereNumber('document')->name('.whatsapp');
        });

        // Documents à transmettre à un mariage : kit, QR, légaux.
        Route::get('/mariages/{slug}/documents/{piece}', [DocumentsController::class, 'voir'])->name('mariage.document');
        Route::post('/mariages/{slug}/documents/envoyer', [DocumentsController::class, 'envoyer'])->name('mariage.documents.envoyer');
        Route::post('/mariages/{slug}/documents/whatsapp', [DocumentsController::class, 'whatsapp'])->name('mariage.documents.whatsapp');

        // Thèmes
        Route::get('/themes',                 [ThemeController::class, 'index'])->name('themes');
        Route::get('/themes/nouveau',         [ThemeController::class, 'creer'])->name('theme.creer');
        Route::post('/themes',                [ThemeController::class, 'enregistrer'])->name('theme.stocker');
        Route::get('/themes/{theme}',         [ThemeController::class, 'editer'])->name('theme.editer');
        Route::post('/themes/{theme}',        [ThemeController::class, 'enregistrer'])->name('theme.maj');
        Route::delete('/themes/{theme}',      [ThemeController::class, 'supprimer'])->name('theme.supprimer');
    });

/*
|--------------------------------------------------------------------------
| Site invité — /mariage/{slug}/…
|--------------------------------------------------------------------------
| Adressage par chemin plutôt que par sous-domaine : aucun DNS joker, aucun
| certificat SSL par client, aucun vhost à créer à la vente. Le middleware
| `event` résout le mariage, cloisonne les requêtes, retire le paramètre des
| signatures de contrôleur, et le réinjecte dans route() — les vues écrivent
| donc route('galerie.index') sans jamais manipuler le slug.
*/
Route::prefix('mariage/{event}')->middleware(['event', 'acces'])->group(function () {

    // Le code d'accès, quand les mariés en ont choisi un.
    Route::post('/acces', function (\Illuminate\Http\Request $request, \App\Solen\CurrentEvent $courant) {
        $event = $courant->get();
        $attendu = mb_strtolower(trim((string) $event->reglage('site', 'mot_de_passe')));

        if ($attendu === '' || hash_equals($attendu, mb_strtolower(trim((string) $request->input('code'))))) {
            $request->session()->put("acces.{$event->id}", true);

            return redirect()->intended(route('landing'));
        }

        return back()->withErrors(['code' => 'Ce code n’est pas le bon. Il figure sur votre faire-part.']);
    })->middleware('throttle:10,1')->name('acces.verifier');

    Route::get('/', fn () => view('landing'))->name('landing');
    Route::get('/accueil', fn () => view('home'))->name('home');

    // RSVP : chaque foyer répond depuis son lien personnel.
    Route::get('/rsvp', [RsvpController::class, 'index'])->name('rsvp');
    Route::post('/rsvp/code', [RsvpController::class, 'code'])->middleware('throttle:15,1')->name('rsvp.code');
    Route::post('/rsvp', [RsvpController::class, 'repondreLibre'])->middleware('throttle:10,1')->name('rsvp.libre');
    Route::get('/rsvp/{code}', [RsvpController::class, 'personnel'])->name('rsvp.personnel');
    Route::post('/rsvp/{code}', [RsvpController::class, 'repondre'])->middleware('throttle:20,1')->name('rsvp.repondre');

    // Le livret de cérémonie, à feuilleter.
    Route::get('/livret', LivretController::class)->name('livret');

    // Sondage du déroulé en direct. Le module est archivé ; la route reste
    // pour ne pas casser un ancien lien, et répond « inactif ».
    Route::get('/direct/{cle?}', DirectController::class)->name('direct');

    // Pages de contenu, pilotées par les blocs éditables
    Route::get('/notre-histoire', [PageController::class, 'histoire'])->name('histoire');
    Route::get('/menu', [PageController::class, 'menu'])->name('menu');
    Route::get('/hommage', [PageController::class, 'penseePour'])->name('pensee.pour');

    // Un moment de la journée : mairie, cérémonie, vin d'honneur, soirée…
    // Une seule vue, autant de pages que de parties déclarées.
    Route::get('/moment/{cle}', PartieController::class)->name('partie');
    Route::get('/ceremonie', fn () => app(PartieController::class)('ceremonie', app(\App\Solen\CurrentEvent::class)))->name('ceremonie');
    Route::get('/mairie',    fn () => app(PartieController::class)('mairie',    app(\App\Solen\CurrentEvent::class)))->name('mairie');
    Route::get('/infos-pratiques', [PratiqueController::class, 'index'])->name('details.pratiques');

    // Livre d'or
    Route::get('/livre-or', [LivreOrController::class, 'index'])->name('livreOr.index');
    Route::post('/livre-or', [LivreOrController::class, 'store'])->name('livreOr.store');

    // Galerie
    Route::get('/galerie', [GalerieController::class, 'index'])->name('galerie.index');
    Route::post('/galerie', [GalerieController::class, 'store'])->name('galerie.store');

    // Photobooth
    Route::get('/photobooth',  [PhotoboothController::class, 'index'])->name('photobooth');
    Route::post('/photobooth', [PhotoboothController::class, 'stocker'])->name('photobooth.stocker');

    // Urne
    Route::get('/urne', [UrneController::class, 'index'])->name('urne.index');
    Route::post('/urne/payer', [UrneController::class, 'payer'])->name('urne.payer');
    Route::get('/urne/merci', [UrneController::class, 'success'])->name('urne.success');
    Route::get('/urne/annule', [UrneController::class, 'cancel'])->name('urne.cancel');

    // Jeux : ouverts d'office dès que les mariés les ont cochés.
    Route::get('/jeux/quiz', [JeuxController::class, 'quiz'])->name('jeux.quiz');
    Route::post('/jeux/quiz', [JeuxController::class, 'submitQuiz'])->name('jeux.submitQuiz');

    Route::get('/jeux/qui-de-nous-2', [JeuxController::class, 'quiDeux'])->name('jeux.quiDeux');
    Route::post('/jeux/qui-de-nous-2', [JeuxController::class, 'submitQuiDeux'])->name('jeux.submitQuiDeux');

    Route::get('/jeux/chasse-photo', [JeuxController::class, 'chassePhoto'])->name('jeux.chassePhoto');
    Route::post('/jeux/chasse-photo', [JeuxController::class, 'submitChassePhoto'])->name('jeux.submitChassePhoto');

    Route::get('/jeux/mots-croises', [JeuxController::class, 'motsCroises'])->name('jeux.motsCroises');
    Route::post('/jeux/mots-croises', [JeuxController::class, 'submitMotsCroises'])->name('jeux.submitMotsCroises');

    Route::get('/jeux/memory', [JeuxController::class, 'memory'])->name('jeux.memory');
    Route::post('/jeux/memory', [JeuxController::class, 'submitMemory'])->name('jeux.submitMemory');

    Route::get('/jeux/puzzle', [JeuxController::class, 'puzzle'])->name('jeux.puzzle');
    Route::post('/jeux/puzzle', [JeuxController::class, 'submitPuzzle'])->name('jeux.submitPuzzle');

    Route::get('/jeux/classement', [JeuxController::class, 'classement'])->name('jeux.classement');
    Route::get('/jeux/{jeu}/resultat', [JeuxController::class, 'afficherResultat'])
        ->whereIn('jeu', ['quiz', 'qui_deux', 'mots_croises', 'memory', 'puzzle', 'chasse_photo'])
        ->name('jeux.resultat');
});

/*
|--------------------------------------------------------------------------
| Espace organisateur — /mariage/{slug}/espace/…
|--------------------------------------------------------------------------
*/
Route::prefix('mariage/{event}/espace')
    ->middleware(['event', 'organisateur'])
    ->name('espace.')
    ->group(function () {
        Route::get('/', [EspaceController::class, 'index'])->name('index');
        Route::get('/informations',  [EspaceController::class, 'informations'])->name('informations');
        Route::post('/informations', [EspaceController::class, 'enregistrerInformations'])->name('informations.enregistrer');

        // Modules : activation et réglages générés depuis le schéma.
        Route::get('/modules', [ModulesController::class, 'index'])->name('modules');
        Route::post('/modules/{cle}/basculer', [ModulesController::class, 'basculer'])->name('modules.basculer');
        Route::get('/modules/{cle}',  [ModulesController::class, 'editer'])->name('modules.editer');
        Route::post('/modules/{cle}', [ModulesController::class, 'enregistrer'])->name('modules.enregistrer');

        // Monter de formule : on ne paie que la différence.
        Route::get('/formule', [FormuleController::class, 'index'])->name('formule');
        Route::post('/formule/{plan}', [FormuleController::class, 'payer'])->name('formule.payer');

        // Invités et RSVP.
        Route::prefix('invites')->name('invites')->group(function () {
            Route::get('/', [InvitesController::class, 'index']);
            Route::post('/', [InvitesController::class, 'ajouter'])->name('.ajouter');
            Route::post('/importer', [InvitesController::class, 'importer'])->name('.importer');
            Route::get('/export', [InvitesController::class, 'exporter'])->name('.exporter');
            Route::put('/{id}', [InvitesController::class, 'maj'])->whereNumber('id')->name('.maj');
            Route::delete('/{id}', [InvitesController::class, 'supprimer'])->whereNumber('id')->name('.supprimer');
            Route::get('/{id}/whatsapp', [InvitesController::class, 'whatsapp'])->whereNumber('id')->name('.whatsapp');
        });

        // Équipe : les mariés et les personnes qui les aident.
        Route::get('/equipe', [EquipeController::class, 'index'])->name('equipe');
        Route::post('/equipe', [EquipeController::class, 'inviter'])->name('equipe.inviter');
        Route::delete('/equipe/{id}', [EquipeController::class, 'retirer'])->whereNumber('id')->name('equipe.retirer');

        // Programme : les moments de la journée et leur déroulé.
        Route::get('/programme', [ProgrammeController::class, 'index'])->name('programme');
        Route::prefix('programme')->name('programme.')->group(function () {
            Route::post('/moments', [ProgrammeController::class, 'ajouterMoment'])->name('moment.ajouter');
            Route::post('/moments/{id}', [ProgrammeController::class, 'majMoment'])->whereNumber('id')->name('moment.maj');
            Route::post('/moments/{id}/{sens}', [ProgrammeController::class, 'deplacerMoment'])->whereNumber('id')->whereIn('sens', ['monter', 'descendre'])->name('moment.deplacer');
            Route::delete('/moments/{id}', [ProgrammeController::class, 'supprimerMoment'])->whereNumber('id')->name('moment.supprimer');
            Route::post('/moments/{id}/etapes', [ProgrammeController::class, 'ajouterEtape'])->whereNumber('id')->name('etape.ajouter');
            Route::post('/etapes/{id}', [ProgrammeController::class, 'majEtape'])->whereNumber('id')->name('etape.maj');
            Route::post('/etapes/{id}/{sens}', [ProgrammeController::class, 'deplacerEtape'])->whereNumber('id')->whereIn('sens', ['monter', 'descendre'])->name('etape.deplacer');
            Route::delete('/etapes/{id}', [ProgrammeController::class, 'supprimerEtape'])->whereNumber('id')->name('etape.supprimer');
        });

        // Jeux : activation, contenu, validation, classement — au même endroit.
        Route::prefix('jeux')->name('jeux.')->group(function () {
            Route::get('/', [EspaceJeuxController::class, 'index'])->name('index');
            Route::post('/reglages', [EspaceJeuxController::class, 'reglages'])->name('reglages');
            Route::post('/basculer/{cle}', [EspaceJeuxController::class, 'basculer'])->name('basculer');

            Route::post('/quiz', [EspaceJeuxController::class, 'enregistrerQuiz'])->name('quiz');

            Route::post('/questions', [EspaceJeuxController::class, 'ajouterQuestion'])->name('questions.ajouter');
            Route::post('/questions/{id}/reponse', [EspaceJeuxController::class, 'repondre'])->whereNumber('id')->name('questions.repondre');
            Route::post('/questions/{id}/basculer', [EspaceJeuxController::class, 'basculerQuestion'])->whereNumber('id')->name('questions.basculer');
            Route::delete('/questions/{id}', [EspaceJeuxController::class, 'supprimerQuestion'])->whereNumber('id')->name('questions.supprimer');

            Route::post('/mots', [EspaceJeuxController::class, 'mots'])->name('mots');
            Route::post('/missions', [EspaceJeuxController::class, 'missions'])->name('missions');
            Route::post('/chasse/{id}/valider', [EspaceJeuxController::class, 'validerPhoto'])->whereNumber('id')->name('chasse.valider');
            Route::delete('/chasse/{id}', [EspaceJeuxController::class, 'supprimerPhoto'])->whereNumber('id')->name('chasse.supprimer');

            Route::post('/puzzle', [EspaceJeuxController::class, 'ajouterImagesPuzzle'])->name('puzzle.ajouter');
            Route::delete('/puzzle/{index}', [EspaceJeuxController::class, 'retirerImagePuzzle'])->whereNumber('index')->name('puzzle.retirer');
        });

        // Livre souvenir imprimable
        Route::get('/livre-souvenir',            [LivreSouvenirController::class, 'index'])->name('livre');
        Route::get('/livre-souvenir/apercu',     [LivreSouvenirController::class, 'apercu'])->name('livre.apercu');
        Route::get('/livre-souvenir/telecharger', [LivreSouvenirController::class, 'telecharger'])->name('livre.telecharger');

        // Cagnotte : raccordement du compte bancaire des mariés.
        Route::get('/paiements',           [PaiementController::class, 'index'])->name('paiements');
        Route::get('/paiements/inscrire',  [PaiementController::class, 'inscrire'])->name('paiements.inscrire');
        Route::get('/paiements/stripe',    [PaiementController::class, 'tableauDeBord'])->name('paiements.stripe');

        // Pages et blocs de contenu.
        Route::get('/pages', [PagesController::class, 'index'])->name('pages');
        Route::get('/pages/{page}', [PagesController::class, 'page'])->name('page');
        Route::get('/pages/{page}/ajouter/{type}',  [PagesController::class, 'creer'])->name('bloc.creer');
        Route::post('/pages/{page}/ajouter/{type}', [PagesController::class, 'stocker'])->name('bloc.stocker');
        Route::get('/blocs/{bloc}',            [PagesController::class, 'editer'])->name('bloc.editer');
        Route::post('/blocs/{bloc}',           [PagesController::class, 'mettreAJour'])->name('bloc.maj');
        Route::delete('/blocs/{bloc}',         [PagesController::class, 'supprimer'])->name('bloc.supprimer');
        Route::post('/blocs/{bloc}/{sens}',    [PagesController::class, 'deplacer'])
            ->whereIn('sens', ['monter', 'descendre'])
            ->name('bloc.deplacer');
    });

/*
|--------------------------------------------------------------------------
| Contributions des invités — /mariage/{slug}/maries/…
|--------------------------------------------------------------------------
| Écrans existants, conservés le temps de leur intégration à l'espace.
| Contiennent des messages privés : authentification obligatoire.
*/
Route::prefix('mariage/{event}/maries')
    ->middleware(['event', 'organisateur'])
    ->name('maries.')
    ->group(function () {
        Route::get('/', [MarieController::class, 'index'])->name('index');
        Route::get('/livre-or', [MarieController::class, 'livreOr'])->name('livreOr');
        Route::get('/galerie', [MarieController::class, 'galerie'])->name('galerie');

        // Modération : publier ou retirer une contribution.
        Route::post('/livre-or/{id}/publier',  [MarieController::class, 'publierMessage'])->whereNumber('id')->name('livreOr.publier');
        Route::delete('/livre-or/{id}',        [MarieController::class, 'supprimerMessage'])->whereNumber('id')->name('livreOr.supprimer');
        Route::post('/galerie/{id}/publier',   [MarieController::class, 'publierPhoto'])->whereNumber('id')->name('galerie.publier');
        Route::delete('/galerie/{id}',         [MarieController::class, 'supprimerPhoto'])->whereNumber('id')->name('galerie.supprimer');
        Route::get('/jeux/qui-de-nous-2', [MarieController::class, 'reponsesQuiDeux'])->name('reponsesQuiDeux');
        Route::get('/photos', [MarieController::class, 'photos'])->name('photos');
        Route::get('/messages', [MarieController::class, 'messages'])->name('messages');
        Route::get('/statistiques', [MarieController::class, 'statistiques'])->name('statistiques');

        // Les réglages passaient par une vue jamais écrite et une config
        // inexistante. Ils sont désormais couverts par l'espace organisateur.
        Route::get('/parametres', fn () => redirect()->route('espace.informations'))->name('parametres');
    });

/*
|--------------------------------------------------------------------------
| Administration du mariage — /mariage/{slug}/admin/…
|--------------------------------------------------------------------------
| Le préfixe obscur `admin190964` a disparu : ce n'était pas une protection.
| L'authentification en est une.
*/
Route::prefix('mariage/{event}/admin')
    ->middleware(['event', 'organisateur'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('espace.index'))->name('dashboard');

        // Les anciens écrans des jeux (sessions à lancer, questions, cartes
        // memory, grilles posées case par case) sont remplacés par l'écran
        // Jeux de l'espace. Les adresses restent, et y mènent.
        foreach (['questions', 'sessions', 'mots-croises', 'memory-cards', 'resultats/{session?}', 'chasse-photos/{session?}'] as $ancien) {
            Route::get("/{$ancien}", fn () => redirect()->route('espace.jeux.index'));
        }

        // L'ancien écran du déroulé (avec ses boutons « en cours ») est
        // remplacé par le Programme de l'espace.
        Route::get('/etapes-ceremonie', fn () => redirect()->route('espace.programme'))->name('etapesCeremonie');

        // QR codes
        Route::get('/qrcodes', [QrCodeController::class, 'index'])->name('qrcodes.index');
        Route::post('/qrcodes', [QrCodeController::class, 'store'])->name('qrcodes.store');
        Route::get('/qrcodes/{qrCode}/stats', [QrCodeController::class, 'stats'])->whereNumber('qrCode')->name('qrcodes.stats');
        Route::get('/qrcodes/{qrCode}/download', [QrCodeController::class, 'download'])->whereNumber('qrCode')->name('qrcodes.download');
        Route::post('/qrcodes/kit', [QrCodeController::class, 'kit'])->name('qrcodes.kit');
        Route::get('/qrcodes/impressions', [QrCodeController::class, 'impressions'])->name('qrcodes.impressions');
    });
