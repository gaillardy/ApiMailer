<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contrat d'interface pour les services d'envoi d'emails.
 * Permet l'injection de dépendance et la simulation (mock) lors des tests unitaires.
 */
interface MailerServiceInterface
{
    /**
     * Envoie un email de contact formaté avec les données transmises.
     *
     * @param array $data Données validées du formulaire
     * @param string|null $customSubject Sujet spécifique éventuel
     * @return bool True si l'envoi a réussi, False sinon
     */
    public function send(array $data, ?string $customSubject = null): bool;

    /**
     * Récupère le dernier message d'erreur technique rencontré.
     */
    public function getLastError(): ?string;
}
