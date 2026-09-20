---
name: publicar-conteudo
description: Publica um post ou projeto novo direto em produção no paulorb.dev, via terminal, sem abrir o admin no navegador. Usa um script PHP temporário enviado por FTP que chama PostRepository::createForAdmin() ou ProjectRepository::create() e é apagado do servidor logo depois de rodar uma vez. Só deve rodar quando o usuário pedir explicitamente (ex: "/publicar-conteudo", "publica esse post pra mim").
argument-hint: "[post|projeto]"
disable-model-invocation: true
---

# Publicar conteúdo no paulorb.dev via terminal

Essa skill documenta o fluxo que já vinha sendo repetido manualmente nesta
sessão pra publicar posts e projetos direto em produção sem passar pelo
formulário do admin: sobe um script PHP temporário por FTP, roda ele uma
vez via HTTP, confirma o resultado e apaga o script na sequência.

Existe porque o blog não tem API — só formulários HTML autenticados por
sessão de cookie — e produção só é acessível via FTP (sem SSH). Ver
memória `paulorb-ftp-deploy` pra credenciais e o padrão geral de deploy;
essa skill cobre especificamente a parte de **criar conteúdo**, não
migração de schema.

## Regras inegociáveis

1. **Nunca deixar o script vivo no servidor.** Ele bypassa toda validação
   de sessão do admin e chama o repositório diretamente — se alguém achar
   a URL antes de você apagar, publica conteúdo arbitrário. Upload → 1
   request → `DELE` imediato, sempre, mesmo se a request falhar (aí você
   debuga localmente com Docker antes de subir de novo).
2. **Nunca gravar a senha de FTP neste arquivo nem em código versionado.**
   As credenciais já estão na memória persistente do Claude (memória
   `paulorb-ftp-deploy`) — recupere de lá. Se não estiverem disponíveis,
   pergunte ao usuário, não invente nem deixe em texto plano no repo.
3. **Confirmar com o usuário antes do primeiro upload contra produção**
   em cada uso: mostrar o HTML/dados que vão ser gravados, esperar OK
   explícito. Depois de aprovado, pode rodar upload → hit → delete sem
   pausar de novo pro mesmo conteúdo.
4. **HTML do campo `content` precisa passar pela whitelist de
   `App\Core\Html::postContent()`** antes de ir pro banco (ela roda de
   novo na exibição, então tag fora da lista simplesmente some depois).
   Tags permitidas hoje:
   `p br strong b em i u s a ul ol li blockquote pre code h2 h3 h4 img table thead tbody tfoot tr th td colgroup col`
5. **Testar localmente primeiro é opcional, mas recomendado** pra
   conteúdo grande/complexo (tabelas, muitas imagens): sobe o Docker
   (`docker compose up -d db` + container de app), roda o mesmo script
   contra o banco local, confere a página renderizada, só depois repete
   contra produção.

## Passo a passo — publicar um POST

1. **Monte o HTML do conteúdo** já dentro da whitelist acima. Blocos de
   código usam `<pre><code>...</code></pre>` (o realce de sintaxe e a
   "janela" com bolinhas são aplicados automaticamente na exibição por
   `Html::renderPostContent()` — não precisa marcar nada a mais).

2. **Monte o slug** manualmente se quiser controlar a URL, ou deixe em
   branco — `createForAdmin()` gera a partir do título via
   `Text::slugify()` (sem acento, minúsculo, hífens).

3. **Escreva o script temporário** em `/tmp` (local) e depois envie por
   FTP pra `public/_tmp_publish.php` na raiz pública de produção. Modelo:

   ```php
   <?php

   declare(strict_types=1);

   require_once __DIR__ . '/../app/Core/Autoloader.php';

   $autoloader = new \App\Core\Autoloader(dirname(__DIR__) . '/app');
   $autoloader->register();

   \App\Core\Env::load(dirname(__DIR__) . '/.env');

   $repo = new \App\Repositories\PostRepository();

   $id = $repo->createForAdmin([
       'title'          => 'Título do post',
       'slug'           => '', // vazio = gerado automaticamente
       'excerpt'        => 'Resumo curto pra listagem e SEO.',
       'content'        => '<p>Conteúdo em HTML já sanitizado.</p>',
       'author_id'      => 1, // ver tabela users; 0/inválido cai no primeiro autor por nome
       'featured_image' => '/assets/images/blog-feature.svg',
       'published_at'   => date('Y-m-d H:i:s'),
       'category_ids'   => [], // array de IDs existentes em categories
       'tags'           => '', // string separada por vírgula, cria tags novas se não existirem
       'status'         => 'published', // ou 'draft' / 'hidden'
   ]);

   echo "OK id={$id}\n";
   ```

4. **Envie por FTP** pra `public/_tmp_publish.php` (use as credenciais da
   memória `paulorb-ftp-deploy`).

5. **Rode uma vez** com `curl -s https://paulorb.dev/_tmp_publish.php`.
   Confira a resposta (`OK id=123`) — se der erro, o script continua no ar
   até você corrigir e rodar de novo, então debugue rápido.

6. **Confirme visualmente**: abra a URL pública do post
   (`https://paulorb.dev/blog/<slug>`) e confira o resultado renderizado.

7. **Apague o script imediatamente** via FTP (`DELE public/_tmp_publish.php`).
   Não pule esse passo mesmo se for publicar mais conteúdo em seguida —
   suba um script novo pra cada rodada.

## Passo a passo — publicar um PROJETO

Mesma mecânica, trocando o repositório e os campos. `ProjectRepository::create()`
espera:

```php
$repo = new \App\Repositories\ProjectRepository();

$id = $repo->create([
    'name'         => 'Nome do projeto',
    'slug'         => '', // vazio = gerado a partir do nome
    'tagline'      => 'Frase curta de destaque.',
    'content'      => '<p>Descrição em HTML sanitizado.</p>',
    'cover_media_id' => null, // ID de um item já na biblioteca de mídia, ou null
    'technologies' => 'PHP, MySQL, Docker',
    'role'         => null, // ou string, ex: 'Desenvolvedor solo'
    'project_type' => null, // 'personal' | 'professional' | 'freelance' | null
    'live_url'     => null, // ou URL string
    'start_date'   => null, // 'YYYY-MM-DD' ou null
    'end_date'     => null,
    'featured'     => false,
    'status'       => 'published', // ou 'draft'
]);

echo "OK id={$id}\n";
```

Links de repositório/fonte (`source_links_label[]`/`source_links_url[]`) e
galeria de mídia (`gallery_media_ids`) são gerenciados por métodos
separados no repositório — se o usuário pedir isso, leia
`app/Repositories/ProjectRepository.php` antes de estender o script pra
confirmar a assinatura atual (o método `create()` cobre só os campos
diretos da tabela `projects`).

## Depois de publicar

- Se o post/projeto usa uma galeria de fotos, o shortcode no `content`
  é `[@slug-da-galeria@]` — a expansão acontece na exibição
  (`GalleryRepository::expandShortcodes()`), não precisa nada especial
  no script de criação.
- Pra editar depois, é mais simples usar o admin normal
  (`/admin/posts/<id>/edit`) do que escrever outro script temporário.
