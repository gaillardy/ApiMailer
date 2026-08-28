<?php

declare(strict_types=1);

/**
 * Point d'entrée principal de l'API Mailer.
 */

// Chargement de l'autoloader Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Controllers\ContactController;

// Initialisation de la configuration et des variables d'environnement
Config::load(dirname(__DIR__));

// Configuration des erreurs PHP
$isDebug = (bool) Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', (string) Config::get('app.error_log', dirname(__DIR__) . '/php_errors.log'));

// Traitement de la requête et émission de la réponse
$controller = new ContactController();
$response = $controller->handle();
$response->send();
