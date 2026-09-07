<?php

declare(strict_types=1);

/**
 * Gera a migration 025_wordpress_featured_images.sql: baixa uma foto de
 * capa (Lorem Picsum — banco publico, sem chave de API) para cada post que
 * ainda esta com a imagem placeholder padrao, salva em
 * public/uploads/wordpress-import/ e grava os UPDATEs.
 *
 * A seed de cada foto e o slug do post, entao a mesma foto sempre volta pro
 * mesmo post — reprocessar isso (ou instalar em producao a partir da
 * migration ja gerada) da o resultado identico. A migration final so faz
 * UPDATE posts SET featured_image = '...' — nao baixa nada em tempo de
 * instalacao, os arquivos ja vem junto no envio dos arquivos do site.
 *
 * Uso: php scripts/generate_wordpress_images.php
 */

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Env;
use App\Database\Database;

$rootPath = dirname(__DIR__);
(new Autoloader($rootPath . '/app'))->register();
Env::load($rootPath . '/.env');

$database = new Database();

function q(\PDO $pdo, string $value): string
{
    return $pdo->quote($value);
}

function fetchFeaturedImage(string $slug, string $uploadDir): ?string
{
    $fileName = $slug . '.jpg';
    $localPath = $uploadDir . '/' . $fileName;

    if (is_file($localPath)) {
        return '/uploads/wordpress-import/' . $fileName;
    }

    $seed = substr(md5($slug), 0, 12);
    $url = "https://picsum.photos/seed/{$seed}/1200/630.jpg";
    $context = stream_context_create(['http' => ['timeout' => 20, 'follow_location' => 1]]);
    $data = @file_get_contents($url, false, $context);

    if ($data === false || strlen($data) < 2000) {
        fwrite(STDERR, "  aviso: falha ao baixar capa de '{$slug}'\n");

        return null;
    }

    file_put_contents($localPath, $data);

    return '/uploads/wordpress-import/' . $fileName;
}

$uploadDir = $rootPath . '/public/uploads/wordpress-import';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$posts = $database->fetchAll(
    "SELECT id, slug FROM posts
     WHERE deleted_at IS NULL AND featured_image = '/assets/images/blog-feature.svg'
     ORDER BY id"
);

$pdo = $database->connection();
$sql = "-- Capas dos posts importados do WordPress, geradas por\n"
     . "-- scripts/generate_wordpress_images.php (Lorem Picsum, seed = slug do\n"
     . "-- post, entao a mesma foto sempre volta pro mesmo post). So faz UPDATE —\n"
     . "-- os arquivos em public/uploads/wordpress-import/ precisam ir junto no\n"
     . "-- envio dos arquivos do site.\n\n";
$total = count($posts);
$current = 0;
$downloaded = 0;

foreach ($posts as $post) {
    $current++;
    fwrite(STDERR, "[{$current}/{$total}] {$post['slug']}\n");
    $image = fetchFeaturedImage($post['slug'], $uploadDir);

    if ($image === null) {
        continue;
    }

    $downloaded++;
    $sql .= sprintf(
        "UPDATE posts SET featured_image = %s WHERE slug = %s;\n",
        q($pdo, $image),
        q($pdo, $post['slug'])
    );
}

$outputPath = $rootPath . '/database/migrations/025_wordpress_featured_images.sql';
file_put_contents($outputPath, $sql);

echo "Gerado: {$outputPath}\n";
echo "Posts sem imagem encontrados: {$total} | Imagens baixadas: {$downloaded}\n";
