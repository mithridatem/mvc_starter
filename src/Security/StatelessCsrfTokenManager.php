<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Double-submit cookie signé : aucun token CSRF n'est stocké en session.
 *
 * La sécurité repose sur une clé HMAC serveur et un cookie aléatoire propre
 * au navigateur. En production, utiliser exclusivement HTTPS et __Host-.
 */
final class StatelessCsrfTokenManager implements CsrfTokenManagerInterface
{
    private const SECURE_COOKIE = '__Host-mvc_csrf';
    private const DEV_COOKIE = 'mvc_csrf_dev';
    private ?string $requestNonce = null;

    /**
     * @param string $secret Clé HMAC propre à l'application (32 caractères minimum).
     * @param bool $secureCookie true pour HTTPS, obligatoire en production.
     * @param int $ttl Durée de validité d'un token en secondes.
     */
    public function __construct(
        private string $secret,
        private bool $secureCookie = true,
        private int $ttl = 3600
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('CSRF_SECRET doit contenir au moins 32 caractères.');
        }
        if ($ttl < 1) {
            throw new \InvalidArgumentException('La durée CSRF doit être positive.');
        }
    }

    public function generate(string $formId): string
    {
        $nonce = $this->getOrCreateNonce();
        $issuedAt = time();
        return $issuedAt . '.' . $this->signature($formId, $issuedAt, $nonce);
    }

    public function isValid(string $formId, mixed $token): bool
    {
        if (!is_string($token) || !preg_match('/^([0-9]{10,11})\.([a-f0-9]{64})$/D', $token, $parts)) {
            return false;
        }

        $issuedAt = (int) $parts[1];
        $now = time();
        if ($issuedAt > $now || $issuedAt < $now - $this->ttl) {
            return false;
        }

        // Un cookie manquant ne doit jamais être créé pendant la validation.
        $nonce = $_COOKIE[$this->cookieName()] ?? null;
        if (!is_string($nonce) || !preg_match('/^[a-f0-9]{64}$/D', $nonce)) {
            return false;
        }

        return hash_equals($this->signature($formId, $issuedAt, $nonce), $parts[2]);
    }

    private function getOrCreateNonce(): string
    {
        if ($this->requestNonce !== null) {
            return $this->requestNonce;
        }

        $stored = $_COOKIE[$this->cookieName()] ?? null;
        if (is_string($stored) && preg_match('/^[a-f0-9]{64}$/D', $stored)) {
            return $this->requestNonce = $stored;
        }

        if (headers_sent()) {
            throw new \LogicException('Impossible de transmettre le cookie CSRF après les en-têtes HTTP.');
        }

        $nonce = bin2hex(random_bytes(32));
        $ok = setcookie($this->cookieName(), $nonce, [
            'expires' => 0,
            'path' => '/',
            'secure' => $this->secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!$ok) {
            throw new \RuntimeException('Impossible de créer le cookie CSRF.');
        }
        return $this->requestNonce = $nonce;
    }

    private function signature(string $formId, int $issuedAt, string $nonce): string
    {
        return hash_hmac('sha256', "csrf-v1\0" . $formId . "\0" . $issuedAt . "\0" . $nonce, $this->secret);
    }

    private function cookieName(): string
    {
        return $this->secureCookie ? self::SECURE_COOKIE : self::DEV_COOKIE;
    }
}
