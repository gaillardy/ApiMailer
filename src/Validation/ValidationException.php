<?php

declare(strict_types=1);

namespace App\Validation;

use Exception;

/**
 * Exception levée lors d'un échec de validation des données d'un formulaire.
 */
class ValidationException extends Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 400, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Récupère le dictionnaire des erreurs de validation par champ.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
