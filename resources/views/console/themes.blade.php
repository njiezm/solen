@extends('console.layout')

@section('titre', 'Thèmes')
@section('chapeau', 'Trois couleurs, deux polices. C’est ce qui rend la prestation « thème sur mesure » rentable.')

@section('contenu')

<div class="carte">
    <div class="actions" style="margin:0 0 1.2rem; padding:0; border:0">
        <a href="{{ route('console.theme.creer') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nouveau thème
        </a>
    </div>

    <div class="theme-grid">
        @foreach ($themes as $theme)
            <div class="theme-card" @unless($theme->actif) style="opacity:.55" @endunless>
                <div class="theme-swatch" style="background: {{ $theme->surface }}; color: {{ $theme->ink }};">
                    <span class="ring"></span>
                    <span class="bar" style="background: {{ $theme->accent }}"></span>
                </div>
                <div class="name">
                    {{ $theme->nom }}
                    <div style="font-size:.72rem; color:var(--ink-mute); font-weight:400; margin-top:.2rem">
                        {{ $theme->resume() }}
                    </div>
                    <div style="font-size:.74rem; color:var(--ink-mute); font-weight:400; margin-top:.15rem">
                        {{ $theme->events_count }} mariage{{ $theme->events_count > 1 ? 's' : '' }}
                        @unless($theme->actif) · masqué @endunless
                    </div>
                    <div style="display:flex; gap:.4rem; margin-top:.6rem">
                        <a href="{{ route('console.theme.editer', $theme) }}" class="mini">Modifier</a>
                        @if ($theme->events_count === 0)
                            <form method="POST" action="{{ route('console.theme.supprimer', $theme) }}"
                                  onsubmit="return confirm('Supprimer ce thème ?')">
                                @csrf @method('DELETE')
                                <button class="mini mini--danger">Supprimer</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection
