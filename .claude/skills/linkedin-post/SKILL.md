---
name: linkedin-post
description: Gera um rascunho de post pro LinkedIn a partir de um artigo já publicado no paulorb.dev (gancho + resumo + convite pra ler + link + hashtags), mostra pro usuário revisar/editar, e só publica no perfil pessoal do LinkedIn depois de aprovação explícita. Usa scripts/linkedin/post.py (publicação, com --dry-run) e scripts/linkedin/authorize.py (setup/renovação do token OAuth, necessário a cada ~60 dias). Só deve rodar quando o usuário pedir explicitamente (ex: "/linkedin-post <url>", "posta esse artigo no linkedin").
argument-hint: "<url-do-artigo-publicado>"
disable-model-invocation: true
---

# Postar um artigo do blog no LinkedIn

Gera um post de divulgação no estilo "gancho + resumo + convite + link +
hashtags" a partir de um artigo já publicado no paulorb.dev, e publica
no perfil pessoal do LinkedIn do usuário depois de revisão humana.

Não toca no app PHP em nenhum momento — só lê o artigo já publicado
(via WebFetch) e chama os scripts em `scripts/linkedin/`. Ver spec em
`docs/superpowers/specs/2026-09-24-linkedin-post-automation-design.md`
pro desenho completo.

## Regras inegociáveis

1. **Nunca chamar `post.py` sem `--dry-run` antes de o usuário aprovar
   explicitamente o texto final.** Mostrar o rascunho completo no chat,
   esperar "pode postar"/"aprovado"/equivalente — nunca assumir
   aprovação pelo silêncio ou por um pedido genérico anterior.
2. **O texto sempre reflete o artigo *publicado*, nunca um rascunho
   local.** Buscar via `WebFetch` na URL passada como argumento da
   skill — se a URL não carregar ou não parecer o artigo esperado,
   avisar o usuário em vez de inventar conteúdo.
3. **Rodar `post.py --dry-run` primeiro, sempre**, mesmo depois da
   aprovação do texto — é a última checagem de que o payload está
   correto antes da chamada real.
4. **Se `post.py` disser que o token expirou**, orientar o usuário a
   rodar `python3 scripts/linkedin/authorize.py` (abre o navegador
   dele) e só então tentar publicar de novo.
5. **Nunca inventar link encurtado.** Usar a URL completa do artigo tal
   como fornecida (ou a canônica encontrada na página); não gerar nem
   supor um encurtador.

## Fluxo

1. Receber a URL do artigo publicado como argumento
   (`argument-hint: "<url-do-artigo-publicado>"`). Se não vier
   argumento, perguntar a URL antes de continuar.
2. `WebFetch` na URL — extrair título, tema central e os pontos
   principais do artigo (não precisa reler o artigo inteiro de volta
   pro usuário, só entender o suficiente pra resumir bem).
3. Montar o rascunho seguindo este formato (referência dada pelo
   usuário):
   - Gancho de abertura — uma pergunta ou afirmação que conecta com a
     dor/curiosidade de quem trabalha na área do artigo.
   - 2-3 parágrafos curtos resumindo o artigo, sem entregar tudo (dar
     vontade de clicar).
   - Um convite explícito pra ler o artigo completo.
   - A URL do artigo.
   - 3-5 hashtags relevantes ao tema específico do artigo (evitar
     hashtags genéricas demais tipo só `#blog` ou `#tecnologia`).
4. Mostrar o rascunho completo no chat. Se o usuário pedir ajuste,
   refazer e mostrar de novo — repetir até aprovação explícita.
5. Rodar `python3 scripts/linkedin/post.py --dry-run` passando o texto
   aprovado (via `--file` num arquivo temporário no scratchpad, ou via
   stdin), mostrar o payload resultante.
6. Rodar `python3 scripts/linkedin/post.py` (sem `--dry-run`) com o
   mesmo texto.
7. Se der erro de token expirado (regra 4), pausar aqui — não insistir
   sozinho.
8. Em sucesso, devolver ao usuário a URL do post publicado que o
   script imprimiu.
