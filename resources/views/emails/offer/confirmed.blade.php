@component('mail::message')
# Vielen Dank für Ihre Bestätigung

@if(($contact['name'] ?? null))
Hallo {{ $contact['name'] }},

@endif
vielen Dank für Ihre Beauftragung. Gerne begleiten wir Sie auf dem weiteren Weg zu Ihrem Restnutzungsdauer-Gutachten.

@php
	$billing = optional($offer->customer);
	$statusMap = [
		'accepted' => 'bestätigt',
		'confirmed' => 'bestätigt',
		'sent' => 'versendet',
		'pending' => 'offen',
		'draft' => 'Entwurf',
	];
	$statusLabel = $statusMap[$offer->status] ?? ($offer->status ? ucfirst($offer->status) : 'bestätigt');
	$billingAddress = trim(collect([
		$billing->billing_street,
		trim(trim(($billing->billing_zip ?? '') . ' ' . ($billing->billing_city ?? '')))
	])->filter()->implode(', '));
@endphp

@component('mail::panel')
**Angebotsnummer:** {{ $offer->number }}  
**Erstellt am:** {{ optional($offer->created_at)->format('d.m.Y H:i') }}  
**Aktueller Status:** {{ $statusLabel }}  
@if($billing->billing_name)
**Rechnungsempfänger:** {{ $billing->billing_name }}  
@endif
@if($billing->billing_company)
**Firma:** {{ $billing->billing_company }}  
@endif
@if($billingAddress)
**Rechnungsadresse:** {{ $billingAddress }}  
@endif
@if($billing->billing_email)
**Rechnungs-E-Mail:** {{ $billing->billing_email }}  
@endif
@endcomponent

@component('mail::button', ['url' => $publicUrl])
Angebot online einsehen
@endcomponent

## Benötigte Unterlagen
- Grundrisse / Baupläne
- Energieausweis (falls vorhanden)
- Lageplan (optional, falls vorhanden)
- Mitteilung über bekannte Schäden oder Defekte
- Sonstige Unterlagen (optional): z. B. Exposé, Baubeschreibungen, Protokolle der Eigentümerversammlungen, Schadensberichte etc.

## Fotos (nur notwendig, wenn keine Video- / Vor-Ort-Besichtigung)
- Außenansicht vorne (gesamtes Gebäude von der Straße aus)
- Außenansicht hinten (z. B. vom Garten oder Hinterhof)
- Treppenhaus (falls vorhanden)
- Dach innen (falls nicht ausgebaut und zugänglich)
- Dach außen (falls möglich, z. B. durch ein Fenster oder aus Straßenperspektive)
- Heizungsanlage
- Schäden bzw. Abnutzungen (falls vorhanden)

> **Hinweis:** Wenn eine Online- oder Vor-Ort-Besichtigung gebucht wurde, können Sie diesen Foto-Abschnitt ignorieren.

## So reichen Sie Ihre Unterlagen ein
Bitte senden Sie uns die Dokumente einfach als Antwort auf diese E-Mail oder an [info@evalio-nutzungsdauer.de](mailto:info@evalio-nutzungsdauer.de) – idealerweise unter Angabe Ihrer Angebotsnummer zur besseren Zuordnung.

Sie können die Dateien ebenfalls per WeTransfer, Google Drive, Dropbox oder einem ähnlichen Dienst bereitstellen. Reichen Sie die Unterlagen möglichst zeitnah nach. Sollten bestimmte Dokumente nicht vorliegen (z. B. keine Grundrisse vorhanden), geben Sie uns bitte kurz Bescheid – unser Sachverständiger prüft dann, ob das Gutachten dennoch finalisiert werden kann.

Bei Fragen sind wir jederzeit gerne für Sie da.

Beste Grüße  
Ihr EVALIO-Team
@endcomponent
