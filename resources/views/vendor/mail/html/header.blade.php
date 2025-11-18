@props(['url'])

@php
	$brandName = strtoupper(config('app.name'));
	$baseUrl = config('app.url');
	$defaultHost = 'https://ersteinschaetzung.evalio-nutzungsdauer.de';
	if (blank($baseUrl) || str_contains($baseUrl, 'localhost')) {
		$baseUrl = $defaultHost;
	}
	$logoUrl = rtrim($baseUrl, '/') . '/images/logos/logo.png';
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-flex; align-items: center; text-decoration: none; gap: 10px;">
	<img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height: 28px; width: auto; max-width: 140px; display: block;">
	<span style="font-size: 18px; font-weight: 600; color: #c9a646; letter-spacing: 0.05em;">{{ $brandName }}</span>
</a>
</td>
</tr>
