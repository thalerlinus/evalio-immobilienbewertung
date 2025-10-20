@component('mail::message')
# Ihre Evalio Immobilien-Einschätzung

@if(!empty($contactName))
Hallo {{ $contactName }},

@endif
Vielen Dank für Ihre Anfrage. Auf Basis Ihrer Angaben haben wir die Restnutzungsdauer wie folgt ermittelt:

@component('mail::panel')
**Restnutzungsdauer:** {{ $calculation->rnd_years ? number_format((float) $calculation->rnd_years, 1, ',', '.') . ' Jahre' : '–' }}

**Ersteinschätzung:** {{ $calculation->rnd_interval_label ?? '–' }}

**AfA-Satz:** {{ $calculation->afa_percent ? number_format((float) $calculation->afa_percent, 2, ',', '.') . ' %' : '–' }}

**Empfehlung:** {{ $calculation->recommendation ?? '–' }}
@endcomponent

## Details zur Bewertung
- Gebäudeart: {{ optional($calculation->propertyType)->label ?? '–' }}
- Baujahr: {{ $calculation->baujahr ?? '–' }}
- Ermittlungsjahr: {{ $calculation->ermittlungsjahr ?? '–' }}
- Score: {{ $calculation->score ? number_format((float) $calculation->score, 1, ',', '.') : '–' }} Punkte

@if(!empty($calculation->score_details))
@component('mail::table')
| Kategorie | Punkte |
| :-- | --: |
@foreach(($calculation->score_details ?? []) as $detail)
| {{ $detail['label'] ?? 'Kategorie' }} | {{ number_format((float) ($detail['points'] ?? 0), 2, ',', '.') }} |
@endforeach
@endcomponent
@endif

## Angebot
Wir haben auf Basis der Daten ein Angebot für Sie erzeugt.

- Angebotsnummer: {{ $offer->number }}
- Nettobetrag: {{ $offer->net_total_eur ? number_format($offer->net_total_eur, 0, ',', '.') . ' €' : 'auf Anfrage' }}
- Bruttobetrag: {{ $offer->gross_total_eur ? number_format($offer->gross_total_eur, 0, ',', '.') . ' €' : 'auf Anfrage' }}

@component('mail::button', ['url' => $publicUrl])
Angebot ansehen & bestätigen
@endcomponent

Sollten Sie Fragen haben, antworten Sie einfach auf diese E-Mail – wir helfen gern weiter.

Viele Grüße
Ihr Evalio-Team
@endcomponent
