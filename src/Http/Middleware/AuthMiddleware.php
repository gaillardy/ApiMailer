<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Config\Config;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware d'authentification par clé API (protection contre les attaques temporelles).
 */
class AuthMiddleware
{
    private string $expectedApiKey;

    public function __construct(?string $expectedApiKey = null)
    {
        $this->expectedApiKey = $expectedApiKey ?? (string) Config::get('app.api_key', '');
    }

    /**
     * Valide l'authentification de la requête.
     *
     * @param Request $request
     * @return Response|null Retourne une réponse d'erreur 401/500 ou null si l'authentification est valide
     */
    public function handle(Request $request): ?Response
    {
        if ($this->expectedApiKey === '') {
            return Response::error('La clé API n\'est pas configurée sur le serveur.', 500);
        }

        $providedApiKey = $request->getApiKey();

        if ($providedApiKey === null || $providedApiKey === '') {
            return Response::error('Accès non autorisé : clé API manquante (en-tête X-API-KEY ou Authorization: Bearer).', 401);
        }

        // Comparaison à temps constant pour neutraliser les attaques temporelles (Timing Attacks)
        if (!hash_equals($this->expectedApiKey, $providedApiKey)) {
            return Response::error('Accès non autorisé : clé API invalide.', 401);
        }

        return null;
    }
}
