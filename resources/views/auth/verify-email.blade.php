<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de l'email</title>
</head>
<body>
    <h1>Vérifiez votre adresse email</h1>
    <p>Un email de vérification a été envoyé à votre adresse. Veuillez cliquer sur le lien dans l'email pour vérifier votre compte.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Renvoyer l'email de vérification</button>
</body>
</html>
