<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Throwable;

/**
 * Service d'envoi d'emails basé sur PHPMailer.
 * Configure la connexion SMTP sécurisée et orchestre la composition du message.
 */
class PHPMailerService implements MailerServiceInterface
{
    private EmailTemplate $template;
    private ?PHPMailer $mailer;
    private ?string $lastError = null;

    /**
     * @param EmailTemplate|null $template Moteur de gabarits d'emails
     * @param PHPMailer|null $mailer Instance PHPMailer préconfigurée (utile pour les tests unitaires)
     */
    public function __construct(?EmailTemplate $template = null, ?PHPMailer $mailer = null)
    {
        $this->template = $template ?? new EmailTemplate();
        $this->mailer = $mailer;
    }

    /**
     * Envoie l'email de contact à l'adresse configurée.
     *
     * @param array $data Données validées du formulaire
     * @param string|null $customSubject Sujet personnalisé éventuel
     * @return bool True si l'email a été expédié avec succès, False sinon
     */
    public function send(array $data, ?string $customSubject = null): bool
    {
        $this->lastError = null;

        try {
            $mail = $this->mailer ?? $this->createMailerInstance();

            // Expéditeur technique
            $fromAddress = (string) Config::get('mailer.from.address', 'contact@exemple.com');
            $fromName = (string) Config::get('mailer.from.name', 'Formulaire de Contact');
            $mail->setFrom($fromAddress, $fromName);

            // Destinataire principal
            $toAddress = (string) Config::get('mailer.to.address', 'contact@exemple.com');
            $toName = (string) Config::get('mailer.to.name', 'Administrateur');
            $mail->addAddress($toAddress, $toName);

            // Reply-To : réponse directe à l'utilisateur ayant soumis le formulaire
            $replyToEmail = $data['email'] ?? null;
            $replyToName = $data['fullName'] ?? ($data['name'] ?? '');

            if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, (string) $replyToName);
            }

            // Sujet et contenus
            $mail->isHTML(true);
            $mail->Subject = $this->template->buildSubject($data, $customSubject);
            $mail->Body = $this->template->renderHtml($data);
            $mail->AltBody = $this->template->renderPlainText($data);

            // Expédition du message
            $sent = $mail->send();

            if (!$sent) {
                $this->lastError = $mail->ErrorInfo ?: 'Échec de l\'envoi de l\'email.';
                error_log('[ApiMailer Error] ' . $this->lastError);
                return false;
            }

            return true;
        } catch (PHPMailerException $e) {
            $this->lastError = $e->getMessage();
            error_log('[ApiMailer PHPMailer Exception] ' . $this->lastError);
            return false;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('[ApiMailer Unexpected Exception] ' . $this->lastError);
            return false;
        }
    }

    /**
     * Récupère le dernier message d'erreur.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Instancie et configure une instance de PHPMailer selon les paramètres d'environnement.
     */
    protected function createMailerInstance(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Paramètres de transport SMTP
        $mail->isSMTP();
        $mail->Host = (string) Config::get('mailer.smtp.host', 'mail.exemple.com');
        $mail->SMTPAuth = (bool) Config::get('mailer.smtp.auth', true);
        $mail->Username = (string) Config::get('mailer.smtp.username', '');
        $mail->Password = (string) Config::get('mailer.smtp.password', '');
        $mail->Port = (int) Config::get('mailer.smtp.port', 465);
        $mail->Timeout = (int) Config::get('mailer.smtp.timeout', 15);
        $mail->SMTPDebug = (int) Config::get('mailer.smtp.debug', 0);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        // Configuration du chiffrement SSL / TLS
        $encryption = strtolower((string) Config::get('mailer.smtp.encryption', 'ssl'));
        if ($encryption === 'ssl' || $encryption === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        return $mail;
    }
}
