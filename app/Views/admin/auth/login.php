<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/blog.css?v=<?= @filemtime(dirname(__DIR__, 4) . '/public/assets/css/blog.css') ?: '1' ?>" rel="stylesheet">
</head>
<body class="admin-login-shell">
    <main class="container py-5">
        <div class="row justify-content-center align-items-center" style="min-height:85vh">
            <div class="col-md-5">
                <div class="admin-login-brand text-center mb-4">paulorb<span class="accent">.dev</span></div>
                <section class="content-surface p-4 p-md-5">
                    <h1 class="h4 mb-4">Entrar no admin</h1>

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" action="/admin/login">
                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input class="form-control" id="email" type="email" name="email" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password">Senha</label>
                            <input class="form-control" id="password" type="password" name="password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Entrar</button>
                    </form>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
