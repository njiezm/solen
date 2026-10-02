{{-- Une mention de l'entreprise, ou un repère visible si elle manque encore. --}}
@php $v = \App\Solen\Entreprise::get($cle); @endphp
@if (filled($v)){{ $v }}@else<mark class="a-completer">[à compléter : {{ $libelle ?? $cle }}]</mark>@endif
