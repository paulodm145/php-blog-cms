-- Remove os posts de teste do blog antigo (soft delete)
UPDATE posts SET deleted_at = NOW(), status = 'draft' WHERE deleted_at IS NULL;

-- Categorias usadas pelos artigos do protótipo
INSERT INTO categories (name, slug)
SELECT 'Engenharia', 'engenharia' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'engenharia');
INSERT INTO categories (name, slug)
SELECT 'Setor Público', 'setor-publico' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'setor-publico');
INSERT INTO categories (name, slug)
SELECT 'Produto', 'produto' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'produto');

-- Artigos do protótipo
INSERT INTO posts (title, slug, excerpt, content, author_name, author_id, featured_image, published_at, status)
VALUES
('Como reduzimos o tempo de deploy em 70% com pipelines enxutos',
 'como-reduzimos-o-tempo-de-deploy-em-70-com-pipelines-enxutos',
 'Um passo a passo prático de como estruturamos CI/CD para times pequenos entregarem com segurança.',
 '<p>Entregar rápido e com segurança não é privilégio de grandes times. Mostramos como uma esteira de deploy bem desenhada muda o jogo mesmo em equipes pequenas.</p><h2>O gargalo não era o código</h2><p>Na maioria dos projetos que assumimos, o tempo perdido não estava em escrever software — estava em colocá-lo no ar. Builds manuais, ambientes divergentes e medo de quebrar a produção travavam a entrega.</p><h2>Automação onde importa</h2><p>Padronizamos ambientes, automatizamos testes e criamos um pipeline que valida cada alteração antes de chegar ao usuário. O resultado foi deploy previsível, várias vezes ao dia, sem plantão de madrugada.</p><h2>O que fica de lição</h2><p>Comece pequeno: automatize primeiro o que mais dói. Cada passo removido do processo manual devolve tempo para o time focar no que gera valor — o produto.</p>',
 'Equipe Pr2', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-07-12 10:00:00', 'published'),
('Modernizar sistemas legados sem parar a operação',
 'modernizar-sistemas-legados-sem-parar-a-operacao',
 'Estratégias de migração incremental que aplicamos em órgãos públicos e grandes empresas.',
 '<p>Sistemas legados sustentam operações críticas — e justamente por isso não podem simplesmente ser desligados. A migração precisa ser cirúrgica.</p><h2>Nada de big bang</h2><p>Substituir tudo de uma vez é a receita mais comum para o fracasso. Preferimos a estratégia de migração incremental, módulo a módulo, mantendo o sistema antigo e o novo convivendo durante a transição.</p><h2>Segurança e continuidade</h2><p>Em órgãos públicos, indisponibilidade tem impacto direto no cidadão. Por isso trabalhamos com camadas de integração e rollback rápido, garantindo que a operação nunca pare.</p><h2>Resultado sustentável</h2><p>Ao final, o cliente fica com uma base moderna, documentada e evolutiva — sem o trauma de uma virada arriscada.</p>',
 'Equipe Pr2', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-06-28 10:00:00', 'published'),
('Software sob medida x plataforma pronta: quando cada um faz sentido',
 'software-sob-medida-x-plataforma-pronta-quando-cada-um-faz-sentido',
 'Um guia direto para decidir onde investir sem desperdiçar orçamento nem tempo.',
 '<p>Nem todo problema pede software sob medida — e nem toda plataforma pronta serve. A decisão certa economiza meses e orçamento.</p><h2>Quando a plataforma pronta basta</h2><p>Para processos comuns e bem resolvidos pelo mercado, adotar uma solução existente é mais rápido e barato. Faz sentido quando seu diferencial não está ali.</p><h2>Quando vale o sob medida</h2><p>Se o processo é o seu diferencial competitivo, ou se nenhuma ferramenta se encaixa sem gambiarra, o software sob medida se paga em produtividade e escala.</p><h2>Como decidir</h2><p>Mapeie onde está o seu valor. Automatize o comum com o que já existe e invista sob medida no que só a sua operação tem.</p>',
 'Equipe Pr2', (SELECT id FROM users ORDER BY id LIMIT 1), '', '2026-06-09 10:00:00', 'published');

INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'como-reduzimos-o-tempo-de-deploy-em-70-com-pipelines-enxutos' AND categories.slug = 'engenharia';
INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'modernizar-sistemas-legados-sem-parar-a-operacao' AND categories.slug = 'setor-publico';
INSERT IGNORE INTO post_category (post_id, category_id)
SELECT posts.id, categories.id FROM posts, categories
WHERE posts.slug = 'software-sob-medida-x-plataforma-pronta-quando-cada-um-faz-sentido' AND categories.slug = 'produto';

-- Projetos do protótipo
INSERT INTO projects (name, slug, tag, description, lead_text, challenge, solution, features, technologies, featured, status, sort_order)
VALUES
('GestorPub', 'gestorpub', 'Setor Público',
 'Plataforma de gestão para órgãos públicos: protocolos, processos e transparência em um só lugar.',
 'Uma plataforma que centraliza protocolos, processos e transparência para órgãos públicos, reduzindo papel e retrabalho.',
 'O órgão dependia de planilhas e sistemas isolados, gerando retrabalho, perda de prazos e pouca visibilidade sobre o andamento dos processos.',
 'Desenvolvemos uma plataforma web unificada, com fluxo de protocolos digital, controle de prazos e um portal de transparência aberto ao cidadão.',
 'Protocolo e tramitação 100% digital\nPainel de prazos e alertas\nPortal de transparência público\nPerfis e permissões por setor',
 'React, Node.js, PostgreSQL, Docker', 1, 'published', 1),
('FluxoPME', 'fluxopme', 'PME',
 'ERP enxuto para pequenas e médias empresas — vendas, estoque e financeiro sem complexidade.',
 'Um ERP enxuto que reúne vendas, estoque e financeiro sem a complexidade dos sistemas tradicionais.',
 'Pequenas empresas precisavam controlar operação e caixa, mas os ERPs do mercado eram caros, pesados e difíceis de usar no dia a dia.',
 'Criamos um ERP focado no essencial, com interface simples e relatórios claros, que a equipe aprende a usar em poucas horas.',
 'Controle de vendas e estoque\nFluxo de caixa e contas\nRelatórios prontos para decisão\nAcesso web e mobile',
 'React, Node.js, MySQL, AWS', 1, 'published', 2),
('IntegraHub', 'integrahub', 'Integração',
 'Hub de integrações que conecta ERPs, marketplaces e serviços via APIs em tempo real.',
 'Um hub que conecta ERPs, marketplaces e serviços externos com integrações em tempo real e monitoradas.',
 'Os sistemas do cliente não conversavam entre si, exigindo digitação manual e gerando divergências de dados entre canais.',
 'Construímos um hub de integrações com APIs padronizadas, filas resilientes e um painel de monitoramento de cada conexão.',
 'Conectores para ERPs e marketplaces\nSincronização em tempo real\nReprocessamento automático de falhas\nMonitoramento e logs centralizados',
 'Node.js, RabbitMQ, Redis, Docker', 1, 'published', 3),
('PainelPr2', 'painelpr2', 'Dados',
 'Dashboards e relatórios sob medida para decisões baseadas em dados, atualizados ao vivo.',
 'Dashboards e relatórios sob medida que transformam dados espalhados em decisões rápidas e confiáveis.',
 'As informações estavam distribuídas em vários sistemas, e montar um relatório consolidado levava dias de trabalho manual.',
 'Centralizamos os dados em um só lugar e criamos painéis interativos, atualizados ao vivo e acessíveis de qualquer dispositivo.',
 'Painéis interativos e filtráveis\nAtualização em tempo real\nExportação de relatórios\nIndicadores personalizados',
 'React, Python, PostgreSQL, Metabase', 1, 'published', 4);
