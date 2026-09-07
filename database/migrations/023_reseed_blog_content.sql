-- Substitui o conteudo de exemplo com voz de agencia (semeado pela 015) por
-- conteudo alinhado ao blog pessoal, e atualiza a identidade do site.

DELETE FROM posts WHERE slug IN (
    'como-reduzimos-o-tempo-de-deploy-em-70-com-pipelines-enxutos',
    'modernizar-sistemas-legados-sem-parar-a-operacao',
    'software-sob-medida-x-plataforma-pronta-quando-cada-um-faz-sentido'
);

DELETE FROM categories WHERE slug IN ('engenharia', 'setor-publico', 'produto');

INSERT INTO categories (name, slug)
SELECT 'Inteligência Artificial', 'ia' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'ia');
INSERT INTO categories (name, slug)
SELECT 'Go', 'go' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'go');

INSERT INTO tags (name, slug)
SELECT 'IA', 'ia' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'ia');
INSERT INTO tags (name, slug)
SELECT 'Python', 'python' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'python');
INSERT INTO tags (name, slug)
SELECT 'RAG', 'rag' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'rag');
INSERT INTO tags (name, slug)
SELECT 'LLM', 'llm' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'llm');
INSERT INTO tags (name, slug)
SELECT 'Go', 'go' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'go');
INSERT INTO tags (name, slug)
SELECT 'Concorrência', 'concorrencia' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tags WHERE slug = 'concorrencia');

