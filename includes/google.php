<?php
/**
 * Login dengan Google (OAuth 2.0 / OpenID Connect) memakai cURL murni.
 */

function google_is_configured(): bool
{
    $id = (string) config('google.client_id');
    $secret = (string) config('google.client_secret');
    return $id !== '' && $secret !== '' && !str_contains($id, 'ISI_') && !str_contains($secret, 'ISI_');
}

function google_redirect_uri(): string
{
    $uri = (string) config('google.redirect_uri', '');
    return $uri !== '' ? $uri : url('google-callback.php');
}

function google_auth_url(string $state): string
{
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => config('google.client_id'),
        'redirect_uri'  => google_redirect_uri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
        'access_type'   => 'online',
    ]);
}

function google_http(string $url, ?array $post = null, ?string $bearer = null): array
{
    $headers = ['Accept: application/json'];
    if ($bearer) {
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }
    $body = null;
    $method = 'GET';
    if ($post !== null) {
        $method = 'POST';
        $body = http_build_query($post);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    [$code, $raw] = http_send($method, $url, $headers, $body, 20);
    return [$code, json_decode((string) $raw, true) ?: []];
}

/** Tukar "code" dari Google menjadi data profil (sub, email, name, picture). */
function google_fetch_profile(string $code): array
{
    [$status, $token] = google_http('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => config('google.client_id'),
        'client_secret' => config('google.client_secret'),
        'redirect_uri'  => google_redirect_uri(),
        'grant_type'    => 'authorization_code',
    ]);
    if ($status !== 200 || empty($token['access_token'])) {
        throw new RuntimeException('Google token error: ' . ($token['error_description'] ?? $token['error'] ?? 'HTTP ' . $status));
    }
    [$status, $profile] = google_http('https://openidconnect.googleapis.com/v1/userinfo', null, $token['access_token']);
    if ($status !== 200 || empty($profile['sub']) || empty($profile['email'])) {
        throw new RuntimeException('Could not read your Google profile.');
    }
    if (isset($profile['email_verified']) && !$profile['email_verified']) {
        throw new RuntimeException('Your Google email is not verified.');
    }
    return $profile;
}
