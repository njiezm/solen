<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\CodePromo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Les codes promo : créés ici, utilisables en console et à la commande en ligne. */
class CodesPromoController extends Controller
{
    public function index(): View
    {
        return view('console.facturation.codes', [
            'codes'    => CodePromo::orderByDesc('actif')->orderByDesc('created_at')->get(),
            'formules' => collect(config('solen.plans'))->pluck('nom', 'key'),
        ]);
    }

    public function enregistrer(Request $request): RedirectResponse
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);

        $donnees = $request->validate([
            'code'             => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('codes_promo', 'code')],
            'libelle'          => ['nullable', 'string', 'max:120'],
            'type'             => ['required', Rule::in(['pourcentage', 'montant'])],
            'valeur'           => ['required', 'numeric', 'min:0.01', $request->input('type') === 'pourcentage' ? 'max:100' : 'max:99999'],
            'formules'         => ['nullable', 'array'],
            'formules.*'       => [Rule::in(collect(config('solen.plans'))->pluck('key')->all())],
            'debut_le'         => ['nullable', 'date'],
            'fin_le'           => ['nullable', 'date', 'after_or_equal:debut_le'],
            'utilisations_max' => ['nullable', 'integer', 'min:1'],
        ], ['code.regex' => 'Lettres, chiffres et tirets uniquement.', 'code.unique' => 'Ce code existe déjà.']);

        $donnees['formules'] = ($donnees['formules'] ?? []) ?: null;

        CodePromo::create($donnees + ['actif' => true]);

        return back()->with('ok', "Code {$donnees['code']} créé.");
    }

    public function basculer(int $code): RedirectResponse
    {
        $promo = CodePromo::findOrFail($code);
        $promo->update(['actif' => ! $promo->actif]);

        return back()->with('ok', "Code {$promo->code} " . ($promo->actif ? 'réactivé' : 'désactivé') . '.');
    }
}
