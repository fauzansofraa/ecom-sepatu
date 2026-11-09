<?php
/** Ikon SVG inline (garis 1.75px, mengikuti desain). */
function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = [
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>',
        'bag'     => '<path d="M5 8h14l-1 13H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'heart'   => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
        'truck'   => '<path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/>',
        'returns' => '<path d="M4 12a8 8 0 1 0 2.5-5.8"/><path d="M4 4v4h4"/>',
        'shield'  => '<path d="M12 3 5 6v6c0 4.2 3 7.6 7 9 4-1.4 7-4.8 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'lock'    => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'chevron-left'  => '<path d="m14 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m10 6 6 6-6 6"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'logout'  => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'card'    => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
        'wallet'  => '<path d="M4 7h15a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7Z"/><path d="M4 7V6a2 2 0 0 1 2-2h11"/><circle cx="16" cy="13" r="1.2"/>',
        'bank'    => '<path d="M3 9 12 4l9 5"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="icon ' . $class . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function star_icon(int $size = 14): string
{
    return '<svg class="star" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.8l2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.2l-5.7 3.1 1.2-6.4-4.7-4.4 6.4-.8z"/></svg>';
}

function google_logo(int $size = 18): string
{
    return '<svg width="' . $size . '" height="' . $size . '" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09c.95-2.85 3.6-4.96 6.73-4.96z"/></svg>';
}
