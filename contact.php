<?php

declare(strict_types=1);

/**
 * Fichier de compatibilité descendante.
 * Redirige l'exécution vers l'architecture modulaire ContactController.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Config;
use App\Controllers\ContactController;

// Initialisation de la configuration
Config::load(__DIR__);

// Traitement de la requête
$controller = new ContactController();
$response = $controller->handle();
$response->send();