# 📬 ApiMailer - API Sécurisée d'Envoi d'Emails de Contact

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/PHPUnit-Passing-brightgreen.svg)]

**ApiMailer** est une API PHP moderne, robuste et sécurisée conçue pour gérer l'envoi d'emails de contact via **PHPMailer**. Idéale pour les applications web (React, Vue, Angular, mobile ou sites statiques) nécessitant un backend fiable et découplé.

---

## Fonctionnalités & Points Forts

- **Sécurité Maximale** :
  - **Secrets externalisés** : Gestion des identifiants via `.env` (aucune clé ou mot de passe en dur).
  - **Protection Timing Attacks** : Comparaison des clés API à temps constant (`hash_equals()`).
  - **Protection XSS & Injection HTML** : Échappement strict et systématique (`htmlspecialchars`) de toutes les données reçues.
  - **Protection Header Injection (CRLF)** : Nettoyage automatique des retours à la ligne dans les sujets et en-têtes.
  - **Protection CORS granulaire** : Origines autorisées configurables avec gestion native du Preflight (`OPTIONS`).
  - **Honeypot Anti-Spam** : Champ piège caché pour neutraliser les robots sans alourdir l'expérience utilisateur.
  - **Pas de fuite d'informations** : Messages d'erreur génériques en production et journalisation technique sécurisée.
- **Architecture Modulaire (PSR-4)** :
- Séparation stricte des responsabilités : Contrôleur, Middlewares (Auth, CORS), Moteur de Validation, Templates et Services de messagerie.
- **Champs Dynamiques & Extensibles** :

  - Définissez vos champs personnalisés et leurs règles (`required`, `optional`, `email`, `phone`, `min:X`, `max:Y`, etc.) directement dans `config/mailer.php` sans modifier le cœur de l'API.
- **Design d'Email Professionnel & Responsive** :
  - Génération automatique d'un rendu HTML élégant avec support automatique des champs dynamiques.
  - Version Texte Brut (`AltBody`) générée pour les clients mail sans support HTML.
- **Suite de Tests Complète** :
  - Tests unitaires et d'intégration automatisés avec **PHPUnit 10+**.
