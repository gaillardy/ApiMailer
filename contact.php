<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Gestion dynamique des origines CORS
$allowedOrigins = [
    'https://exemple.com/',
    'https://www.exemple.com/',
    'http://localhost:3000',
    'http://localhost:8000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://exemple.com");
}

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Réponse OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// Vérification méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}

// Authentification API
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== 'iofefehioeheghioehzfio465e4zgreWednf') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Traitement des données
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit();
}

// Validation
$required = ['fullName', 'email','phone','message'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Le champ $field est requis"]);
        exit();
    }
}
// Configuration SMTP avec PHPMailer
$mail = new PHPMailer(true);

try {
    // Paramètres SMTP
    $mail->isSMTP();
    $mail->Host = 'mail.exemple.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'contact@exemple.com';
    $mail->Password = 'PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    // Destinataires
    $mail->setFrom('contact@exemple.com', 'Contact');
    $mail->addAddress('contact@exemple.com');
    $mail->addReplyTo($data['email'], $data['fullName']);

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = "Nouveau contact: {$data['fullName']}";
    $mail->Body = "
        <h1>Nouveau message de contact</h1>
        <p><strong>Nom et prénom:</strong> {$data['fullname']}</p>
        <p><strong>Email :</strong> {$data['email']}</p>
        <p><strong>Tel :</strong> {$data['phone']}</p>
        <p><strong>Message :</strong></p>
        <p>{$data['message']}</p>
    ";

    // Envoi
    $mail->send();
    
    http_response_code(200);
    echo json_encode(['success' => 'Message envoyé avec succès']);
} catch (Exception $e) {
    error_log('Erreur PHPMailer: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['error' => "Erreur lors de l'envoi: " . $e->getMessage()]);
}