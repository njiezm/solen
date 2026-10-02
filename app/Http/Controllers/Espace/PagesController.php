<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Solen\Champs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Éditeur de contenu : les pages du site invité et leurs blocs.
 *
 * Les blocs sont résolus à la main plutôt que par liaison implicite : le
 * middleware SubstituteBindings s'exécute AVANT celui qui établit le
 * locataire, une liaison automatique contournerait donc le cloisonnement et
 * laisserait modifier le bloc d'un autre mariage par son identifiant.
 */
class PagesController extends Controller
{
    public function __construct(private readonly Champs $champs)
    {
    }

    public function index(): View
    {
        $comptes = ContentBlock::query()
            ->selectRaw('page, count(*) as total')
            ->groupBy('page')
            ->pluck('total', 'page');

        return view('espace.pages', [
            'pages'   => config('solen_schema.pages'),
            'comptes' => $comptes,
        ]);
    }

    public function page(string $page): View
    {
        $definition = config("solen_schema.pages.{$page}");

        abort_unless($definition, 404, 'Cette page n’existe pas.');

        return view('espace.page', [
            'cle'    => $page,
            'page'   => $definition,
            'blocs'  => ContentBlock::where('page', $page)->orderBy('ordre')->get(),
            'types'  => ContentBlock::typesPourPage($page),
        ]);
    }

    public function creer(string $page, string $type): View
    {
        $definition = config("solen_schema.blocs.{$type}");

        abort_unless($definition && config("solen_schema.pages.{$page}"), 404);

        return view('espace.bloc', [
            'cle'        => $page,
            'page'       => config("solen_schema.pages.{$page}"),
            'type'       => $type,
            'definition' => $definition,
            'bloc'       => null,
            'valeurs'    => [],
        ]);
    }

    public function stocker(Request $request, string $page, string $type): RedirectResponse
    {
        $definition = config("solen_schema.blocs.{$type}");

        abort_unless($definition && config("solen_schema.pages.{$page}"), 404);

        $request->validate(
            $this->champs->regles($definition['champs']),
            [],
            $this->champs->intitules($definition['champs'])
        );

        ContentBlock::create([
            'page'    => $page,
            'type'    => $type,
            'donnees' => $this->champs->normaliser($definition['champs'], $request),
            'ordre'   => (ContentBlock::where('page', $page)->max('ordre') ?? 0) + 1,
            'actif'   => true,
        ]);

        return redirect()
            ->route('espace.page', $page)
            ->with('ok', 'Le contenu a été ajouté.');
    }

    public function editer(int $bloc): View
    {
        $modele = $this->trouver($bloc);

        return view('espace.bloc', [
            'cle'        => $modele->page,
            'page'       => config("solen_schema.pages.{$modele->page}"),
            'type'       => $modele->type,
            'definition' => $modele->definition(),
            'bloc'       => $modele,
            'valeurs'    => $modele->donnees ?? [],
        ]);
    }

    public function mettreAJour(Request $request, int $bloc): RedirectResponse
    {
        $modele = $this->trouver($bloc);
        $champs = $modele->champs();

        $request->validate(
            $this->champs->regles($champs),
            [],
            $this->champs->intitules($champs)
        );

        $modele->update([
            'donnees' => $this->champs->normaliser($champs, $request, 'donnees', $modele->donnees ?? []),
            'actif'   => $request->boolean('actif'),
        ]);

        return redirect()
            ->route('espace.page', $modele->page)
            ->with('ok', 'Le contenu a été mis à jour.');
    }

    public function supprimer(int $bloc): RedirectResponse
    {
        $modele = $this->trouver($bloc);
        $page   = $modele->page;
        $modele->delete();

        return redirect()
            ->route('espace.page', $page)
            ->with('ok', 'Le contenu a été supprimé.');
    }

    /** Échange la position du bloc avec son voisin. */
    public function deplacer(int $bloc, string $sens): RedirectResponse
    {
        $modele = $this->trouver($bloc);

        $voisin = ContentBlock::where('page', $modele->page)
            ->when($sens === 'monter',
                fn ($q) => $q->where('ordre', '<', $modele->ordre)->orderByDesc('ordre'),
                fn ($q) => $q->where('ordre', '>', $modele->ordre)->orderBy('ordre'))
            ->first();

        if ($voisin) {
            [$modele->ordre, $voisin->ordre] = [$voisin->ordre, $modele->ordre];
            $modele->save();
            $voisin->save();
        }

        return redirect()->route('espace.page', $modele->page);
    }

    /** Toujours passer par le scope du mariage courant. */
    private function trouver(int $id): ContentBlock
    {
        return ContentBlock::findOrFail($id);
    }
}
