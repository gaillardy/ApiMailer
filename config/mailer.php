<?php

declare(strict_types=1);

use App\Config\Config;

/**
 * Configuration du service d'envoi d'emails et des règles de validation des formulaires.
 */
return [
    // Paramètres du serveur SMTP
    'smtp' => [
        'host' => (string) Config::env('SMTP_HOST', 'mail.exemple.com'),
        'port' => (int) Config::env('SMTP_PORT', 465),
        'auth' => (bool) Config::env('SMTP_AUTH', true),
        'username' => (string) Config::env('SMTP_USERNAME', 'contact@exemple.com'),
        'password' => (string) Config::env('SMTP_PASSWORD', ''),
        'encryption' => (string) Config::env('SMTP_ENCRYPTION', 'ssl'), // 'ssl' (port 465) ou 'tls' (port 587)
        'debug' => (int) Config::env('SMTP_DEBUG', 0),
        'timeout' => 15, // Délai d'attente en secondes
    ],

    // Expéditeur et destinataire par défaut
    'from' => [
        'address' => (string) Config::env('MAIL_FROM_ADDRESS', 'contact@exemple.com'),
        'name' => (string) Config::env('MAIL_FROM_NAME', 'Formulaire de Contact'),
    ],
    'to' => [
        'address' => (string) Config::env('MAIL_TO_ADDRESS', 'contact@exemple.com'),
        'name' => (string) Config::env('MAIL_TO_NAME', 'Administrateur'),
    ],
    'subject_prefix' => (string) Config::env('MAIL_SUBJECT_PREFIX', '[Contact Web]'),

    // Champ anti-spam (Honeypot) : s'il est renseigné, la requête est ignorée sans erreur apparente
    'honeypot_field' => (string) Config::env('HONEYPOT_FIELD', '_gotcha'),

    /**
     * Définition des champs du formulaire et de leurs règles de validation.
     * 
     * Vous pouvez facilement ajouter, modifier ou retirer des champs ici.
     * Règles disponibles : 'required', 'optional', 'email', 'string', 'numeric', 'phone', 'min:X', 'max:Y'
     */
    'fields' => [
        'fullName' => [
            'label' => 'Nom et prénom',
            'rules' => ['required', 'string', 'min:2', 'max:100'],
        ],
        'email' => [
            'label' => 'Adresse email',
            'rules' => ['required', 'email', 'max:255'],
        ],
        'phone' => [
            'label' => 'Numéro de téléphone',
            'rules' => ['required', 'string', 'max:30'],
        ],
        'subject' => [
            'label' => 'Sujet du message',
            'rules' => ['optional', 'string', 'max:150'],
        ],
        'message' => [
            'label' => 'Message',
            'rules' => ['required', 'string', 'min:5', 'max:5000'],
        ],
    ],

    // Autoriser ou bloquer les champs additionnels non explicitement configurés ci-dessus
    'allow_extra_fields' => true,
];
