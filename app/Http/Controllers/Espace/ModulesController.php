<?php

namespace App\Http\Controllers\Espace;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Solen\Champs;
use App\Solen\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Activation et réglage des modules.
 *
 * Aucun formulaire n'est écrit ici : les champs viennent de la colonne
 * `champs` du module, elle-même issue de config/solen_schema.php.
 */
class ModulesController extends Controller
{
    public function __construct(private readonly Champs $champs)
    {
    }

    public function index(CurrentEvent $courant): View
    {
        $event = $courant->get();

        return view('espace.modules', [
            'phases'  => config('solen.phases'),
            'modules' => $event->modules()->orderBy('ordre')->get()->groupBy('phase'),
        ]);
    }

    /** Active ou désactive un module d'un geste, sans quitter la liste. */
    public function basculer(Request $request, string $cle, CurrentEvent $courant): RedirectResponse
    {
        $event  = $courant->get();
        $module = $event->modules()->where('cle', $cle)->firstOrFail();

        if ($module->statut !== 'live') {
            return back()->with('erreur', "« {$module->nom} » n’est pas encore disponible.");
        }

        $event->modules()->updateExistingPivot($module->id, [
            'actif' => ! $module->pivot->actif,
        ]);

        return back()->with('ok', $module->pivot->actif
            ? "« {$module->nom} » a été désactivé."
            : "« {$module->nom} » a été activé.");
    }

    public function editer(string $cle, CurrentEvent $courant): View
    {
        $event  = $courant->get();
        $module = $event->modules()->where('cle', $cle)->firstOrFail();

        abort_unless($module->reglable(), 404, 'Ce module n’a aucun réglage.');

        return view('espace.module-reglages', [
            'module'  => $module,
            'valeurs' => $this->valeurs($module),
        ]);
    }

    public function enregistrer(Request $request, string $cle, CurrentEvent $courant): RedirectResponse
    {
        $event  = $courant->get();
        $module = $event->modules()->where('cle', $cle)->firstOrFail();

        abort_unless($module->reglable(), 404);

        $request->validate(
            $this->champs->regles($module->champs, 'reglages'),
            [],
            $this->champs->intitules($module->champs, 'reglages')
        );

        $valeurs = $this->champs->normaliser(
            $module->champs,
            $request,
            'reglages',
            $this->valeurs($module)
        );

        // Les clés gérées ailleurs que dans ce formulaire (questions, images
        // du puzzle, mots croisés…) sont conservées telles quelles.
        $event->modules()->updateExistingPivot($module->id, [
            'config' => json_encode(array_merge($this->valeurs($module), $valeurs)),
        ]);

        return redirect()
            ->route('espace.modules')
            ->with('ok', "Les réglages de « {$module->nom} » ont été enregistrés.");
    }

    /** @return array<string, mixed> */
    private function valeurs(Module $module): array
    {
        $config = $module->pivot->config ?? [];

        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }

        return array_merge($module->valeursParDefaut(), $config);
    }
}
