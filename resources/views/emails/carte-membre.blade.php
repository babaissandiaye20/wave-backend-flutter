{{-- resources/views/emails/carte-membre.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px;
        }
        .footer {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            font-size: 0.9em;
        }
        .details {
            background: #ffffff;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Bienvenue {{ $utilisateur->prenom }} {{ $utilisateur->nom }} !</h2>
    </div>

    <div class="content">
        <p>Bonjour {{ $utilisateur->prenom }},</p>
        
        <p>Nous sommes ravis de vous confirmer la création de votre compte. Voici un récapitulatif de vos informations :</p>

        <div class="details">
            <p><strong>Login :</strong> {{ $utilisateur->login }}</p>
            <p><strong>Solde initial :</strong> {{ number_format($compte->solde, 0, ',', ' ') }} FCFA</p>
            <p><strong>Plafond de solde :</strong> {{ number_format($compte->plafond_solde, 0, ',', ' ') }} FCFA</p>
            <p><strong>Limite mensuelle de transactions :</strong> {{ number_format($compte->cumul_transaction_mensuelle, 0, ',', ' ') }} FCFA</p>
        </div>

        <p>Vous trouverez en pièce jointe votre carte de membre officielle.</p>
        
        <p>Pour des raisons de sécurité, nous vous recommandons de :</p>
        <ul>
            <li>Conserver précieusement votre carte de membre</li>
            <li>Ne jamais partager vos identifiants de connexion</li>
            <li>Nous contacter immédiatement en cas de perte ou de vol</li>
        </ul>
    </div>

    <div class="footer">
        <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
    </div>
</body>
</html>