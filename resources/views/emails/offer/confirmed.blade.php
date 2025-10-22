@component('mail::message')
# Vielen Dank für Ihre Bestätigung

@if(($contact['name'] ?? null))
Hallo {{ $contact['name'] }},

@endif
wir haben Ihre Angebotsbestätigung erhalten. Gerne begleiten wir Sie auf dem weiteren Weg zum Immobiliengutachten.

@component('mail::panel')
**Angebotsnummer:** {{ $offer->number }}  
**Erstellt am:** {{ optional($offer->created_at)->format('d.m.Y H:i') }}  
**Aktueller Status:** {{ $offer->status ?? 'bestätigt' }}
@endcomponent


@component('mail::button', ['url' => $publicUrl])
Angebot online einsehen
@endcomponent

Sollten Sie Fragen haben oder weitere Unterlagen benötigen, antworten Sie einfach auf diese E-Mail oder rufen Sie uns an.

Beste Grüße  
Ihr Evalio-Team
@endcomponent
