<?php

declare(strict_types=1);

use App\Config\Config;

/**
 * Configuration globale de l'application (Environnement, CORS, Authentification).
 */
return [
    // Environnement de l'application (production, development, local, test)
    'env' => Config::env('APP_ENV', 'production'),

    // Mode débogage (activer uniquement en local/développement)
    'debug' => (bool) Config::env('APP_DEBUG', false),

    // Clé API secrète pour sécuriser les requêtes POST
    'api_key' => (string) Config::env('API_KEY', ''),

    // Liste des origines autorisées pour les requêtes Cross-Origin (CORS)
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) Config::env('ALLOWED_ORIGINS', 'http://localhost:3000')))
    ),

    // Chemin du fichier de journalisation des erreurs
    'error_log' => dirname(__DIR__) . '/php_errors.log',
];
