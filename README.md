# paulorb.dev

> Blog pessoal, portfólio de projetos e currículo com PDF gerado automaticamente — um CMS estilo WordPress construído do zero, sem framework e sem dependências, pra rodar em qualquer hospedagem compartilhada.

[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker Ready](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Zero Dependencies](https://img.shields.io/badge/composer-zero%20dependencies-brightgreen)](#-stack)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## O que é isso

Este repositório é o código-fonte de [paulorb.dev](https://paulorb.dev): um blog técnico, uma vitrine de projetos e um currículo público — tudo gerenciado por um painel administrativo próprio, sem depender de WordPress, Laravel ou qualquer outro framework. Foi construído deliberadamente em **PHP puro**, pensando numa hospedagem compartilhada comum (sem SSH, sem Composer, sem Node) como alvo de produção.

## ✨ Funcionalidades

**Blog**
- Posts com categorias, tags, busca, arquivo por mês e comentários moderados (com honeypot + reCAPTCHA v3 opcional)
- Editor rich-text (Quill) com upload/redimensionamento de imagem embutido e alternância pra HTML bruto
- Páginas estáticas customizáveis (estilo WordPress Pages)

**Projetos & Currículo**
- Seção de projetos estilo portfólio, com galeria de imagens e lightbox
- Currículo público com experiência, formação, cursos e certificações — cada seção com ordenação, busca e paginação no admin
- **PDF do currículo gerado automaticamente** a partir dos mesmos dados da página pública (sem upload manual de arquivo)

**Mídia**
- Biblioteca de mídia própria (upload, busca, metadados, exclusão) com três modos de visualização: grade, lista e agrupado por mês
- Seletor de mídia integrado a qualquer editor do admin

**Administração & operação**
- Painel `/admin` com dashboard, gestão de usuários e configurações do site
- **Instalador visual** (`/install`): confere requisitos do servidor, testa a conexão com o banco, roda as migrations com barra de progresso e cria a conta de administrador — nenhuma credencial vem pré-cadastrada no código
- Atualizações de versão aplicadas pelo próprio painel (`/admin/atualizar`), rodando só as migrations pendentes
- SEO básico embutido: URLs amigáveis, Open Graph/Twitter Card, JSON-LD, sitemap.xml, dark mode
- Suporte a Google Analytics (GA4) via um campo de configuração — nada é carregado até você colar o Measurement ID

## 🧱 Stack

Sem Composer, sem framework, sem build step. De propósito: a hospedagem de produção é compartilhada, sem acesso SSH.

| Camada | Tecnologia |
|---|---|
| Backend | PHP 7.2+ puro, MVC caseiro (`App\Controllers`, `App\Repositories`, `App\Core`) |
| Banco de dados | MySQL/MariaDB via PDO, migrations numeradas em SQL puro |
| PDF | [FPDF](http://www.fpdf.org/) vendorizado (sem Composer) |
| Front-end | Bootstrap 5 + Font Awesome (CDN), Quill.js no editor, JS vanilla |
| Deploy | FTP (upload direto de arquivos, sem SSH) |
| Dev local | Docker Compose (PHP + Apache + MySQL) |

## 🚀 Instalação

### Hospedagem compartilhada (produção)

1. Copie `.env.install.example` para `.env.install` e troque `INSTALL_KEY` por um valor aleatório (ex: `openssl rand -hex 16`). Guarde essa chave.
2. Envie todos os arquivos e pastas pra raiz do FTP (ex: `public_html/`), incluindo o `.env.install`. **Não** envie `.env`.
3. Crie um banco MySQL vazio no painel da hospedagem (cPanel, DirectAdmin, Plesk...). O instalador cria as tabelas, mas não o banco em si.
4. Acesse o site pelo navegador. Você cai no instalador, que pede a chave do passo 1, confere os requisitos do servidor, testa a conexão com o banco, grava o `.env`, roda as migrations com uma tela de progresso e, por último, pede pra você criar a conta de administrador — nome, e-mail e senha à sua escolha.
5. Ao concluir, o instalador se desativa sozinho e apaga o `.env.install`.

> Se a raiz do site não tiver permissão de escrita, o instalador mostra o conteúdo do `.env` pra você enviar manualmente por FTP e continuar de onde parou.

**Atualizações:** depois de enviar arquivos novos que incluam migrations, entre em `/admin` — o painel avisa quantas estão pendentes e o botão **Atualizar banco** roda cada uma com a mesma tela de progresso, já atrás do login. Faça backup do banco antes: alterações de estrutura no MySQL não são transacionais, e uma falha no meio deixa mudanças parciais aplicadas.

### Ambiente de desenvolvimento (Docker)

Sobe PHP 7.2 + Apache e MySQL 8 localmente, sem instalar nada na máquina.

```bash
# build + sobe os containers em background
docker compose up -d --build

# primeiro acesso: http://localhost:8000 cai no instalador
# (mesmo fluxo da produção — cria as tabelas e a conta de admin)
```

O `.env.docker` (versão de desenvolvimento, com credenciais de banco locais e descartáveis) já vem pronto no repositório — não precisa criar nada à mão pra rodar `docker compose up`. Ele é montado por cima do `.env` só dentro do container; o `.env` real da raiz (produção) nunca é tocado.

```bash
# acompanhar logs
docker compose logs -f app

# rodar comandos PHP dentro do container
docker compose exec app php scripts/migrate.php

# console do MySQL
docker compose exec db mysql -uuser -ppassword blog

# parar (mantendo os dados)
docker compose down

# parar e apagar os dados do banco
docker compose down -v
```

Portas expostas: app em `localhost:8000`, MySQL em `localhost:3306` (`user`/`password`, banco `blog`, root `root` — valores de dev, sem relação com produção).

> Editou o `.env.docker`? Rode `docker compose restart app` — é um bind mount de arquivo único, e a maioria dos editores substitui o arquivo em vez de escrever por cima, o que quebra o mount até reiniciar o container.

## 🔑 Acesso administrativo

Não existe usuário ou senha padrão em lugar nenhum do código. A conta de administrador é sempre criada por você, na hora, durante o passo final do instalador (`/install/admin`) — tanto em produção quanto no Docker local. Depois disso, use `/admin` pra entrar.

## 📄 Licença

Distribuído sob a licença MIT — veja [`LICENSE`](LICENSE) para mais detalhes.
