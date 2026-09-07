<?php

declare(strict_types=1);

/**
 * Gera a migration 024_import_wordpress_posts.sql a partir de um dump do
 * WordPress importado numa base MySQL temporaria (veja README abaixo).
 *
 * Uso (dentro do container app, com a base "wp_import" carregada no MySQL
 * do docker compose deste projeto):
 *
 *   php scripts/generate_wordpress_import.php
 *
 * Le posts (post_type=post, status publish/draft), categorias e tags do WP
 * e escreve database/migrations/024_import_wordpress_posts.sql com INSERTs
 * ja limpos — a migration final NAO depende do dump nem da base wp_import
 * em tempo de execucao.
 */

$sourcePdo = new PDO(
    'mysql:host=db;port=3306;dbname=wp_import;charset=utf8mb4',
    'root',
    'root',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function q(PDO $pdo, string $value): string
{
    return $pdo->quote($value);
}

function slugify(string $value): string
{
    // Mesmo mapa de App\Core\Text::slugify() — evita depender da tabela de
    // transliteracao do iconv, que varia por SO/glibc (isso ja causou um
    // slug quebrado com bytes percent-encoded crus de um titulo com emoji).
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = strtr($value, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ]);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim($value, '-') ?: 'post';
}

function cleanContent(string $html): string
{
    // Marcadores de bloco do editor Gutenberg.
    $html = preg_replace('/<!--\s*\/?wp:.*?-->/s', '', $html);
    // Shortcode do plugin de blocos de codigo (envolve um <pre>, so tira o wrapper).
    $html = preg_replace('/\[\/?dm_code_snippet[^\]]*\]/i', '', $html);
    // Sem imagens nesta migracao (decisao do produto): remove <img> inteiro.
    $html = preg_replace('/<img\b[^>]*>/i', '', $html);
    // Atributos ruidosos colados por ferramentas externas (classes de
    // frameworks CSS, data-* de apps de chat, etc.) — mantem href/src/alt/title.
    $html = preg_replace('/\s(class|style|id|data-[a-z0-9-]+)="[^"]*"/i', '', $html);
    $html = preg_replace('/\n{3,}/', "\n\n", $html);

    return trim($html);
}

/**
 * Baixa uma imagem de capa do Lorem Picsum (banco de fotos publicas, sem
 * necessidade de chave de API) usando o slug do post como seed — a mesma
 * seed sempre devolve a mesma foto, entao reprocessar o dump (ou instalar
 * em producao a partir desta migration ja gerada) da o resultado identico,
 * sem depender de internet no momento da instalacao.
 */
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
        fwrite(STDERR, "  aviso: falha ao baixar capa de '{$slug}', mantendo placeholder padrao\n");

        return null;
    }

    file_put_contents($localPath, $data);

    return '/uploads/wordpress-import/' . $fileName;
}

function makeExcerpt(string $html): string
{
    $text = trim(strip_tags($html));
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    if (mb_strlen($text) <= 220) {
        return $text;
    }

    $cut = mb_substr($text, 0, 220);
    $lastSpace = mb_strrpos($cut, ' ');

    if ($lastSpace !== false) {
        $cut = mb_substr($cut, 0, $lastSpace);
    }

    return $cut . '…';
}

// ---- Posts (publicados + rascunhos) -----------------------------------

$posts = $sourcePdo->query(
    "SELECT ID, post_title, post_name, post_content, post_excerpt, post_date, post_status
     FROM prb_posts
     WHERE post_type = 'post' AND post_status IN ('publish', 'draft')
     ORDER BY post_date ASC"
)->fetchAll();

$termsStmt = $sourcePdo->prepare(
    "SELECT t.name, t.slug, tt.taxonomy
     FROM prb_term_relationships tr
     INNER JOIN prb_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
     INNER JOIN prb_terms t ON t.term_id = tt.term_id
     WHERE tr.object_id = :post_id AND tt.taxonomy IN ('category', 'post_tag')"
);

$uploadDir = dirname(__DIR__) . '/public/uploads/wordpress-import';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allCategories = []; // slug => name
$allTags = []; // slug => name
$postRows = [];
$total = count($posts);
$current = 0;

foreach ($posts as $post) {
    $current++;
    $wpSlug = trim($post['post_name']);
    // Alguns titulos com emoji ficam com bytes percent-encoded crus no
    // post_name do WP (ex: "%f0%9f%94%8d..."); nesse caso gera de novo a
    // partir do titulo em vez de herdar a sujeira.
    $slug = ($wpSlug !== '' && strpos($wpSlug, '%') === false) ? $wpSlug : slugify($post['post_title']);
    $content = cleanContent($post['post_content']);
    $excerpt = trim($post['post_excerpt']) !== '' ? trim($post['post_excerpt']) : makeExcerpt($content);
    $excerpt = mb_substr($excerpt, 0, 250);
    $status = $post['post_status'] === 'publish' ? 'published' : 'draft';

    fwrite(STDERR, "[{$current}/{$total}] {$slug}\n");
    $featuredImage = fetchFeaturedImage($slug, $uploadDir) ?? '/assets/images/blog-feature.svg';

    $termsStmt->execute(['post_id' => $post['ID']]);
    $categories = [];
    $tags = [];

    foreach ($termsStmt->fetchAll() as $term) {
        if ($term['taxonomy'] === 'category') {
            $allCategories[$term['slug']] = $term['name'];
            $categories[] = $term['slug'];
        } else {
            $allTags[$term['slug']] = $term['name'];
            $tags[] = $term['slug'];
        }
    }

    $postRows[] = [
        'title' => $post['post_title'],
        'slug' => $slug,
        'excerpt' => $excerpt,
        'content' => $content,
        'featured_image' => $featuredImage,
        'published_at' => $post['post_date'],
        'status' => $status,
        'categories' => $categories,
        'tags' => $tags,
    ];
}

