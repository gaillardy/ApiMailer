<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;

/**
 * Générateur de gabarits d'emails HTML et Texte sécurisés.
 * Échappe systématiquement les données utilisateurs pour contrer les injections HTML et XSS.
 */
class EmailTemplate
{
    private array $fieldsConfig;

    public function __construct(?array $fieldsConfig = null)
    {
        $this->fieldsConfig = $fieldsConfig ?? Config::get('mailer.fields', []);
    }

    /**
     * Génère le corps de l'email au format HTML avec un design responsive et soigné.
     *
     * @param array $data Données du formulaire
     * @return string Contenu HTML sécurisé
     */
    public function renderHtml(array $data): string
    {
        $rowsHtml = '';

        // Champs séparés pour le corps principal du message
        $messageContent = '';
        if (isset($data['message'])) {
            $messageContent = nl2br($this->escape((string) $data['message']));
        }

        foreach ($data as $key => $value) {
            // On traite le champ "message" séparément en bas du template
            if ($key === 'message') {
                continue;
            }

            $label = $this->getFieldLabel($key);
            $safeValue = $this->formatFieldValue($key, $value);

            $rowsHtml .= <<<HTML
            <tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #374151; width: 35%; background-color: #f9fafb;">
                    {$label}
                </td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #e5e7eb; color: #111827;">
                    {$safeValue}
                </td>
            </tr>
HTML;
        }

        $messageSection = '';
        if ($messageContent !== '') {
            $messageSection = <<<HTML
            <div style="margin-top: 24px; padding: 16px; background-color: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px;">
                <h3 style="margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">Message :</h3>
                <div style="color: #1e293b; line-height: 1.6; font-size: 15px;">
                    {$messageContent}
                </div>
            </div>
HTML;
        }

        $dateStr = date('d/m/Y à H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
</head>
<body style="margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <!-- En-tête -->
        <div style="background-color: #2563eb; padding: 20px 24px; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 700;">Nouveau message de contact</h1>
            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">Reçu le {$dateStr}</p>
        </div>

        <!-- Contenu des champs -->
        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">
                <tbody>
                    {$rowsHtml}
                </tbody>
            </table>

            {$messageSection}
        </div>

        <!-- Pied de page -->
        <div style="background-color: #f9fafb; padding: 14px 24px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb;">
            Cet email a été envoyé automatiquement depuis l'API Contact.
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Génère la version texte brut (Plain Text) de l'email pour les clients mail sans support HTML.
     */
    public function renderPlainText(array $data): string
    {
        $dateStr = date('d/m/Y à H:i:s');
        $lines = [
            "=== NOUVEAU MESSAGE DE CONTACT ===",
            "Date : " . $dateStr,
            "----------------------------------",
        ];

        foreach ($data as $key => $value) {
            if ($key === 'message') {
                continue;
            }
            $label = $this->getFieldLabel($key);
            $valStr = is_scalar($value) ? (string) $value : json_encode($value);
            $lines[] = "{$label} : {$valStr}";
        }

        if (isset($data['message']) && trim((string) $data['message']) !== '') {
            $lines[] = "----------------------------------";
            $lines[] = "Message :";
            $lines[] = (string) $data['message'];
        }

        $lines[] = "----------------------------------";
        $lines[] = "Envoyé automatiquement par l'API Contact.";

        return implode("\n", $lines);
    }

    /**
     * Construit et sécurise le sujet de l'email (neutralisation de l'injection d'en-tête CRLF).
     */
    public function buildSubject(array $data, ?string $customSubject = null): string
    {
        $prefix = (string) Config::get('mailer.subject_prefix', '[Contact Web]');

        if ($customSubject !== null && trim($customSubject) !== '') {
            $subject = $customSubject;
        } elseif (isset($data['subject']) && trim((string) $data['subject']) !== '') {
            $subject = (string) $data['subject'];
        } elseif (isset($data['fullName']) && trim((string) $data['fullName']) !== '') {
            $subject = "Nouveau contact de {$data['fullName']}";
        } else {
            $subject = "Nouveau message de contact";
        }

        $fullSubject = trim("{$prefix} {$subject}");

        // Sécurisation stricte contre l'injection d'en-têtes (suppression des sauts de ligne)
        return str_replace(["\r", "\n", "\t"], ' ', $fullSubject);
    }

    /**
     * Échappe une chaîne pour un affichage HTML sécurisé (protection XSS).
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Récupère le libellé propre d'un champ.
     */
    private function getFieldLabel(string $key): string
    {
        if (isset($this->fieldsConfig[$key]['label'])) {
            return $this->escape((string) $this->fieldsConfig[$key]['label']);
        }

        // Si le champ n'a pas de libellé configuré, on formate le nom de la clé
        $formatted = str_replace(['_', '-'], ' ', $key);
        return $this->escape(ucfirst($formatted));
    }

    /**
     * Formate la valeur d'un champ avec échappement et liens automatiques si approprié (ex: email, lien).
     */
    private function formatFieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<span style="color: #9ca3af; font-style: italic;">Non renseigné</span>';
        }

        if (!is_scalar($value)) {
            return '<pre style="margin: 0; font-size: 12px;">' . $this->escape(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
        }

        $strValue = (string) $value;
        $escaped = $this->escape($strValue);

        if ($key === 'email' || filter_var($strValue, FILTER_VALIDATE_EMAIL)) {
            return "<a href=\"mailto:{$escaped}\" style=\"color: #2563eb; text-decoration: none;\">{$escaped}</a>";
        }

        if ($key === 'phone' || preg_match('/^[0-9+\(\)\#\.\s\/-]{6,30}$/', $strValue)) {
            return "<a href=\"tel:{$escaped}\" style=\"color: #2563eb; text-decoration: none;\">{$escaped}</a>";
        }

        return $escaped;
    }
}
