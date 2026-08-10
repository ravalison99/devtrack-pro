<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - DevTrack Pro</title>
</head>
<body>
    <h1>DevTrack Pro</h1>

    @if ($errors->any())
        <div style="color:red">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/login">
        @csrf

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}">

        <label for="password">Mot de passe</label>
        <input id="password" type="password" name="password">

        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
