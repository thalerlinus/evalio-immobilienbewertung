@component('mail::message')
# Neues bestätigtes Angebot

Ein Kunde hat ein Angebot bestätigt, bei dem das Gutachten als sinnvoll eingestuft wurde.

@component('mail::panel')
**Angebotsnummer:** {{ $offer->number }}  
**Bestätigt am:** {{ optional($offer->accepted_at)->format('d.m.Y H:i') }}  
**Status:** {{ $offer->status ?? 'accepted' }}
@endcomponent

@component('mail::table')
| Feld | Wert |
| :--- | :---- |
| Name | {{ $contact['name'] ?? 'nicht angegeben' }} |
| E-Mail | {{ $contact['email'] ?? 'nicht angegeben' }} |
| Telefon | {{ $contact['phone'] ?? 'nicht angegeben' }} |
| Rechnungsstraße | {{ optional($offer->customer)->billing_street ?? 'nicht angegeben' }} |
| Rechnungs-PLZ | {{ optional($offer->customer)->billing_zip ?? 'nicht angegeben' }} |
| Rechnungsort | {{ optional($offer->customer)->billing_city ?? 'nicht angegeben' }} |
| Immobilienart | {{ $offer->calculation?->propertyType?->label ?? 'k.A.' }} |
| Empfehlung | {{ $offer->calculation?->recommendation ?? 'k.A.' }} |
@endcomponent

@component('mail::button', ['url' => $publicUrl])
Angebot öffnen
@endcomponent

Viele Grüße  
Ihr Evalio-System
@endcomponent
