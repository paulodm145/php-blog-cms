-- Corrige um post cujo slug original no WordPress ficou com bytes
-- percent-encoded crus (titulo comecava com o emoji "🔍", que o WP nao
-- conseguiu transliterar em sanitize_title()). O "%" literal no slug
-- quebra a URL (o navegador tenta decodificar como percent-encoding).

UPDATE posts
SET slug = 'laravel-eloquent-vs-query-builder-qual-usar-para-cada-situacao',
    featured_image = '/uploads/wordpress-import/laravel-eloquent-vs-query-builder-qual-usar-para-cada-situacao.jpg'
WHERE slug = '%f0%9f%94%8dlaravel-eloquent-vs-query-builder-qual-usar-para-cada-situacao';