- **Compatibilité Descendante** :
  - Support de `public/index.php` (point d'entrée recommandé) et de `contact.php` à la racine.

---

## Structure du Projet

```text
ApiMailer/
├── .env.example                # Modèle de configuration des variables d'environnement
├── .gitignore                  # Exclusion des fichiers sensibles et dépendances
├── composer.json               # Dépendances Composer et autoloading PSR-4
├── phpunit.xml                 # Configuration de la suite de tests PHPUnit
├── README.md                   # Documentation officielle
├── public/
│   └── index.php               # Point d'entrée principal pour serveurs web
├── contact.php                 # Fichier de compatibilité descendante
├── config/
│   ├── app.php                 # Paramètres de l'application, CORS et sécurité
│   └── mailer.php              # Configuration SMTP et schéma des champs
├── src/
│   ├── Config/
│   │   └── Config.php          # Gestionnaire de configuration (.env et PHP)
│   ├── Http/
│   │   ├── Request.php         # Abstraction sécurisée de la requête HTTP
│   │   ├── Response.php        # Abstraction des réponses JSON standardisées
│   │   └── Middleware/
│   │       ├── CorsMiddleware.php   # Gestion sécurisée des origines CORS
│   │       └── AuthMiddleware.php   # Authentification par clé API
│   ├── Validation/
│   │   ├── Validator.php       # Moteur de validation dynamique et extensible
│   │   └── ValidationException.php
│   ├── Services/
│   │   ├── MailerServiceInterface.php # Interface du service d'email
│   │   ├── PHPMailerService.php       # Implémentation SMTP via PHPMailer
│   │   └── EmailTemplate.php          # Générateur HTML/Texte sécurisé
│   └── Controllers/
│       └── ContactController.php      # Orchestration de la requête à l'envoi
└── tests/
    ├── Unit/                   # Tests unitaires des composants
    └── Integration/            # Tests d'intégration de bout en bout
```

---

## Installation Rapide

### 1. Prérequis

- PHP 8.1 ou supérieur avec les extensions `json`, `mbstring`, `openssl`.
- [Composer](https://getcomposer.org/) installé.

### 2. Cloner et installer les dépendances

```bash
git clone https://github.com/votre-compte/ApiMailer.git
cd ApiMailer
composer install
```

### 3. Configurer l'environnement

Copiez le fichier d'exemple `.env.example` vers `.env` :

```bash
cp .env.example .env
```

Éditez le fichier `.env` avec vos informations SMTP et votre clé API secrète :

```dotenv
APP_ENV=production
APP_DEBUG=false

# Clé API secrète (utilisée dans l'en-tête X-API-KEY ou Authorization: Bearer)
API_KEY=votre_super_cle_api_secrete_aleatoire_ici

# Origines autorisées pour le frontend (séparées par des virgules, sans slash de fin)
ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,https://mon-site.com

# Paramètres SMTP
SMTP_HOST=smtp.mon-hebergeur.com
SMTP_PORT=465
SMTP_AUTH=true
SMTP_USERNAME=contact@mon-site.com
SMTP_PASSWORD=mon_mot_de_passe_smtp
SMTP_ENCRYPTION=ssl

# Expéditeur et Destinataire
MAIL_FROM_ADDRESS=contact@mon-site.com
MAIL_FROM_NAME="Formulaire de Contact"
MAIL_TO_ADDRESS=destinataire@mon-site.com
MAIL_TO_NAME="Support Client"
MAIL_SUBJECT_PREFIX="[Contact Web]"

# Champ Anti-Spam (Honeypot)
HONEYPOT_FIELD=_gotcha
```

---

## Configuration des Champs (Personnalisation Facile)

Vous pouvez ajouter, supprimer ou modifier les règles des champs dans le fichier [`config/mailer.php`](file:///c:/Users/Yohana/Documents/App/ApiMailer/config/mailer.php) :

```php
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
        'rules' => ['optional', 'phone'], // Champ rendu optionnel
    ],
    'company' => [
        'label' => 'Nom de l\'entreprise',
        'rules' => ['required', 'string', 'min:2', 'max:150'], // Nouveau champ personnalisé !
    ],
    'message' => [
        'label' => 'Votre message',
        'rules' => ['required', 'string', 'min:10', 'max:5000'],
    ],
],
```

### Règles de validation disponibles

- `required` : Le champ doit être présent et non vide.
- `optional` / `nullable` : Le champ peut être omis ou vide.
- `email` : Doit être une adresse email valide.
- `string` : Doit être une chaîne de caractères.
- `numeric` : Doit être une valeur numérique.
- `phone` : Doit respecter le format d'un numéro de téléphone.
- `min:X` : Longueur minimale de X caractères.
- `max:Y` : Longueur maximale de Y caractères.
- `regex:pattern` : Expression régulière personnalisée (ex: `regex:/^[A-Z]{3}$/`).

---

## Utilisation de l'API

### Point d'accès

- **URL** : `POST /public/index.php` (ou `/contact.php`)
- **Headers requis** :
  - `Content-Type: application/json`
  - `X-API-KEY: <votre_cle_api>` *(ou `Authorization: Bearer <votre_cle_api>`)*

---

### Exemple avec cURL

```bash
curl -X POST https://api.mon-domaine.com/public/index.php \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: votre_super_cle_api_secrete_aleatoire_ici" \
  -d '{
    "fullName": "Jean Dupont",
    "email": "jean.dupont@example.com",
    "phone": "+33 6 12 34 56 78",
    "subject": "Demande de partenariat",
    "message": "Bonjour, je souhaiterais obtenir des informations sur vos services."
  }'
```

---

### Exemple en JavaScript (Fetch API / Frontend)

```javascript
async function sendContactForm(formData) {
  try {
    const response = await fetch('https://api.mon-domaine.com/public/index.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-KEY': 'votre_super_cle_api_secrete_aleatoire_ici',
      },
      body: JSON.stringify({
        fullName: formData.fullName,
        email: formData.email,
        phone: formData.phone,
        message: formData.message,
        _gotcha: '' // Laissez ce champ invisible dans le formulaire HTML pour piéger les bots
      }),
    });

    const result = await response.json();

    if (!response.ok) {
      console.error('Erreur de validation :', result.error, result.errors);
      alert(result.error);
      return;
    }

    alert('Votre message a bien été envoyé !');
  } catch (error) {
    console.error('Erreur réseau :', error);
  }
}
```

---

## Exécution des Tests

Le projet inclut une suite de tests unitaires et d'intégration complète pour assurer une fiabilité sans faille.

Pour exécuter les tests :

```bash
composer test
```

ou directement :

```bash
vendor/bin/phpunit
```

---

## Bonnes Pratiques de Déploiement

1. **Ne commitez JAMAIS votre fichier `.env`** sur GitHub.
2. Configurez votre serveur web (Nginx / Apache) pour pointer le Document Root vers le dossier `public/`.
3. Assurez-vous que le fichier `.env` n'est pas accessible directement par les utilisateurs web (le dossier `public/` garantit cela).
4. Définissez `APP_DEBUG=false` et `APP_ENV=production` en production.

---

## Licence

Ce projet est sous licence [MIT](LICENSE). Vous êtes libre de l'utiliser, le modifier et le distribuer dans vos projets personnels ou commerciaux.
