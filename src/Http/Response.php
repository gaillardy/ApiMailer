<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Gère les réponses HTTP JSON de l'API.
 */
class Response
{
    private int $statusCode;
    private array $data;
    private array $headers;

    public function __construct(array $data = [], int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ], $headers);
    }

    /**
     * Crée une réponse JSON de succès.
     *
     * @param string $message Message descriptif
     * @param array $data Données additionnelles éventuelles
     * @param int $status Code HTTP (défaut 200)
     */
    public static function success(string $message, array $data = [], int $status = 200): self
    {
        $payload = array_merge(['success' => true, 'message' => $message], $data);
        return new self($payload, $status);
    }

    /**
     * Crée une réponse JSON d'erreur.
     *
     * @param string $message Message d'erreur
     * @param int $status Code HTTP (défaut 400)
     * @param array $errors Détail des erreurs de validation ou champs
     */
    public static function error(string $message, int $status = 400, array $errors = []): self
    {
        $payload = ['success' => false, 'error' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        return new self($payload, $status);
    }

    /**
     * Crée une réponse vide (utile pour les requêtes OPTIONS 204).
     */
    public static function noContent(int $status = 204, array $headers = []): self
    {
        return new self([], $status, $headers);
    }

    /**
     * Ajoute un en-tête à la réponse.
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Récupère le code de statut HTTP.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Récupère le tableau des données de la réponse.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Récupère les en-têtes configurés.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Envoie la réponse au client (en-têtes HTTP et corps JSON).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        if ($this->statusCode !== 204 && (!empty($this->data) || $this->statusCode !== 200)) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