// ---- Monta o SQL --------------------------------------------------------

$sql = "-- Importa os artigos do blog WordPress original (paulorb.dev) para o\n"
     . "-- schema do blog novo. Gerado por scripts/generate_wordpress_import.php\n"
     . "-- a partir de um dump do WordPress — nao depende do dump em tempo de\n"
     . "-- execucao, so contem os INSERTs ja prontos.\n\n"
     . "-- Remove os posts placeholder semeados pela 023 antes do conteudo real.\n"
     . "DELETE FROM posts WHERE slug IN (\n"
     . "    'rag-na-pratica-construindo-um-sistema-de-busca-semantica',\n"
     . "    'context-engineering-e-o-novo-prompt-engineering',\n"
     . "    'go-channels-revisitado-padroes-que-envelheceram-bem'\n"
     . ");\n"
     . "DELETE FROM categories WHERE slug = 'ia' AND NOT EXISTS (\n"
     . "    SELECT 1 FROM post_category WHERE post_category.category_id = categories.id\n"
     . ");\n\n";

$sql .= "-- Categorias\n";
foreach ($allCategories as $slug => $name) {
    $sql .= sprintf(
        "INSERT INTO categories (name, slug) VALUES (%s, %s) ON DUPLICATE KEY UPDATE name = VALUES(name);\n",
        q($sourcePdo, $name),
        q($sourcePdo, $slug)
    );
}

$sql .= "\n-- Tags\n";
foreach ($allTags as $slug => $name) {
    $sql .= sprintf(
        "INSERT INTO tags (name, slug) VALUES (%s, %s) ON DUPLICATE KEY UPDATE name = VALUES(name);\n",
        q($sourcePdo, $name),
        q($sourcePdo, $slug)
    );
}

$sql .= "\n-- Posts\n";
foreach ($postRows as $row) {
    $sql .= sprintf(
        "INSERT INTO posts (title, slug, excerpt, content, author_name, author_id, featured_image, published_at, status, deleted_at)\n"
        . "VALUES (%s, %s, %s, %s, 'Paulo Bolsanello', (SELECT id FROM users WHERE email = 'paulo.bolsanello@gmail.com' LIMIT 1), %s, %s, %s, NULL)\n"
        . "ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), content = VALUES(content), featured_image = VALUES(featured_image), published_at = VALUES(published_at), status = VALUES(status);\n",
        q($sourcePdo, $row['title']),
        q($sourcePdo, $row['slug']),
        q($sourcePdo, $row['excerpt']),
        q($sourcePdo, $row['content']),
        q($sourcePdo, $row['featured_image']),
        q($sourcePdo, $row['published_at']),
        q($sourcePdo, $row['status'])
    );

    foreach ($row['categories'] as $categorySlug) {
        $sql .= sprintf(
            "INSERT IGNORE INTO post_category (post_id, category_id)\n"
            . "SELECT posts.id, categories.id FROM posts, categories\n"
            . "WHERE posts.slug = %s AND categories.slug = %s;\n",
            q($sourcePdo, $row['slug']),
            q($sourcePdo, $categorySlug)
        );
    }

    foreach ($row['tags'] as $tagSlug) {
        $sql .= sprintf(
            "INSERT IGNORE INTO post_tag (post_id, tag_id)\n"
            . "SELECT posts.id, tags.id FROM posts, tags\n"
            . "WHERE posts.slug = %s AND tags.slug = %s;\n",
            q($sourcePdo, $row['slug']),
            q($sourcePdo, $tagSlug)
        );
    }
}

$sql .= "\n-- Remove categorias orfas (sem nenhum post) que sobraram de seeds anteriores.\n"
     . "DELETE FROM categories WHERE NOT EXISTS (\n"
     . "    SELECT 1 FROM post_category WHERE post_category.category_id = categories.id\n"
     . ");\n";

$outputPath = dirname(__DIR__) . '/database/migrations/024_import_wordpress_posts.sql';
file_put_contents($outputPath, $sql);

$totalCategories = count($allCategories);
$totalTags = count($allTags);
$totalPosts = count($postRows);

echo "Gerado: {$outputPath}\n";
echo "Posts: {$totalPosts} | Categorias: {$totalCategories} | Tags: {$totalTags}\n";
echo 'Tamanho: ' . round(filesize($outputPath) / 1024, 1) . " KB\n";
