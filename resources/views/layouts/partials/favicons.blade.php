@php
	$pngPath = public_path('images/logo.png');
	$pngVer = file_exists($pngPath) ? filemtime($pngPath) : null;
	$faviconPngUrl = asset('images/logo.png') . ($pngVer ? '?v=' . $pngVer : '');

	$icoPath = public_path('favicon.ico');
	$icoVer = file_exists($icoPath) ? filemtime($icoPath) : null;
	$faviconIcoUrl = asset('favicon.ico') . ($icoVer ? '?v=' . $icoVer : '');
@endphp

<link rel="icon" type="image/png" sizes="32x32" href="{{ $faviconPngUrl }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ $faviconPngUrl }}">
<link rel="icon" type="image/x-icon" href="{{ $faviconIcoUrl }}">
<link rel="shortcut icon" href="{{ $faviconIcoUrl }}" type="image/x-icon">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconPngUrl }}">
