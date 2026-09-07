# Repository Guidelines

## Project Structure & Module Organization

This is a pure PHP blog project using MVC. Add `Services` and `Repositories` only when the use case justifies the extra layer. Keep the public entry point in `public/index.php` and application code outside the web root. A recommended layout is `app/Controllers`, `app/Models`, `app/Views`, `app/Core`, `app/Services`, and `app/Repositories`. Store static assets in `public/assets` and tests in `tests/`.

## Build, Test, and Development Commands

Use commands that work with plain PHP. If Composer is added, keep scripts in `composer.json`.

- `composer install`: install PHP dependencies.
- `composer test`: run the full test suite.
- `composer lint`: run PHP style checks, if configured.
- `php -S localhost:8000 -t public public/dev-router.php`: run the development server.

Document services in `README.md` and sample environment values in `.env.example`.

## Coding Style & Naming Conventions

Use PHP 7.2-compatible code even when developing on PHP 8.3 — a hospedagem de produção está travada em PHP 7.2 e não pode ser trocada. Isso é mais restrito que o PHP em geral costuma exigir: nada de propriedades tipadas (`private string $x;` — só existe a partir do 7.4), nada de PHP 8-only (attributes, constructor property promotion, union types, `match`, named arguments, `readonly`, nullsafe `?->`, `str_contains`/`str_starts_with`/`str_ends_with`), nada de `??=` ou arrow functions (`fn()`, só a partir do 7.4). Tipos em parâmetro e retorno de método (`string $x`, `: void`, `?array`) são permitidos normalmente — a restrição é só em propriedade de classe. Antes de assumir que uma sintaxe é segura, teste com `docker run --rm -v "$(pwd):/app" php:7.2-cli php -l <arquivo>`. Follow PSR-12 with 4-space indentation and namespaces, for example `App\Controllers\PostController`. Name classes in `PascalCase`, methods and variables in `camelCase`, and database tables or route names in `snake_case`. Keep controllers focused on HTTP flow.

## Front-End & SEO

As páginas públicas usam Bootstrap 5 e Font Awesome via CDN (mesmo padrão do admin), Google Fonts (Inter, Source Serif 4, JetBrains Mono) e o tema próprio em `public/assets/css/blog-theme.css`. A referência visual é a pasta `design/` (protótipo "paulorb.dev": magazine + sidebar, light/dark mode). O layout precisa ser responsivo (a navbar colapsa em telas pequenas, a sidebar empilha abaixo do conteúdo). Public pages must follow SEO basics: semantic HTML, one clear `<h1>`, descriptive `<title>` and meta description, readable slugs, canonical URLs when needed, image `alt`, and fast-loading assets.

## Testing Guidelines

Use PHPUnit or Pest once dependencies are added. Name tests after behavior, for example `test_guest_cannot_access_admin_posts`. Keep unit tests fast; use feature tests for routes, database behavior, authentication, and authorization.

## Commit & Pull Request Guidelines

No Git history is present, so use imperative commits such as `Add post publishing flow` or `Fix comment validation`. Pull requests should include a short summary, test results, linked issues when relevant, and screenshots for UI changes.

## Routing & URLs

The project must use friendly URLs managed by the application itself. Route requests through `public/index.php` and resolve paths with an internal router in `app/Core` or equivalent. Avoid exposing PHP filenames. Prefer `/posts/minha-primeira-postagem` instead of `/post.php?id=1`.

## Security & Configuration Tips

Never commit real `.env` files, credentials, database dumps, or generated dependency directories. If Composer is used, commit `composer.json` and `composer.lock`, but not `vendor/`. Validate and escape user input, especially comments, search fields, slugs, and admin forms.
