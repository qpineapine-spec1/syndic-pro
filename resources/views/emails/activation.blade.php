<!DOCTYPE html>
<html>
<body>
    <h1>Activation de votre compte</h1>
    <p>Bonjour,</p>
    <p>Veuillez activer votre compte en cliquant sur le lien suivant :</p>
    <p><a href="{{ route('activate.show', ['token' => $token]) }}">Activer mon compte</a></p>
</body>
</html>
