<?php
// php-app/includes/jwt.php

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret, int $expirySeconds = 86400): string {
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $now = time();
    $payload['iat'] = $payload['iat'] ?? $now;
    $payload['exp'] = $payload['exp'] ?? ($now + $expirySeconds);

    $hEncoded = base64url_encode(json_encode($header));
    $pEncoded = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$hEncoded.$pEncoded", $secret, true);
    $sEncoded = base64url_encode($signature);

    return "$hEncoded.$pEncoded.$sEncoded";
}

function jwt_decode(string $token, string $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$hEncoded, $pEncoded, $sEncoded] = $parts;

    $signature = base64url_decode($sEncoded);
    $validSignature = hash_hmac('sha256', "$hEncoded.$pEncoded", $secret, true);

    if (!hash_equals($validSignature, $signature)) {
        return null;
    }

    $payload = json_decode(base64url_decode($pEncoded), true);
    if (!$payload || !is_array($payload)) {
        return null;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}