INSERT INTO posts (title, slug, excerpt, content, author_name, author_id, featured_image, published_at, status)
VALUES
('RAG na prática: construindo um sistema de busca semântica',
 'rag-na-pratica-construindo-um-sistema-de-busca-semantica',
 'Como combinar embeddings, vector stores e LLMs para criar um sistema de perguntas e respostas sobre documentação interna, sem frameworks mágicos.',
 '<p>Construir um sistema de busca semântica deixou de ser um problema de pesquisa e virou uma tarefa de engenharia. Os tijolos estão prontos: modelos de embedding bons e baratos, vector stores que rodam dentro do Postgres, e LLMs com tool calling estável. O desafio agora é montar isso de um jeito que não exploda em produção.</p><h2>O setup mínimo</h2><p>Para o exemplo, três coisas: pgvector como armazenamento, um modelo de embedding para vetorizar os documentos, e um modelo de chat para gerar a resposta final. Nada de framework — o objetivo é deixar explícito o que cada peça faz.</p><h2>Chunking: o detalhe que define a qualidade</h2><p>A primeira versão cortava textos a cada 500 tokens. Funcionava, até alguém perguntar algo cuja resposta atravessava dois chunks. A correção é trivial: sobreposição de uns 100 tokens entre chunks consecutivos. Ganho de qualidade desproporcional ao trabalho.</p><blockquote>Boas heurísticas de chunking valem mais do que vector stores caros. Esse é o aprendizado mais barato que você pode ter.</blockquote><p>Próximo passo: avaliação. Como medir se o RAG está realmente respondendo melhor do que o modelo cru.</p>',
 'Paulo Bolsanello', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-04-22 10:00:00', 'published'),
('Context engineering é o novo prompt engineering',
 'context-engineering-e-o-novo-prompt-engineering',
 'A diferença entre um prompt mediano e um sistema de IA confiável raramente está nas instruções — está em como você monta e ordena o contexto.',
 '<p>A diferença entre um prompt mediano e um sistema de IA confiável raramente está nas instruções em si. Está em como você monta, ordena e limita o contexto que chega ao modelo.</p><h2>Contexto não é só o prompt</h2><p>Histórico de conversa, resultados de busca, memória de longo prazo, definição de ferramentas — tudo isso compete pelo mesmo espaço, e a ordem em que aparece muda o comportamento do modelo.</p><h2>Cortar é tão importante quanto incluir</h2><p>Contexto em excesso degrada a qualidade da resposta tanto quanto contexto insuficiente. Parte do trabalho é decidir o que <em>não</em> mandar.</p><blockquote>Um sistema de IA confiável se parece mais com um pipeline de dados do que com uma conversa.</blockquote>',
 'Paulo Bolsanello', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-04-15 10:00:00', 'published'),
('Go channels revisitado: padrões que envelheceram bem',
 'go-channels-revisitado-padroes-que-envelheceram-bem',
 'Sete anos depois de começar a escrever Go em produção, voltei aos padrões clássicos de concorrência. Alguns continuam úteis, outros nem tanto.',
 '<p>Sete anos depois de começar a escrever Go em produção, voltei aos padrões clássicos de concorrência com channels. Alguns envelheceram bem, outros hoje eu evitaria.</p><h2>O que ainda vale</h2><p>Fan-out/fan-in continua sendo a estrutura mais simples para paralelizar trabalho independente. Worker pools com channel de tamanho fixo ainda são a forma mais previsível de limitar concorrência.</p><h2>O que eu evitaria hoje</h2><p>Channels usados só como mutex disfarçado — dá para resolver com <code>sync.Mutex</code> de um jeito mais direto e mais fácil de ler.</p><pre><code>func worker(jobs &lt;-chan int, results chan&lt;- int) {\n    for j := range jobs {\n        results &lt;- j * 2\n    }\n}</code></pre><p>Concorrência simples é concorrência que dá para explicar em uma frase. Se o padrão precisa de um diagrama, provavelmente há uma forma mais simples.</p>',
 'Paulo Bolsanello', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-04-08 10:00:00', 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'rag-na-pratica-construindo-um-sistema-de-busca-semantica' AND categories.slug = 'ia';
INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'context-engineering-e-o-novo-prompt-engineering' AND categories.slug = 'ia';
INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'go-channels-revisitado-padroes-que-envelheceram-bem' AND categories.slug = 'go';

INSERT IGNORE INTO post_tag (post_id, tag_id)
SELECT posts.id, tags.id FROM posts, tags
WHERE posts.slug = 'rag-na-pratica-construindo-um-sistema-de-busca-semantica' AND tags.slug IN ('ia', 'python', 'rag');
INSERT IGNORE INTO post_tag (post_id, tag_id)
SELECT posts.id, tags.id FROM posts, tags
WHERE posts.slug = 'context-engineering-e-o-novo-prompt-engineering' AND tags.slug IN ('ia', 'llm');
INSERT IGNORE INTO post_tag (post_id, tag_id)
SELECT posts.id, tags.id FROM posts, tags
WHERE posts.slug = 'go-channels-revisitado-padroes-que-envelheceram-bem' AND tags.slug IN ('go', 'concorrencia');

INSERT INTO pages (title, slug, content, status)
SELECT 'Sobre', 'sobre',
 '<p>Olá. Sou o Paulo, engenheiro de software. Trabalho com sistemas distribuídos há mais de uma década e nos últimos anos venho me dedicando à interseção entre engenharia tradicional e modelos de linguagem.</p><p>Este blog é onde escrevo o que aprendo. Não é um curso, não é uma newsletter — é um caderno técnico aberto. A maioria dos posts nasce de problemas reais que tive que resolver e da vontade de organizar o raciocínio antes de esquecer.</p><p>Os temas variam: Python, Go, TypeScript, infraestrutura, e cada vez mais sobre como construir produtos em cima de LLMs sem entrar em armadilhas óbvias.</p>',
 'published'
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'sobre');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'paulorb.dev'),
('blog_description', 'Engenheiro de software. Escrevo sobre IA, LLMs, Go, TypeScript e arquitetura.'),
('github_url', 'https://github.com/paulorb'),
('linkedin_url', 'https://linkedin.com/in/paulorb'),
('recaptcha_site_key', ''),
('recaptcha_secret_key', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
