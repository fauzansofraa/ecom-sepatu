<?php
/**
 * Kirim request HTTP ke server lain (Midtrans / Google).
 * Memakai cURL jika aktif. Jika ekstensi cURL mati (sering terjadi di XAMPP),
 * otomatis memakai file_get_contents + stream (butuh ekstensi openssl).
 *
 * @return array{0:int,1:string|false,2:string} [httpCode, rawBody|false, errorMessage]
 */
function http_send(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 30): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return [$code, $raw, $err];
    }

    if (!extension_loaded('openssl')) {
        return [0, false, 'PHP extensions "curl" and "openssl" are disabled. Enable extension=curl in php.ini, then restart Apache.'];
    }

    $opts = [
        'http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'timeout'       => $timeout,
            'ignore_errors' => true, // tetap baca body walau HTTP 4xx/5xx
        ],
    ];
    if ($body !== null) {
        $opts['http']['content'] = $body;
    }
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    $err = $raw === false ? ((error_get_last()['message'] ?? 'Connection failed')) : '';
    return [$code, $raw, $err];
}
