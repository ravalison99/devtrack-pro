<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Connexion') - DevTrack Pro</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #4f46e5, #4338ca 55%, #312e81);
            font-family: -apple-system, "Segoe UI", Roboto, system-ui, sans-serif;
        }

        .guest-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(17, 24, 39, .25);
        }

        .guest-brand {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: .9rem;
            background: #4f46e5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #4338ca;
            border-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
