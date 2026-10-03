<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Participant;
use App\Models\LivreOr;

class LivreOrController extends Controller
{
    public function index()
    {
        $messages = LivreOr::publies()->with('participant')
            ->orderBy('created_at', 'desc')
            ->get();

        $event = app(\App\Solen\CurrentEvent::class)->get();

        return view('pages.livre-or', [
            'messages'     => $messages,
            'introduction' => $event->reglage('livredor', 'texte_intro', 'Laissez-nous un mot, nous le lirons avec émotion.'),
            'longueurMax'  => (int) $event->reglage('livredor', 'longueur_max', 1000),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // Le prénom suffit à signer un mot : le nom est facultatif.
            'nom' => 'nullable|string|max:100',
            'prenom' => 'required|string|max:100',
            'message' => 'required|string|max:' . (int) app(\App\Solen\CurrentEvent::class)->get()->reglage('livredor', 'longueur_max', 1000),
        ]);

        // Identification légère
        $participant = Participant::firstOrCreate([
            'nom' => trim((string) $request->input('nom')),
            'prenom' => $request->prenom,
        ]);

        $aValider = app(\App\Solen\CurrentEvent::class)->get()
            ->reglage('livredor', 'moderation', 'aucune') === 'apres';

        LivreOr::create([
            'participant_id' => $participant->id,
            'message' => $request->message,
            'publie' => ! $aValider,
        ]);

        return redirect()->back()->with('success', $aValider
            ? 'Merci ! Votre message apparaîtra dès que les mariés l’auront lu.'
            : 'Merci pour votre message ❤️');
    }
}
