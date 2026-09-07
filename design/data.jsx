// Posts e dados compartilhados entre as variações do paulorb.dev

const POSTS = [
  {
    id: "rag-na-pratica",
    title: "RAG na prática: construindo um sistema de busca semântica",
    excerpt: "Como combinar embeddings, vector stores e LLMs para criar um sistema de Q&A sobre documentação interna. Sem frameworks mágicos — só Python, pgvector e a API da OpenAI.",
    date: "22 abr 2026",
    readTime: "12 min",
    tags: ["IA", "Python", "RAG"],
    cover: { hue: 212, label: "RAG · embeddings" },
    featured: true,
  },
  {
    id: "context-engineering",
    title: "Context engineering é o novo prompt engineering",
    excerpt: "A diferença entre um prompt mediano e um sistema de IA confiável raramente está nas instruções — está em como você monta e ordena o contexto.",
    date: "15 abr 2026",
    readTime: "8 min",
    tags: ["IA", "LLM"],
    cover: { hue: 228, label: "Context" },
  },
  {
    id: "go-channels-revisitado",
    title: "Go channels revisitado: padrões que envelheceram bem",
    excerpt: "Sete anos depois de começar a escrever Go em produção, voltei aos padrões clássicos de concorrência. Alguns continuam úteis, outros nem tanto.",
    date: "08 abr 2026",
    readTime: "10 min",
    tags: ["Go", "Concorrência"],
    cover: { hue: 204, label: "Go · channels" },
  },
  {
    id: "agentes-llm-producao",
    title: "Agentes LLM em produção: o que ninguém te conta",
    excerpt: "Tool calling, retry com backoff, observabilidade, custos. Lições de seis meses operando agentes em ambiente real para clientes reais.",
    date: "29 mar 2026",
    readTime: "15 min",
    tags: ["IA", "Agentes", "Produção"],
    cover: { hue: 234, label: "Agents" },
  },
  {
    id: "typescript-sem-classes",
    title: "TypeScript sem classes: discriminated unions na prática",
    excerpt: "Modelagem de domínio com tipos algébricos. Por que abandonei classes para a maior parte do código de aplicação que escrevo hoje.",
    date: "21 mar 2026",
    readTime: "9 min",
    tags: ["TypeScript", "Tipos"],
    cover: { hue: 218, label: "TS · ADTs" },
  },
  {
    id: "fine-tuning-vale-pena",
    title: "Fine-tuning ainda vale a pena em 2026?",
    excerpt: "Com modelos de 1M de tokens de contexto e prompt caching barato, quando faz sentido investir em treinamento dedicado? Análise de custo, latência e qualidade.",
    date: "14 mar 2026",
    readTime: "11 min",
    tags: ["IA", "LLM"],
    cover: { hue: 200, label: "Fine-tuning" },
  },
];

const TAGS = [
  { name: "IA", count: 18 },
  { name: "Python", count: 12 },
  { name: "Go", count: 9 },
  { name: "TypeScript", count: 8 },
  { name: "LLM", count: 7 },
  { name: "RAG", count: 5 },
  { name: "Agentes", count: 4 },
  { name: "Concorrência", count: 3 },
  { name: "Produção", count: 6 },
  { name: "Tipos", count: 4 },
  { name: "Arquitetura", count: 7 },
  { name: "DevOps", count: 5 },
];

// Conteúdo do post para a página individual
const POST_BODY = [
  { type: "p", text: "Construir um sistema de busca semântica deixou de ser um problema de pesquisa e virou uma tarefa de engenharia. Os tijolos estão prontos: modelos de embedding bons e baratos, vector stores que rodam dentro do Postgres, e LLMs com tool calling estável. O desafio agora é montar isso de um jeito que não exploda em produção." },
  { type: "h2", text: "O setup mínimo" },
  { type: "p", text: "Para o exemplo, vou usar três coisas: pgvector como armazenamento, text-embedding-3-small para vetorizar os documentos, e um modelo de chat para gerar a resposta final. Nada de framework — quero deixar explícito o que cada peça faz." },
  { type: "code", lang: "python", text: "from openai import OpenAI\nimport psycopg\n\nclient = OpenAI()\nconn = psycopg.connect(\"postgresql://localhost/blog\")\n\ndef embed(text: str) -> list[float]:\n    r = client.embeddings.create(\n        model=\"text-embedding-3-small\",\n        input=text,\n    )\n    return r.data[0].embedding" },
  { type: "p", text: "Aparentemente simples. Mas há decisões importantes escondidas: tamanho do chunk, sobreposição, normalização, e como você lida com documentos longos que não cabem numa única embedding." },
  { type: "h2", text: "Chunking: o detalhe que define a qualidade" },
  { type: "p", text: "A primeira versão que escrevi cortava textos a cada 500 tokens. Funcionava — até alguém perguntar algo cuja resposta atravessava dois chunks. A correção é trivial: sobreposição de uns 100 tokens entre chunks consecutivos. Ganho de qualidade desproporcional ao trabalho." },
  { type: "blockquote", text: "Boas heurísticas de chunking valem mais do que vector stores caros. Esse é o aprendizado mais barato que você pode ter." },
  { type: "p", text: "No próximo post entro na parte mais difícil: avaliação. Como medir se o seu RAG está realmente respondendo melhor do que o modelo cru." },
];

const RELATED_IDS = ["context-engineering", "agentes-llm-producao", "fine-tuning-vale-pena"];

// Categorias (com total de posts) — sidebar
const CATEGORIES = [
  { name: "Inteligência Artificial", slug: "ia", count: 18 },
  { name: "Python", slug: "python", count: 12 },
  { name: "Go", slug: "go", count: 9 },
  { name: "TypeScript", slug: "typescript", count: 8 },
  { name: "Arquitetura", slug: "arquitetura", count: 7 },
  { name: "DevOps", slug: "devops", count: 5 },
  { name: "Carreira", slug: "carreira", count: 3 },
];

// Arquivo de postagens por mês (estilo WordPress)
const ARCHIVE = [
  { year: 2026, months: [
    { label: "Abril", count: 4 },
    { label: "Março", count: 6 },
    { label: "Fevereiro", count: 3 },
    { label: "Janeiro", count: 5 },
  ] },
  { year: 2025, months: [
    { label: "Dezembro", count: 4 },
    { label: "Novembro", count: 7 },
    { label: "Outubro", count: 5 },
    { label: "Setembro", count: 2 },
  ] },
  { year: 2024, months: [
    { label: "Dezembro", count: 3 },
    { label: "Novembro", count: 4 },
  ] },
];

// Posts recentes para o widget da sidebar
const RECENT = POSTS.slice(0, 5).map(p => ({ id: p.id, title: p.title, date: p.date, hue: p.cover.hue }));

Object.assign(window, { POSTS, TAGS, POST_BODY, RELATED_IDS, CATEGORIES, ARCHIVE, RECENT });
