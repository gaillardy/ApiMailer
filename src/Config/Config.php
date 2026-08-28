<?php

declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

/**
 * Gestionnaire de configuration de l'application.
 * Charge les variables d'environnement (.env) et fournit un accès unifié aux paramètres.
 */
class Config
{
    private static bool $loaded = false;
    private static array $items = [];

    /**
     * Initialise la configuration en chargeant le fichier .env et les configurations PHP.
     *
     * @param string|null $basePath Chemin racine du projet
     */
    public static function load(?string $basePath = null): void
    {
        if (self::$loaded && $basePath === null) {
            return;
        }

        $root = $basePath ?? dirname(__DIR__, 2);

        // Chargement sécurisé du fichier .env s'il existe
        if (file_exists($root . '/.env')) {
            $dotenv = Dotenv::createImmutable($root);
            $dotenv->safeLoad();
        }

        // Chargement des fichiers de configuration
        self::$items = [
            'app' => self::loadFileIfExists($root . '/config/app.php'),
            'mailer' => self::loadFileIfExists($root . '/config/mailer.php'),
        ];

        self::$loaded = true;
    }

    /**
     * Récupère une valeur de configuration via une clé (supporte la notation pointée, ex: 'mailer.smtp.host').
     *
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        $segments = explode('.', $key);
        $data = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * Définit manuellement une valeur de configuration (particulièrement utile pour les tests unitaires).
     *
     * @param string $key Clé de configuration
     * @param mixed $value Valeur à assigner
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $segments = explode('.', $key);
        $current = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * Récupère une variable d'environnement avec valeur de secours.
     *
     * @param string $key Nom de la variable d'environnement
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        // Conversion des chaînes booléennes et spéciales
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }

    /**
     * Réinitialise la configuration (utile pour isoler les tests).
     */
    public static function reset(): void
    {
        self::$loaded = false;
        self::$items = [];
    }

    /**
     * Charge un fichier PHP de configuration s'il existe.
     *
     * @param string $filePath Chemin du fichier
     * @return array
     */
    private static function loadFileIfExists(string $filePath): array
    {
        if (file_exists($filePath)) {
            $config = require $filePath;
            if (is_array($config)) {
                return $config;
            }
        }
        return [];
    }
}
