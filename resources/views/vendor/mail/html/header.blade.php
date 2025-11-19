@props(['url'])

@php
	$brandName = strtoupper(config('app.name'));
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
	<span style="font-size: 18px; font-weight: 600; color: #c9a646; letter-spacing: 0.05em;">{{ $brandName }}</span>
</a>
</td>
</tr>
