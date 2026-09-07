<?php require __DIR__ . '/partials/shell-top.php'; ?>
            <div class="mb-4">
                <h1 class="h3 mb-1">Configurações</h1>
                <p class="text-secondary mb-0">Identidade do blog exibida no cabeçalho, rodapé e páginas públicas.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Configurações salvas com sucesso.</div>
            <?php endif; ?>

            <form method="post" action="/admin/settings">
                <div class="mb-3">
                    <label class="form-label" for="site_name">Nome do blog</label>
                    <input class="form-control" id="site_name" name="site_name" type="text"
                        value="<?= htmlspecialchars($settings['site_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="blog_description">Descrição / tagline</label>
                    <textarea class="form-control" id="blog_description" name="blog_description" rows="3"><?= htmlspecialchars($settings['blog_description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="github_url">Link do GitHub</label>
                    <input class="form-control" id="github_url" name="github_url" type="url"
                        value="<?= htmlspecialchars($settings['github_url'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="https://github.com/seu-usuario">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="linkedin_url">Link do LinkedIn</label>
                    <input class="form-control" id="linkedin_url" name="linkedin_url" type="url"
                        value="<?= htmlspecialchars($settings['linkedin_url'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="https://www.linkedin.com/in/seu-usuario">
                </div>

                <hr class="my-4">
                <h2 class="h5 mb-1">Anti-spam de comentários (reCAPTCHA v3)</h2>
                <p class="text-secondary small">
                    Crie as chaves em
                    <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">google.com/recaptcha/admin</a>
                    (tipo v3). Sem chaves configuradas, os comentários seguem protegidos só pelo honeypot.
                </p>
                <div class="mb-3">
                    <label class="form-label" for="recaptcha_site_key">Site Key</label>
                    <input class="form-control" id="recaptcha_site_key" name="recaptcha_site_key" type="text"
                        value="<?= htmlspecialchars($settings['recaptcha_site_key'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="recaptcha_secret_key">Secret Key</label>
                    <input class="form-control" id="recaptcha_secret_key" name="recaptcha_secret_key" type="password" autocomplete="new-password"
                        placeholder="<?= $settings['recaptcha_secret_key'] !== '' ? 'Chave salva — deixe em branco para manter' : 'Secret key' ?>" value="">
                </div>

                <hr class="my-4">
                <h2 class="h5 mb-1">Google Analytics</h2>
                <p class="text-secondary small">
                    Crie uma propriedade GA4 em
                    <a href="https://analytics.google.com" target="_blank" rel="noopener">analytics.google.com</a>
                    e cole o Measurement ID abaixo (formato <code>G-XXXXXXXXXX</code>). Em branco, nada é carregado nas páginas públicas.
                </p>
                <div class="mb-4">
                    <label class="form-label" for="google_analytics_id">Measurement ID</label>
                    <input class="form-control" id="google_analytics_id" name="google_analytics_id" type="text"
                        value="<?= htmlspecialchars($settings['google_analytics_id'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="G-XXXXXXXXXX">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="/admin">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Salvar</button>
                </div>
            </form>
<?php require __DIR__ . '/partials/shell-bottom.php'; ?>
</body>
</html>
