<?php
// Server side of the ALTCHA v3 proof-of-work captcha (widget in /static/js/).
//
// The server picks a random counter, derives key = SHA-256(salt || nonce ||
// uint32_be(counter)) and sends the first half of the key as keyPrefix. The
// browser searches counters from 0 until it finds a key with that prefix,
// which costs it ~counter hashes. The challenge carries
//   keySignature = HMAC(key)              (so a solution is checked with one HMAC)
//   signature    = HMAC(canonical params) (so the challenge can't be altered)
// Mirrors createChallenge/verifySolution in altcha/dist/lib.

const ALTCHA_ALGORITHM = 'SHA-256';
const ALTCHA_TTL = 20 * 60;
const ALTCHA_MIN_COUNTER = 5000;
const ALTCHA_MAX_COUNTER = 40000;

function altcha_keys(): array {
    $secret = getenv('CONTACT_SECRET') ?: '';
    if (strlen($secret) < 32) {
        throw new RuntimeException('CONTACT_SECRET is missing or shorter than 32 characters');
    }
    return [
        'signature' => hash_hmac('sha256', 'altcha-signature', $secret),
        'key' => hash_hmac('sha256', 'altcha-key-signature', $secret),
    ];
}

// Matches JSON.stringify(sortKeys(params)) for the flat ASCII/integer
// parameters used here.
function altcha_canonical(array $params): string {
    ksort($params);
    return json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function altcha_create_challenge(): array {
    $keys = altcha_keys();
    $salt = random_bytes(16);
    $nonce = random_bytes(16);
    $counter = random_int(ALTCHA_MIN_COUNTER, ALTCHA_MAX_COUNTER);
    $key = hash('sha256', $salt . $nonce . pack('N', $counter), true);

    $params = [
        'algorithm' => ALTCHA_ALGORITHM,
        'cost' => 1,
        'expiresAt' => time() + ALTCHA_TTL,
        'keyLength' => 32,
        'keyPrefix' => bin2hex(substr($key, 0, 16)),
        'keySignature' => hash_hmac('sha256', $key, $keys['key']),
        'nonce' => bin2hex($nonce),
        'salt' => bin2hex($salt),
    ];
    ksort($params);
    return [
        'parameters' => $params,
        'signature' => hash_hmac('sha256', altcha_canonical($params), $keys['signature']),
    ];
}

// Returns the challenge signature on success (to be stored against replay),
// or null if the payload is missing, malformed, expired or wrong.
function altcha_verify(string $payload): ?string {
    if ($payload === '' || strlen($payload) > 4096) {
        return null;
    }
    $data = json_decode((string) base64_decode($payload, true), true);
    $params = $data['challenge']['parameters'] ?? null;
    $signature = $data['challenge']['signature'] ?? null;
    $derived = $data['solution']['derivedKey'] ?? null;
    if (!is_array($params) || !is_string($signature) || !is_string($derived)) {
        return null;
    }
    if (($params['algorithm'] ?? null) !== ALTCHA_ALGORITHM
        || !is_int($params['expiresAt'] ?? null) || $params['expiresAt'] < time()
        || !is_string($params['keySignature'] ?? null)
        || !preg_match('/^[0-9a-f]{64}$/', $derived)) {
        return null;
    }
    $keys = altcha_keys();
    try {
        $expected = hash_hmac('sha256', altcha_canonical($params), $keys['signature']);
    } catch (JsonException) {
        return null;
    }
    if (!hash_equals($expected, $signature)) {
        return null;
    }
    if (!hash_equals($params['keySignature'], hash_hmac('sha256', hex2bin($derived), $keys['key']))) {
        return null;
    }
    return $signature;
}
