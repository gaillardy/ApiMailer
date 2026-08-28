<?php

declare(strict_types=1);

namespace App\Validation;

use App\Config\Config;

/**
 * Moteur de validation dynamique et extensible pour les données de requêtes.
 * Permet aux développeurs de définir facilement les champs requis, optionnels et personnalisés.
 */
class Validator
{
    private array $fieldsConfig;
    private bool $allowExtraFields;
    private ?string $honeypotField;
    private array $errors = [];
    private array $validatedData = [];
    private bool $isSpamDetected = false;

    /**
     * @param array|null $fieldsConfig Configuration personnalisée des champs (sinon chargée depuis config/mailer.php)
     * @param bool|null $allowExtraFields Autoriser ou non les champs non déclarés
     * @param string|null $honeypotField Nom du champ piège anti-spam
     */
    public function __construct(
        ?array $fieldsConfig = null,
        ?bool $allowExtraFields = null,
        ?string $honeypotField = null
    ) {
        $this->fieldsConfig = $fieldsConfig ?? Config::get('mailer.fields', []);
        $this->allowExtraFields = $allowExtraFields ?? (bool) Config::get('mailer.allow_extra_fields', true);
        $this->honeypotField = $honeypotField ?? (string) Config::get('mailer.honeypot_field', '_gotcha');
    }

    /**
     * Valide un jeu de données selon les règles définies.
     *
     * @param array $data Données brutes reçues
     * @return bool True si toutes les données sont valides, False sinon
     */
    public function validate(array $data): bool
    {
        $this->errors = [];
        $this->validatedData = [];
        $this->isSpamDetected = false;

        // 1. Vérification du champ Honeypot (protection anti-spam)
        if ($this->honeypotField !== '' && !empty($data[$this->honeypotField])) {
            $this->isSpamDetected = true;
            // On valide formellement sans enregistrer d'erreur pour ne pas alerter le bot
            return true;
        }

        // 2. Validation des champs configurés
        foreach ($this->fieldsConfig as $fieldName => $fieldMeta) {
            $label = $fieldMeta['label'] ?? $fieldName;
            $rules = $fieldMeta['rules'] ?? [];
            $value = $data[$fieldName] ?? null;

            $this->validateField($fieldName, $value, $rules, $label);
        }

        // 3. Traitement des champs additionnels (extra fields)
        if ($this->allowExtraFields) {
            foreach ($data as $key => $value) {
                if ($key === $this->honeypotField) {
                    continue;
                }
                if (!isset($this->fieldsConfig[$key])) {
                    // Nettoyage et assainissement basique des champs non configurés
                    if (is_string($value)) {
                        $this->validatedData[$key] = trim($value);
                    } elseif (is_scalar($value)) {
                        $this->validatedData[$key] = $value;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Valide les données et lève une exception en cas d'erreur.
     *
     * @throws ValidationException
     */
    public function validateOrFail(array $data): array
    {
        if (!$this->validate($data)) {
            $firstErrorMessage = $this->getFirstError() ?? 'Erreur de validation des données.';
            throw new ValidationException($firstErrorMessage, $this->errors);
        }

        return $this->validatedData;
    }

    /**
     * Valide un champ unitaire contre sa liste de règles.
     */
    private function validateField(string $fieldName, mixed $value, array $rules, string $label): void
    {
        $isRequired = in_array('required', $rules, true);
        $isOptional = in_array('optional', $rules, true) || in_array('nullable', $rules, true);

        // Vérification de présence pour champ obligatoire
        $isEmpty = ($value === null || $value === '' || (is_string($value) && trim($value) === ''));

        if ($isRequired && $isEmpty) {
            $this->addError($fieldName, "Le champ \"{$label}\" est obligatoire.");
            return;
        }

        // Si le champ est vide et optionnel, on s'arrête ici
        if ($isEmpty && !$isRequired) {
            $this->validatedData[$fieldName] = '';
            return;
        }

        // Nettoyage de la valeur chaîne
        $sanitizedValue = is_string($value) ? trim($value) : $value;

        // Application des règles individuelles
        foreach ($rules as $rule) {
            if ($rule === 'required' || $rule === 'optional' || $rule === 'nullable') {
                continue;
            }

            if ($rule === 'email') {
                if (!is_string($sanitizedValue) || !filter_var($sanitizedValue, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($fieldName, "Le champ \"{$label}\" doit être une adresse email valide.");
                }
            } elseif ($rule === 'string') {
                if (!is_string($sanitizedValue)) {
                    $this->addError($fieldName, "Le champ \"{$label}\" doit être une chaîne de caractères.");
                }
            } elseif ($rule === 'numeric') {
                if (!is_numeric($sanitizedValue)) {
                    $this->addError($fieldName, "Le champ \"{$label}\" doit être une valeur numérique.");
                }
            } elseif ($rule === 'phone') {
                if (!is_string($sanitizedValue) || !preg_match('/^[0-9+\(\)\#\.\s\/-]{6,30}$/', $sanitizedValue)) {
                    $this->addError($fieldName, "Le champ \"{$label}\" doit être un numéro de téléphone valide.");
                }
            } elseif (str_starts_with($rule, 'min:')) {
                $min = (int) substr($rule, 4);
                $length = is_string($sanitizedValue) ? mb_strlen($sanitizedValue, 'UTF-8') : (float) $sanitizedValue;
                if ($length < $min) {
                    $this->addError($fieldName, "Le champ \"{$label}\" doit contenir au minimum {$min} caractères.");
                }
            } elseif (str_starts_with($rule, 'max:')) {
                $max = (int) substr($rule, 4);
                $length = is_string($sanitizedValue) ? mb_strlen($sanitizedValue, 'UTF-8') : (float) $sanitizedValue;
                if ($length > $max) {
                    $this->addError($fieldName, "Le champ \"{$label}\" ne peut pas dépasser {$max} caractères.");
                }
            } elseif (str_starts_with($rule, 'regex:')) {
                $pattern = substr($rule, 6);
                if (!is_string($sanitizedValue) || !preg_match($pattern, $sanitizedValue)) {
                    $this->addError($fieldName, "Le format du champ \"{$label}\" est invalide.");
                }
            }
        }

        $this->validatedData[$fieldName] = $sanitizedValue;
    }

    /**
     * Ajoute une règle pour un champ donné.
     *
     * @param string $fieldName Nom du champ
     * @param string $label Libellé lisible
     * @param array $rules Liste des règles
     */
    public function setFieldRule(string $fieldName, string $label, array $rules): self
    {
        $this->fieldsConfig[$fieldName] = [
            'label' => $label,
            'rules' => $rules,
        ];
        return $this;
    }

    /**
     * Supprime la configuration d'un champ.
     */
    public function removeField(string $fieldName): self
    {
        unset($this->fieldsConfig[$fieldName]);
        return $this;
    }

    /**
     * Récupère la liste des erreurs accumulées.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Récupère la première erreur rencontrée.
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        $firstFieldErrors = reset($this->errors);
        return is_array($firstFieldErrors) ? ($firstFieldErrors[0] ?? null) : null;
    }

    /**
     * Récupère les données validées et assainies.
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Indique si un spam a été détecté via le champ honeypot.
     */
    public function isSpam(): bool
    {
        return $this->isSpamDetected;
    }

    /**
     * Ajoute une erreur pour un champ spécifique.
     */
    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
