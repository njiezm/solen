<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LivreOr;
use App\Models\Photo;
use App\Models\ReponseQuiDeux;
use App\Models\QuestionQuiDeux;
use App\Models\SessionJeu;
use App\Models\ChassePhoto;
use App\Models\Participant;
use App\Models\MotsCroises;
use App\Models\MotCroise;
use App\Models\MemoryCard;
use App\Models\QrCodeScan;

class MarieController extends Controller
{
    /**
     * Affiche la page d'accueil de l'espace mariés
     */
    public function index()
    {
        // Statistiques générales
        $messagesCount = LivreOr::count();
        $photosCount = Photo::count();
        $chassePhotosCount = ChassePhoto::count();
        $participantsCount = Participant::count();
        
        return view('maries.index', compact(
            'messagesCount', 
            'photosCount', 
            'chassePhotosCount', 
            'participantsCount'
        ));
    }
    
    /**
     * Affiche le livret d'or
     */
    public function livreOr()
    {
        // Les messages en attente d'abord : ce sont eux qui demandent un geste.
        $messages = LivreOr::with('participant')->orderBy('publie')->orderBy('created_at', 'desc')->get();

        return view('maries.livre-or', compact('messages'));
    }
    
    /**
     * Affiche la galerie photo
     */
    public function galerie()
    {
        $photos = Photo::with('participant')->orderBy('publie')->orderBy('created_at', 'desc')->get();

        return view('maries.galerie', compact('photos'));
    }

    // --- Modération --------------------------------------------------------
    // Recherche explicite plutôt que liaison implicite : le cloisonnement par
    // mariage n'est posé qu'après la résolution des paramètres de route.

    public function publierMessage(int $id)
    {
        LivreOr::findOrFail($id)->update(['publie' => true]);

        return back()->with('ok', 'Message publié.');
    }

    public function supprimerMessage(int $id)
    {
        LivreOr::findOrFail($id)->delete();

        return back()->with('ok', 'Message retiré.');
    }

    public function publierPhoto(int $id)
    {
        Photo::findOrFail($id)->update(['publie' => true]);

        return back()->with('ok', 'Photo publiée.');
    }

    public function supprimerPhoto(int $id)
    {
        $photo = Photo::findOrFail($id);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('ok', 'Photo retirée.');
    }
    
    /**
     * Affiche les réponses au jeu "Qui de nous 2"
     */
    public function reponsesQuiDeux()
    {
        $sessions = SessionJeu::where('type_jeu', 'qui_deux')->get();
        $questions = QuestionQuiDeux::with('reponses')->get();
        
        // Statistiques par question
        $event = app(\App\Solen\CurrentEvent::class)->get();
        $p1 = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($event->partenaire_1 ?: 'Elle'));
        $p2 = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($event->partenaire_2 ?: 'Lui'));
        $pour = fn ($question, string $prenom) => $question->reponses
            ->filter(fn ($r) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $r->reponse)) === $prenom)
            ->count();

        $statsParQuestion = [];
        foreach ($questions as $question) {
            $statsParQuestion[$question->id] = [
                'question' => $question->question,
                'bonne_reponse' => $question->bonne_reponse,
                // Clés historiques : « gilles » = partenaire 2, « maeva » = partenaire 1.
                'reponses_gilles' => $pour($question, $p2),
                'reponses_maeva' => $pour($question, $p1),
                'total_reponses' => $question->reponses()->count()
            ];
        }
        
        return view('maries.reponses-qui-deux', compact('sessions', 'questions', 'statsParQuestion'));
    }
    
    /**
     * Affiche toutes les photos des invités (chasse photo)
     */
    public function photos()
    {
        $photos = ChassePhoto::with('participant')->orderBy('created_at', 'desc')->get();
        
        return view('maries.photos', compact('photos'));
    }
    
    /**
     * Affiche tous les messages des invités
     */
    public function messages()
    {
        $messages = LivreOr::orderBy('created_at', 'desc')->get();
        
        return view('maries.messages', compact('messages'));
    }
    
    /**
     * Affiche les statistiques des jeux
     */
    public function statistiques()
    {
        // Statistiques "Qui de nous 2"
        $sessionsQuiDeux = SessionJeu::where('type_jeu', 'qui_deux')->get();
        // La relation s'appelle `reponses` sur Participant, pas `reponsesQuiDeux`.
        $participantsQuiDeux = Participant::whereHas('reponses')->count();
        
        // Statistiques "Mots croisés"
        $sessionsMotsCroises = SessionJeu::where('type_jeu', 'mots_croises')->get();
        
        // Statistiques "Memory"
        $sessionsMemory = SessionJeu::where('type_jeu', 'memory')->get();
        
        // Statistiques "Chasse photo"
        $sessionsChassePhoto = SessionJeu::where('type_jeu', 'chasse_photo')->get();
        $photosChasse = ChassePhoto::count();
        
        // Statistiques QR Codes
        $scansCount = QrCodeScan::count();
        
        return view('maries.statistiques', compact(
            'sessionsQuiDeux', 
            'participantsQuiDeux',
            'sessionsMotsCroises',
            'sessionsMemory',
            'sessionsChassePhoto',
            'photosChasse',
            'scansCount'
        ));
    }
    
    /**
     * Affiche la page des paramètres
     */
    public function parametres()
    {
        // Récupérer les paramètres existants ou créer des valeurs par défaut
        $parametres = [
            'titre_site' => config('maries.titre_site', 'Mariage de Gilles et Maëva'),
            'couleur_principale' => config('maries.couleur_principale', '#6a8e7f'),
            'message_accueil' => config('maries.message_accueil', 'Bienvenue sur notre site de mariage !'),
            'afficher_compteur' => config('maries.afficher_compteur', true),
            'date_mariage' => config('maries.date_mariage', now()->addDays(30)->format('Y-m-d')),
        ];
        
        return view('maries.parametres', compact('parametres'));
    }
    
    /**
     * Met à jour les paramètres
     */
    public function updateParametres(Request $request)
    {
        $request->validate([
            'titre_site' => 'required|string|max:255',
            'couleur_principale' => 'required|string|max:7',
            'message_accueil' => 'required|string|max:1000',
            'afficher_compteur' => 'boolean',
            'date_mariage' => 'required|date',
        ]);
        
        // Mettre à jour les paramètres dans un fichier de configuration ou en base de données
        // Ici, nous utilisons la session pour simplifier, mais en production, utilisez une table ou un fichier de config
        session([
            'maries.titre_site' => $request->titre_site,
            'maries.couleur_principale' => $request->couleur_principale,
            'maries.message_accueil' => $request->message_accueil,
            'maries.afficher_compteur' => $request->has('afficher_compteur'),
            'maries.date_mariage' => $request->date_mariage,
        ]);
        
        return back()->with('success', 'Paramètres mis à jour avec succès !');
    }
}