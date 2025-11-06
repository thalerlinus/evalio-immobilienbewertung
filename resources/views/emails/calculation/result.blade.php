@component('mail::message')
# Ihre Ersteinschätzung zur Restnutzungsdauer

@if(!empty($contactName))
Hallo {{ $contactName }},

@endif
Vielen Dank für Ihre Anfrage bei EVALIO. Auf Basis Ihrer Angaben haben wir die Restnutzungsdauer wie folgt ermittelt:

@component('mail::panel')
**Restnutzungsdauer:** {{ $calculation->rnd_interval_label ?? '–' }}

**AfA-Satz:** {{ $calculation->afa_percent_label ?? '–' }}

**Empfehlung:** {{ $calculation->recommendation ?? '–' }}
@endcomponent

## Details zur Immobilie
- Gebäudeart: {{ optional($calculation->propertyType)->label ?? '–' }}
- Baujahr: {{ $calculation->baujahr ?? '–' }}
- Ermittlungsjahr: {{ $calculation->ermittlungsjahr ?? '–' }}

@php
    $address = $calculation->inputs['address'] ?? null;
    $formatAddress = static fn (?string $street, ?string $zip, ?string $city): string => collect([
        $street ? trim($street) : null,
        trim(trim(($zip ?? '') . ' ' . ($city ?? '')))
    ])->filter()->implode(', ');
@endphp

@if($address && ($address['street'] ?? $address['zip'] ?? $address['city']))
**Objektadresse:** {{ $formatAddress($address['street'] ?? null, $address['zip'] ?? null, $address['city'] ?? null) }}
@endif

@php
    $customer = $offer->customer;
    $customerSnapshot = $offer->input_snapshot['customer'] ?? [];
    $contactStreet = $customer?->billing_street
        ?? ($customerSnapshot['street'] ?? data_get($calculation->inputs, 'contact.street'));
    $contactZip = $customer?->billing_zip
        ?? ($customerSnapshot['zip'] ?? data_get($calculation->inputs, 'contact.zip'));
    $contactCity = $customer?->billing_city
        ?? ($customerSnapshot['city'] ?? data_get($calculation->inputs, 'contact.city'));
    $contactPersonName = $customer?->name
        ?? ($customerSnapshot['name'] ?? data_get($calculation->inputs, 'contact.name'));
    $contactEmail = $customer?->email
        ?? ($customerSnapshot['email'] ?? data_get($calculation->inputs, 'contact.email'));
    $contactPhone = $customer?->phone
        ?? ($customerSnapshot['phone'] ?? data_get($calculation->inputs, 'contact.phone'));
    $contactAddressLine = $formatAddress($contactStreet, $contactZip, $contactCity);
    $contactLines = collect([
        $contactPersonName,
        $contactAddressLine,
        $contactEmail ? 'E-Mail: ' . $contactEmail : null,
        $contactPhone ? 'Telefon: ' . $contactPhone : null,
    ])->filter()->values();
@endphp

@if($contactLines->isNotEmpty())
## Ihre Kontaktdaten
@foreach($contactLines as $line)
- {{ $line }}
@endforeach
@endif

@php
    $renovations = $calculation->inputs['renovations'] ?? [];
    $timeWindowLabels = [
        'nicht' => 'Nicht durchgeführt / Weiß nicht',
        'weiss_nicht' => 'Weiß nicht wann',
        'bis_5' => 'In den letzten 5 Jahren',
        'bis_10' => 'Vor 5-10 Jahren',
        'bis_15' => 'Vor 10-15 Jahren',
        'bis_20' => 'Vor 15-20 Jahren',
        'ueber_20' => 'Vor über 20 Jahren',
    ];
    $extentLabels = [
        0 => 'Nicht durchgeführt',
        20 => 'Nur Ausbesserungsarbeiten',
        40 => 'Vereinzelte Maßnahmen',
        60 => 'Teilweise erneuert',
        80 => 'Überwiegend erneuert',
        100 => 'Vollständig saniert',
    ];
    
    // Normalisiere Renovations-Daten
    $normalizedRenovations = collect($renovations)->map(function($ren, $key) {
        // Wenn category_key nicht im Wert ist, ist der Key der category_key
        if (!isset($ren['category_key']) && is_string($key)) {
            $ren['category_key'] = $key;
        }
        return $ren;
    });
    
    // Nur Sanierungen mit relevanten Zeitfenstern anzeigen
    $relevantRenovations = $normalizedRenovations->filter(function($ren) {
        $timeKey = $ren['time_window_key'] ?? 'nicht';
        return $timeKey !== 'nicht' && $timeKey !== 'weiss_nicht';
    });
@endphp

@if($relevantRenovations->isNotEmpty())

**Ihre Sanierungsangaben:**
@foreach($relevantRenovations as $ren)
@php
    $categoryKey = $ren['category_key'] ?? null;
    $categoryLabel = $categoryKey && isset($calculation->score_details[$categoryKey]) 
        ? $calculation->score_details[$categoryKey]['label'] 
        : ($categoryKey ?? 'Kategorie');
    $timeLabel = $timeWindowLabels[$ren['time_window_key'] ?? 'nicht'] ?? '–';
    $extentPercent = $ren['extent_percent'] ?? 0;
    $extentLabel = $extentLabels[$extentPercent] ?? $extentPercent . ' %';
@endphp
- {{ $categoryLabel }}: {{ $extentLabel }} ({{ $timeLabel }})
@endforeach
@endif

## Angebot
Wir haben auf Basis der Daten ein Angebot für Sie erzeugt.

- Angebotsnummer: {{ $offer->number }}
- Bruttobetrag: {{ $offer->gross_total_eur ? number_format($offer->gross_total_eur, 0, ',', '.') . ' €' : 'auf Anfrage' }}

@component('mail::button', ['url' => $publicUrl])
Angebot ansehen
@endcomponent

Sie können das Angebot dort bei Bedarf bestätigen.

Sollten Sie Fragen haben, antworten Sie einfach auf diese E-Mail – wir helfen gern weiter.

Viele Grüße
Ihr EVALIO-Team
@endcomponent
