<?php

use App\Core\Auth;
use App\Core\Text;

$hasImage = !empty($post['featured_image']) && $post['featured_image'] !== '/assets/images/blog-feature.svg';
$recaptchaSiteKey = trim((string) ($settings['recaptcha_site_key'] ?? ''));
$commentStatus = (string) ($_GET['comentario'] ?? '');
require dirname(__DIR__) . '/partials/site-top.php';
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <div class="bg-surface border-b">
        <div class="container-lg py-5">
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/blog" class="accent" style="font-size:.78rem;font-weight:500">← voltar</a>
                        <?php if (Auth::check()): ?>
                            <a href="/admin/posts/<?= (int) $post['id'] ?>/edit" class="chip-pill no-print"><i class="fa-solid fa-pen"></i> Editar post</a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($isDraftPreview)): ?>
                        <div class="alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0 no-print" role="alert">
                            <i class="fa-solid fa-eye-slash"></i>
                            <span>Rascunho — só você vê essa página, ela não está publicada nem indexada. Publique pelo admin quando estiver pronto.</span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 flex-wrap mt-3 mb-3">
                        <?php foreach ($post['categories'] ?? [] as $postCategory): ?>
                            <a href="/blog/categoria/<?= rawurlencode($postCategory['slug']) ?>" class="chip-pill"><?= htmlspecialchars($postCategory['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endforeach; ?>
                        <?php foreach ($post['tags'] ?? [] as $postTag): ?>
                            <a href="/blog/tag/<?= rawurlencode($postTag['slug']) ?>" class="chip-pill"><?= htmlspecialchars($postTag['name'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endforeach; ?>
                    </div>
                    <h1 class="post-title"><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="post-meta-line d-flex gap-2 align-items-center num mt-2">
                        <span style="color:var(--text);font-weight:500"><?= htmlspecialchars($post['author_name'] ?: 'Equipe Editorial', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="opacity-50">·</span><span><?= Text::shortDate($post['published_at']) ?></span>
                        <span class="opacity-50">·</span><span><?= Text::readingTime((string) $post['content']) ?> de leitura</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <main class="container-lg py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <?php if ($hasImage): ?>
                    <div class="cover mb-4" style="height:320px">
                        <img src="<?= htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endif; ?>
                <div class="prose"><?= $content ?></div>

                <section class="mt-5 pt-4 border-t" id="comentarios">
                    <h3 class="widget-title">Comentários (<?= count($comments) ?>)</h3>

                    <?php if (count($comments) === 0): ?>
                        <p class="text-muted" style="font-size:.875rem">Nenhum comentário ainda. Seja o primeiro.</p>
                    <?php endif; ?>

                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($comment['author_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="flex-fill min-w-0">
                                <div style="font-size:.8rem;margin-bottom:.25rem">
                                    <strong style="font-weight:600"><?= htmlspecialchars($comment['author_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="text-muted ms-2"><?= Text::shortDate($comment['created_at']) ?></span>
                                </div>
                                <p class="mb-0" style="font-size:.875rem;line-height:1.6"><?= nl2br(htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4" id="comentar">
                        <?php if ($commentStatus === '1'): ?>
                            <div class="widget" style="border-color:var(--accent)">Comentário enviado — ele aparece aqui assim que for aprovado.</div>
                        <?php elseif ($commentStatus === 'erro'): ?>
                            <div class="widget" style="border-color:#c0392b">Preencha nome, e-mail e comentário antes de enviar.</div>
                        <?php endif; ?>
                        <form id="comment-form" method="post" action="/blog/<?= rawurlencode($post['slug']) ?>/comentarios">
                            <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">
                            <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                            <div class="row g-2 mb-2">
                                <div class="col-sm-6">
                                    <input class="field" type="text" name="author_name" placeholder="seu nome" required>
                                </div>
                                <div class="col-sm-6">
                                    <input class="field" type="email" name="author_email" placeholder="seu e-mail (não é publicado)" required>
                                </div>
                            </div>
                            <textarea class="field" name="body" placeholder="deixe seu comentário..." required></textarea>
                            <div class="d-flex justify-content-end mt-2">
                                <button class="btn-accent" type="submit">publicar</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
            <div class="col-lg-4">
                <?php $sidebarOrder = ['about', 'categories', 'archive', 'recent']; ?>
                <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
            </div>
        </div>
    </main>
    <?php if (count($related) > 0): ?>
        <section class="bg-surface border-t py-5">
            <div class="container-lg">
                <h3 class="widget-title">Continue lendo</h3>
                <div class="row g-4">
                    <?php $currentPost = $post; ?>
                    <?php foreach ($related as $post): ?>
                        <div class="col-md-6"><?php require dirname(__DIR__) . '/partials/post-card.php'; ?></div>
                    <?php endforeach; ?>
                    <?php $post = $currentPost; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php $hasEmbeddedGallery = strpos($content, 'gallery-grid-thumb') !== false; ?>
    <?php if ($hasEmbeddedGallery): ?>
        <?php require dirname(__DIR__) . '/partials/gallery-lightbox-modal.php'; ?>
    <?php endif; ?>
<?php require dirname(__DIR__) . '/partials/site-bottom.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>
<?php if ($hasEmbeddedGallery): ?>
    <script src="/assets/js/gallery-lightbox.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/gallery-lightbox.js') ?: '1' ?>"></script>
<?php endif; ?>
<script>
    (function () {
        document.querySelectorAll('.prose pre code').forEach(function (block) {
            hljs.highlightElement(block);
            hljs.lineNumbersBlock(block);
        });

        document.querySelectorAll('.code-copy-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var code = button.closest('.code-window').querySelector('code');
                navigator.clipboard.writeText(code.innerText).then(function () {
                    var original = button.textContent;
                    button.textContent = 'Copiado!';
                    button.classList.add('copied');
                    setTimeout(function () {
                        button.textContent = original;
                        button.classList.remove('copied');
                    }, 1800);
                });
            });
        });
    })();
</script>
<?php if ($recaptchaSiteKey !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= urlencode($recaptchaSiteKey) ?>"></script>
    <script>
        (function () {
            var form = document.getElementById('comment-form');
            if (!form) { return; }
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                grecaptcha.ready(function () {
                    grecaptcha.execute('<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>', { action: 'comment' }).then(function (token) {
                        document.getElementById('recaptcha_token').value = token;
                        form.submit();
                    });
                });
            });
        })();
    </script>
<?php endif; ?>
