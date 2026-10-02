@props(['champ', 'valeur' => null, 'prefixe' => 'donnees'])

@php
    /**
     * Rend n'importe quel champ à partir de sa déclaration dans
     * config/solen_schema.php. C'est ce composant qui évite d'écrire
     * un formulaire par module et par type de bloc.
     */
    $cle    = $champ['cle'];
    $type   = $champ['type'] ?? 'texte';
    $requis = ! empty($champ['requis']);

    // Un préfixe vide signifie « champ à la racine du formulaire » :
    // name="nom" plutôt que name="donnees[nom]".
    $nom    = $prefixe ? "{$prefixe}[{$cle}]" : $cle;
    $chemin = $prefixe ? "{$prefixe}.{$cle}" : $cle;
    $id     = Str::slug($chemin ?: $cle);

    $erreur = $errors->first($chemin);
    $valeur = old($chemin, $valeur ?? $champ['defaut'] ?? null);
@endphp

<div class="champ @if($erreur) champ--erreur @endif" data-type="{{ $type }}">

    @if ($type !== 'booleen')
        <label for="{{ $id }}">
            {{ $champ['label'] ?? $cle }}
            @if ($requis) <span class="champ-requis" aria-hidden="true">*</span> @endif
        </label>
    @endif

    @switch($type)

        @case('texte_long')
        @case('riche')
            <textarea id="{{ $id }}" name="{{ $nom }}" rows="4"
                      @if($requis) required @endif
                      placeholder="{{ $champ['placeholder'] ?? '' }}">{{ $valeur }}</textarea>
            @break

        @case('booleen')
            <label class="champ-bascule" for="{{ $id }}">
                <input type="hidden" name="{{ $nom }}" value="0">
                <input type="checkbox" id="{{ $id }}" name="{{ $nom }}" value="1"
                       @checked($valeur)>
                <span>{{ $champ['label'] ?? $cle }}</span>
            </label>
            @break

        @case('choix')
            <select id="{{ $id }}" name="{{ $nom }}" @if($requis) required @endif>
                @unless($requis)
                    <option value="">—</option>
                @endunless
                @foreach ($champ['options'] ?? [] as $val => $libelle)
                    <option value="{{ $val }}" @selected((string) $valeur === (string) $val)>{{ $libelle }}</option>
                @endforeach
            </select>
            @break

        @case('choix_multiple')
            @php $coches = is_array($valeur) ? $valeur : []; @endphp
            <div class="champ-cases">
                @foreach ($champ['options'] ?? [] as $val => $libelle)
                    <label class="champ-bascule">
                        <input type="checkbox" name="{{ $prefixe }}[{{ $cle }}][]" value="{{ $val }}"
                               @checked(in_array($val, $coches, true))>
                        <span>{{ $libelle }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case('image')
        @case('fichier')
            @if ($valeur)
                <div class="champ-apercu">
                    @if ($type === 'image')
                        <img src="{{ Str::startsWith($valeur, ['http', '/']) ? $valeur : Storage::url($valeur) }}"
                             alt="" loading="lazy">
                    @else
                        <span class="champ-fichier">{{ basename($valeur) }}</span>
                    @endif

                    <label class="champ-bascule champ-supprimer">
                        <input type="checkbox" name="supprimer[{{ $prefixe }}][{{ $cle }}]" value="1">
                        <span>Retirer</span>
                    </label>
                </div>
            @endif
            <input type="file" id="{{ $id }}" name="{{ $nom }}"
                   @if($type === 'image') accept="image/*" @endif>
            @break

        @case('couleur')
            <input type="color" id="{{ $id }}" name="{{ $nom }}" value="{{ $valeur ?: '#C99B63' }}">
            @break

        @case('nombre')
            <input type="number" id="{{ $id }}" name="{{ $nom }}" value="{{ $valeur }}"
                   @isset($champ['min']) min="{{ $champ['min'] }}" @endisset
                   @isset($champ['max']) max="{{ $champ['max'] }}" @endisset
                   @if($requis) required @endif>
            @break

        @case('montant')
            <div class="champ-suffixe">
                <input type="number" id="{{ $id }}" name="{{ $nom }}" value="{{ $valeur }}"
                       step="1" min="0" @if($requis) required @endif>
                <span>€</span>
            </div>
            @break

        @default
            @php
                $html = match ($type) {
                    'date'      => 'date',
                    'heure'     => 'time',
                    'datetime'  => 'datetime-local',
                    'email'     => 'email',
                    'telephone' => 'tel',
                    'lien'      => 'url',
                    default     => 'text',
                };
            @endphp
            <input type="{{ $html }}" id="{{ $id }}" name="{{ $nom }}" value="{{ $valeur }}"
                   @if($requis) required @endif
                   placeholder="{{ $champ['placeholder'] ?? '' }}">
    @endswitch

    @if ($erreur)
        <p class="champ-erreur">{{ $erreur }}</p>
    @elseif (! empty($champ['aide']))
        <p class="champ-aide">{{ $champ['aide'] }}</p>
    @endif
</div>
