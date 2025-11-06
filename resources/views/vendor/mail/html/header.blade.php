@props(['url'])

@php
	$brandName = strtoupper(config('app.name'));
	$logoUrl = asset('images/logos/logo.png');
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-flex; align-items: center; text-decoration: none;">	
	<span style="font-size: 18px; font-weight: 600; color: #0f172a; letter-spacing: 0.05em;">{{ $brandName }}</span>
</a>
</td>
</tr>
