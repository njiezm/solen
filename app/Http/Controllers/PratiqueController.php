<?php

namespace App\Http\Controllers;

use App\Models\ContentBlock;
use App\Solen\CurrentEvent;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PratiqueController extends Controller
{
    /**
     * Page des infos pratiques : hébergement, transport, contacts.
     *
     * Le contenu vient désormais des blocs éditables. Rien n'est écrit ici
     * ni dans la vue : les mariés ajoutent un hôtel ou changent un numéro
     * depuis leur espace.
     */
    public function index(): View
    {
        $blocs = ContentBlock::pourPage('pratique')->get();
        $event = app(CurrentEvent::class)->get();

        return view('pages.details-pratiques', [
            'hebergements' => $this->champs($blocs, 'hebergement',
                ['nom', 'adresse', 'telephone', 'lien', 'prix', 'distance', 'code_promo']),

            'transports'   => $this->champs($blocs, 'transport',
                ['titre', 'mode', 'description', 'telephone', 'whatsapp', 'lien']),

            'contacts'     => $this->champs($blocs, 'contact',
                ['nom', 'role', 'telephone', 'whatsapp', 'email']),

            'infos'        => $this->champs($blocs, 'info', ['icone', 'titre', 'contenu']),

            'dressCode'    => $event?->reglage('site', 'dress_code'),
            'introduction' => $event?->reglage('pratique', 'texte_intro'),
        ]);
    }

    /**
     * Aplatit les blocs d'un type en objets simples, prêts pour la vue.
     *
     * @param  Collection<int, ContentBlock>  $blocs
     * @param  list<string>                   $champs
     * @return Collection<int, object>
     */
    private function champs(Collection $blocs, string $type, array $champs): Collection
    {
        return $blocs
            ->where('type', $type)
            ->map(fn (ContentBlock $bloc) => (object) collect($champs)
                ->mapWithKeys(fn (string $cle) => [$cle => $bloc->valeur($cle)])
                ->all())
            ->values();
    }
}
