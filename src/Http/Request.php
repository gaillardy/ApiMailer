<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Encapsule la requête HTTP entrante (méthode, en-têtes, données JSON).
 */
class Request
{
    private string $method;
    private array $headers;
    private array $body;
    private string $rawBody;
    private ?string $jsonError = null;

    /**
     * @param string|null $method Méthode HTTP (POST, GET, OPTIONS, etc.)
     * @param array|null $headers Tableau des en-têtes HTTP
     * @param array|null $body Corps de requête sous forme de tableau (ou null pour lire php://input)
     * @param string|null $rawBody Corps brut de la requête
     */
    public function __construct(
        ?string $method = null,
        ?array $headers = null,
        ?array $body = null,
        ?string $rawBody = null
    ) {
        $this->method = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->headers = $headers ?? $this->captureHeaders();

        if ($body !== null) {
            $this->body = $body;
            $this->rawBody = $rawBody ?? json_encode($body);
        } else {
            $this->rawBody = $rawBody ?? (file_get_contents('php://input') ?: '');
            $this->body = $this->parseJsonBody($this->rawBody);
        }
    }

    /**
     * Crée une instance Request à partir des superglobales courantes.
     */
    public static function createFromGlobals(): self
    {
        return new self();
    }

    /**
     * Récupère la méthode HTTP de la requête.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Vérifie si la méthode correspond à celle spécifiée.
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    /**
     * Récupère la valeur d'un en-tête insensible à la casse.
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }
        return $default;
    }

    /**
     * Récupère tous les en-têtes.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Récupère l'origine de la requête HTTP (Header Origin).
     */
    public function getOrigin(): string
    {
        return $this->getHeader('Origin') ?? '';
    }

    /**
     * Récupère la clé API transmise soit via X-API-KEY, soit via Authorization (Bearer token).
     */
    public function getApiKey(): ?string
    {
        $apiKey = $this->getHeader('X-API-KEY');
        if ($apiKey !== null && trim($apiKey) !== '') {
            return trim($apiKey);
        }

        $authHeader = $this->getHeader('Authorization');
        if ($authHeader !== null && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Récupère une valeur du corps JSON.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Récupère l'ensemble des données reçues sous forme de tableau associatif.
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Récupère le corps brut de la requête.
     */
    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Indique si le corps JSON est valide.
     */
    public function hasValidJson(): bool
    {
        return $this->jsonError === null;
    }

    /**
     * Récupère l'erreur de décodage JSON s'il y en a une.
     */
    public function getJsonError(): ?string
    {
        return $this->jsonError;
    }

    /**
     * Analyse et décode le corps JSON en tableau associatif PHP.
     */
    private function parseJsonBody(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonError = json_last_error_msg();
            return [];
        }

        if (!is_array($decoded)) {
            $this->jsonError = 'Le corps JSON doit être un objet.';
            return [];
        }

        return $decoded;
    }

    /**
     * Récupère et normalise les en-têtes HTTP de l'environnement PHP.
     */
    private function captureHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                return $headers;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'AUTHORIZATION'], true)) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }
}
