<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Config;
use App\Http\Request;
use App\Http\Response;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Validation\Validator;
use App\Services\MailerServiceInterface;
use App\Services\PHPMailerService;

/**
 * Contrôleur principal gérant la réception et le traitement des messages de contact.
 */
class ContactController
{
    private CorsMiddleware $corsMiddleware;
    private AuthMiddleware $authMiddleware;
    private Validator $validator;
    private MailerServiceInterface $mailerService;

    /**
     * @param CorsMiddleware|null $corsMiddleware
     * @param AuthMiddleware|null $authMiddleware
     * @param Validator|null $validator
     * @param MailerServiceInterface|null $mailerService
     */
    public function __construct(
        ?CorsMiddleware $corsMiddleware = null,
        ?AuthMiddleware $authMiddleware = null,
        ?Validator $validator = null,
        ?MailerServiceInterface $mailerService = null
    ) {
        $this->corsMiddleware = $corsMiddleware ?? new CorsMiddleware();
        $this->authMiddleware = $authMiddleware ?? new AuthMiddleware();
        $this->validator = $validator ?? new Validator();
        $this->mailerService = $mailerService ?? new PHPMailerService();
    }

    /**
     * Point d'entrée principal pour traiter la requête HTTP entrante.
     *
     * @param Request|null $request Requête HTTP (utilise Request::createFromGlobals() par défaut)
     * @return Response Réponse HTTP prête à être envoyée
     */
    public function handle(?Request $request = null): Response
    {
        $request = $request ?? Request::createFromGlobals();

        // 1. Traitement des requêtes CORS et Preflight (OPTIONS)
        $preflightResponse = $this->corsMiddleware->handle($request);
        if ($preflightResponse !== null) {
            return $preflightResponse;
        }

        // 2. Vérification de la méthode HTTP (seul POST est accepté pour l'envoi)
        if (!$request->isMethod('POST')) {
            $response = Response::error('Méthode non autorisée. Seules les requêtes POST sont acceptées.', 405);
            $this->corsMiddleware->handle($request, $response);
            return $response;
        }

        // 3. Authentification par clé API
        $authResponse = $this->authMiddleware->handle($request);
        if ($authResponse !== null) {
            $this->corsMiddleware->handle($request, $authResponse);
            return $authResponse;
        }

        // 4. Validation du format JSON
        if (!$request->hasValidJson()) {
            $errorMsg = $request->getJsonError() ?? 'Données JSON invalides.';
            $response = Response::error("Format de données invalide : {$errorMsg}", 400);
            $this->corsMiddleware->handle($request, $response);
            return $response;
        }

        $rawData = $request->all();

        // 5. Validation des données du formulaire selon les règles configurées
        if (!$this->validator->validate($rawData)) {
            $firstError = $this->validator->getFirstError() ?? 'Données de formulaire invalides.';
            $response = Response::error($firstError, 400, $this->validator->getErrors());
            $this->corsMiddleware->handle($request, $response);
            return $response;
        }

        // 6. Gestion du piège anti-spam (Honeypot)
        if ($this->validator->isSpam()) {
            // Le robot a rempli le champ caché : on retourne un faux succès pour ne pas l'alerter
            $response = Response::success('Message envoyé avec succès.');
            $this->corsMiddleware->handle($request, $response);
            return $response;
        }

        $validatedData = $this->validator->getValidatedData();

        // 7. Envoi de l'email via le service de messagerie
        $sent = $this->mailerService->send($validatedData);

        if (!$sent) {
            $isDebug = (bool) Config::get('app.debug', false);
            $technicalError = $this->mailerService->getLastError();

            $clientError = $isDebug
                ? "Erreur technique lors de l'envoi : {$technicalError}"
                : "Une erreur est survenue lors de l'envoi de votre message. Veuillez réessayer ultérieurement.";

            $response = Response::error($clientError, 500);
            $this->corsMiddleware->handle($request, $response);
            return $response;
        }

        // 8. Réponse de succès standardisée
        $response = Response::success('Message envoyé avec succès.', [
            'timestamp' => date('c'),
        ]);
        $this->corsMiddleware->handle($request, $response);
        return $response;
    }
}
