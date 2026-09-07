<?php

namespace App\Controllers;

use App\Core\ErrorPage;
use App\Core\Recaptcha;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\SettingRepository;

class CommentController
{
    public function store(string $slug): void
    {
        $post = (new PostRepository())->findBySlug($slug);

        if ($post === null) {
            ErrorPage::notFound();
            return;
        }

        $redirect = '/blog/' . rawurlencode($post['slug']);

        // Honeypot: campo oculto que só um bot preenche. Se veio preenchido,
        // finge sucesso sem gravar nada — não dá pista de que foi filtrado.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            header('Location: ' . $redirect . '?comentario=1#comentarios');
            return;
        }

        $name = trim((string) ($_POST['author_name'] ?? ''));
        $email = trim((string) ($_POST['author_email'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || $body === '') {
            header('Location: ' . $redirect . '?comentario=erro#comentar');
            return;
        }

        $settings = (new SettingRepository())->all();
        $recaptchaOk = Recaptcha::passes(
            (string) $settings['recaptcha_secret_key'],
            (string) ($_POST['recaptcha_token'] ?? '')
        );

        if (!$recaptchaOk) {
            header('Location: ' . $redirect . '?comentario=1#comentarios');
            return;
        }

        (new CommentRepository())->create([
            'post_id' => $post['id'],
            'author_name' => $name,
            'author_email' => $email,
            'body' => $body,
        ]);

        header('Location: ' . $redirect . '?comentario=1#comentarios');
    }
}
