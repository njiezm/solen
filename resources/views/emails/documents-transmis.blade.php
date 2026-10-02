<x-courriel::layout
    titre="{{ $noms ? 'Vos documents Solen' : 'Un message de Solen' }}"
    preheader="{{ count($noms) }} document{{ count($noms) > 1 ? 's' : '' }} joint{{ count($noms) > 1 ? 's' : '' }} à ce message.">

<p style="margin:0 0 18px;">{!! nl2br(e($texte)) !!}</p>

@if ($noms)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background:#FCFAF7;border:1px solid #E7E1D7;border-radius:12px;margin:0 0 18px;">
        <tr>
            <td style="padding:18px 22px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:600;letter-spacing:1.4px;text-transform:uppercase;color:#A87C46;">
                    En pièce{{ count($noms) > 1 ? 's' : '' }} jointe{{ count($noms) > 1 ? 's' : '' }}
                </p>
                @foreach ($noms as $nom)
                    <p style="margin:0 0 6px;font-size:14px;color:#1B1B2F;">📄 {{ $nom }}</p>
                @endforeach
            </td>
        </tr>
    </table>
@endif

@if ($event)
    <x-courriel::partials.bouton :url="route('espace.index', $event->slug)" libelle="Ouvrir mon espace" />
@endif

<p style="margin:0;color:#6E6E85;font-size:13px;">
    Une question ? Répondez simplement à cet e-mail.<br>
    {{ config('solen.entreprise.nom_commercial') }}
</p>

</x-courriel::layout>
