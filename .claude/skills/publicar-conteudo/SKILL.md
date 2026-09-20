---
name: publicar-conteudo
description: Publica um post ou projeto novo direto em produção no paulorb.dev, via terminal, sem abrir o admin no navegador. Usa um script PHP temporário enviado por FTP que chama PostRepository::createForAdmin() ou ProjectRepository::create() e é apagado do servidor logo depois de rodar uma vez. Pode pesquisar e usar imagens de licença livre da internet como capa/ilustração, e escolher ou criar categorias que façam sentido pro conteúdo. Só deve rodar quando o usuário pedir explicitamente (ex: "/publicar-conteudo", "publica esse post pra mim").
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
   em cada uso: mostrar o HTML/dados que vão ser gravados — incluindo a
   imagem escolhida (URL de origem + licença) e as categorias
   selecionadas/novas, se houver — e esperar OK explícito. Depois de
   aprovado, pode rodar upload → hit → delete sem pausar de novo pro
   mesmo conteúdo.
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
6. **Imagem só de fonte com licença livre confirmada** — nunca usar uma
   imagem só porque apareceu bem posicionada numa busca; ver critérios
   na seção "Buscando e anexando uma imagem" abaixo antes de baixar
   qualquer coisa.

## Escolhendo ou criando categorias

Antes de montar o script, decida as categorias do post:

1. Liste as categorias existentes lendo `App\Repositories\CategoryRepository::all()`
   (pode incluir essa leitura no mesmo script temporário, num `echo`
   separado, antes de decidir criar algo novo).
2. Escolha as que fazem sentido pro tema do post pelo nome/slug. Não
   force encaixe — um post pode ficar sem categoria.
3. Se nenhuma existente couber bem, crie uma nova com
   `CategoryRepository::create(['name' => 'Nome da categoria', 'slug' => ''])`
   (slug vazio = gerado automaticamente) **antes** de chamar
   `createForAdmin()`, e use o ID resultante em `category_ids`. Categoria
   nova entra na lista de coisas mostradas pro usuário na confirmação da
   regra 3 — não crie categoria em silêncio.
4. Evite duplicar: se já existe "Docker" e "Containers", não crie
   "Containerização" pro mesmo assunto — reaproveite a mais próxima.

## Buscando e anexando uma imagem de licença livre

Use quando o post/projeto não tiver uma imagem própria pronta (screenshot,
foto do usuário etc.) e fizer sentido ilustrar com uma imagem de banco.

**Critérios de licença (inegociável):** só usar imagens de domínio
público ou com licença explícita de reuso, de fontes conhecidas por
isso — Unsplash, Pexels, Pixabay (licenças próprias que permitem uso
comercial sem exigir crédito) ou Wikimedia Commons (checar a licença
específica do arquivo: CC0 não precisa de crédito, CC-BY/CC-BY-SA
precisa). Nunca usar resultado de busca de imagens genérica (Google
Images) sem confirmar a licença na página de origem — a maioria do que
aparece ali é protegida por direito autoral normal. Prefira sempre a
opção sem exigência de atribuição quando houver mais de uma imagem
adequada.

Passo a passo:

1. **Pesquisar** (`WebSearch`/`WebFetch`) no banco de imagens escolhido,
   por termos que descrevam o tema do post — não o título literal.
2. **Verificar a licença** na própria página do arquivo antes de baixar.
   Se exigir atribuição, anotar o nome do autor e o link da fonte.
3. **Baixar o arquivo** pra `/tmp` local (`curl -o`), preferindo JPEG/PNG/WebP
   em resolução razoável pra web (não precisa do original em altíssima
   resolução).
4. **Gerar o thumbnail** localmente com a mesma classe usada em produção,
   pra manter consistência com uploads feitos pelo admin:
   ```php
   require_once 'app/Core/ImageThumbnail.php';
   $thumbPath = \App\Core\ImageThumbnail::generate('/tmp/imagem-baixada.jpg', 'image/jpeg');
   ```
   (rodar isso no container Docker de PHP já usado pros testes, já que
   depende da extensão GD).
5. **Enviar por FTP** a imagem original e o thumbnail gerado pra
   `public/uploads/media/<ano>/<mes>/` (mesmo padrão de path que
   `AdminMediaController` usa pra uploads feitos pelo admin), com nomes
   únicos (sufixo aleatório, ex: `bin2hex(random_bytes(3))`).
6. **Registrar na tabela `media`** no mesmo script temporário, com
   `MediaRepository::create()`:
   ```php
   $mediaId = (new \App\Repositories\MediaRepository())->create([
       'file_name'      => 'nome-do-arquivo-abc123.jpg',
       'original_name'  => 'nome-original.jpg',
       'path'           => '/uploads/media/2026/09/nome-do-arquivo-abc123.jpg',
       'thumbnail_path' => '/uploads/media/2026/09/nome-do-arquivo-abc123-thumb.jpg', // ou null
       'mime_type'      => 'image/jpeg',
       'kind'           => 'image',
       'size'           => filesize('/tmp/imagem-baixada.jpg'),
       'width'          => 1600, // de getimagesize()
       'height'         => 900,
       'alt_text'       => 'Descrição curta da imagem pra acessibilidade',
   ]);
   ```
7. **Usar o `path` resultante** como `featured_image` do post, e/ou
   inserir `<img src="...">` dentro do `content` onde fizer sentido.
8. **Se a licença exigir atribuição**, incluir um parágrafo de crédito
   logo abaixo da imagem no `content` (a whitelist não tem
   `<figure>/<figcaption>`, então usa `<em>` mesmo):
   ```html
   <p><em>Foto: Nome do Autor, via Unsplash.</em></p>
   ```

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
       'featured_image' => '/assets/images/blog-feature.svg', // ou o `path` de um item recém-registrado em media (ver seção de imagem)
       'published_at'   => date('Y-m-d H:i:s'),
       'category_ids'   => [], // IDs de categorias existentes e/ou recém-criadas (ver seção de categorias)
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
    'cover_media_id' => null, // ID de um item já na biblioteca de mídia (ou recém-registrado, ver seção de imagem), ou null
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
