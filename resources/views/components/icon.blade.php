@props(['name'=>'grid'])
@php
$paths = [
'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
'bag'=>'<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
'box'=>'<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5"/>',
'people'=>'<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 4v3"/>',
'staff'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
'history'=>'<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 7h6M9 11h6M9 15h3"/>',
'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
'plus'=>'<path d="M12 5v14M5 12h14"/>',
'search'=>'<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
'logout'=>'<path d="M10 4H4v16h6M8 12h13m-4-4 4 4-4 4"/>',
'check'=>'<path d="m5 12 4 4L19 6"/>',
'chevron'=>'<path d="m9 5 7 7-7 7"/>',
'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
'print'=>'<path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6z"/>',
'lock'=>'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['grid'] !!}</svg>
