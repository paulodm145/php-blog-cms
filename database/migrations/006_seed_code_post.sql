INSERT INTO posts (
    title,
    slug,
    excerpt,
    content,
    author_name,
    featured_image,
    category_id,
    published_at
) VALUES (
    'Exemplo de codigo em um post',
    'exemplo-de-codigo-em-um-post',
    'Um post de teste mostrando como blocos de codigo aparecem na leitura.',
    '<h2>Renderizando codigo</h2><p>Este post usa conteudo HTML salvo pelo editor administrativo.</p><pre><code>&lt;?php\nnamespace App\\Controllers;\n\nclass ExampleController\n{\n    public function index(): void\n    {\n        echo ''Hello World'';\n    }\n}\n</code></pre><p>Blocos de codigo devem manter espacos, quebra de linha e contraste adequado.</p>',
    'Paulo Silva',
    '/assets/images/blog-feature.svg',
    (SELECT id FROM categories WHERE slug = 'php' LIMIT 1),
    '2026-04-21 09:00:00'
)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    excerpt = VALUES(excerpt),
    content = VALUES(content),
    author_name = VALUES(author_name),
    featured_image = VALUES(featured_image),
    category_id = VALUES(category_id),
    published_at = VALUES(published_at);

INSERT INTO post_tag (post_id, tag_id)
SELECT posts.id, tags.id
FROM posts
INNER JOIN tags ON tags.slug IN ('php-7-4', 'mvc')
WHERE posts.slug = 'exemplo-de-codigo-em-um-post'
ON DUPLICATE KEY UPDATE tag_id = VALUES(tag_id);

