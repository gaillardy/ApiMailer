<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Config\Config;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware pour la gestion sécurisée des requêtes Cross-Origin Resource Sharing (CORS).
 */
class CorsMiddleware
{
    private array $allowedOrigins;

    public function __construct(?array $allowedOrigins = null)
    {
        $origins = $allowedOrigins ?? Config::get('app.allowed_origins', []);
        // Nettoyage et suppression des éventuels slashes de fin
        $this->allowedOrigins = array_map(fn($url) => rtrim(trim($url), '/'), $origins);
    }

    /**
     * Traite les en-têtes CORS pour la requête courante.
     *
     * @param Request $request
     * @param Response|null $response
     * @return Response|null Réponse immédiate pour OPTIONS (Preflight) ou null pour continuer
     */
    public function handle(Request $request, ?Response &$response = null): ?Response
    {
        $origin = rtrim(trim($request->getOrigin()), '/');
        $isAllowed = $this->isOriginAllowed($origin);

        $corsHeaders = [
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-API-KEY, X-Requested-With',
            'Access-Control-Max-Age' => '86400', // Cache Preflight 24h
        ];

        if ($isAllowed && $origin !== '') {
            $corsHeaders['Access-Control-Allow-Origin'] = $origin;
            $corsHeaders['Access-Control-Allow-Credentials'] = 'true';
            $corsHeaders['Vary'] = 'Origin';
        }

        // Si c'est une requête preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            if (!$isAllowed && $origin !== '') {
                return Response::error('Origine non autorisée par la politique CORS', 403, []);
            }
            return Response::noContent(204, $corsHeaders);
        }

        // Si une réponse existe déjà, lui injecter les en-têtes CORS
        if ($response !== null) {
            foreach ($corsHeaders as $key => $value) {
                $response->withHeader($key, $value);
            }
        }

        return null;
    }

    /**
     * Vérifie si l'origine transmise est autorisée.
     */
    public function isOriginAllowed(string $origin): bool
    {
        if ($origin === '') {
            // Requête même origine ou outil backend direct (ex: cURL, Postman)
            return true;
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }

    /**
     * Récupère la liste des origines autorisées configurées.
     */
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }
}
