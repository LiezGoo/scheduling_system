@php
	$pngPath = public_path('images/logo.png');
	$pngVer = file_exists($pngPath) ? filemtime($pngPath) : time();
	$faviconUrl = asset('images/logo.png') . '?v=' . $pngVer;
@endphp

<link rel="icon" type="image/png" sizes="32x32" href="{{ $faviconUrl }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconUrl }}">
