// Variação B — Magazine + Sidebar
// Featured full-width no topo, grid de cards 2-col dentro de col-lg-8,
// sidebar col-lg-4. Header mono, tags em pill, hero de post com fundo.

function BCard({ post, dark, coverH = 150 }) {
  return (
    <article>
      <Cover hue={post.cover.hue} label={post.cover.label} height={coverH} radius={3} dark={dark}/>
      <div className="mt-3">
        <div className="text-muted num d-flex gap-2 align-items-center mb-2" style={{ fontSize: ".72rem" }}>
          <span>{post.date}</span><span className="opacity-50">·</span><span>{post.readTime}</span>
        </div>
        <h3 style={{ fontSize: "1.1rem", fontWeight: 600, lineHeight: 1.3, marginBottom: ".5rem" }}>
          <a href="#">{post.title}</a>
        </h3>
        <p className="text-muted mb-2" style={{ fontSize: ".85rem", lineHeight: 1.55,
          display: "-webkit-box", WebkitLineClamp: 3, WebkitBoxOrient: "vertical", overflow: "hidden" }}>
          {post.excerpt}
        </p>
        <div className="d-flex gap-2 flex-wrap">
          {post.tags.map(t => <span key={t} className="chip-pill">{t}</span>)}
        </div>
      </div>
    </article>
  );
}

function BShell({ dark, page, children }) {
  return (
    <div className="blog" data-skin="b" data-mode={dark ? "dark" : "light"}>
      <SiteHeader dark={dark} page={page} container="container-lg" mono/>
      {children}
      <SiteFooter container="container-lg"/>
    </div>
  );
}

// Destaque full-width: ocupa toda a largura acima da grid conteúdo+sidebar.
function BFeatured({ post, dark }) {
  return (
    <article className="row g-4 align-items-center pb-5 mb-5 border-b">
      <div className="col-md-7">
        <Cover hue={post.cover.hue} label={post.cover.label} height={330} radius={4} dark={dark}/>
      </div>
      <div className="col-md-5 d-flex flex-column justify-content-center">
        <div className="accent mb-2" style={{ fontSize: ".68rem", letterSpacing: ".12em", textTransform: "uppercase", fontWeight: 600 }}>
          ★ Em destaque
        </div>
        <h2 style={{ fontSize: "2rem", fontWeight: 700, lineHeight: 1.15, marginBottom: ".75rem" }}>
          <a href="#">{post.title}</a>
        </h2>
        <p className="text-muted mb-3" style={{ fontSize: "1rem", lineHeight: 1.6 }}>{post.excerpt}</p>
        <div className="text-muted num d-flex gap-2 align-items-center mb-3" style={{ fontSize: ".78rem" }}>
          <span>{post.date}</span><span className="opacity-50">·</span><span>{post.readTime}</span>
        </div>
        <div className="d-flex gap-2 flex-wrap">
          {post.tags.map(t => <span key={t} className="chip-pill">{t}</span>)}
        </div>
      </div>
    </article>
  );
}

function BHomePage({ dark }) {
  return (
    <BShell dark={dark} page="home">
      <main className="container-lg py-5">
        <BFeatured post={POSTS[0]} dark={dark}/>
        <div className="row g-5">
          <div className="col-lg-8">
            <div className="d-flex justify-content-between align-items-baseline mb-4">
              <h2 className="widget-title mb-0">Mais artigos</h2>
              <a href="#" className="accent" style={{ fontSize: ".78rem", fontWeight: 500 }}>arquivo completo →</a>
            </div>
            <div className="row g-4">
              {POSTS.slice(1).map(p => (
                <div className="col-md-6" key={p.id}><BCard post={p} dark={dark}/></div>
              ))}
            </div>
          </div>
          <div className="col-lg-4">
            <Sidebar dark={dark} order={["search", "categories", "archive", "recent", "tags"]}/>
          </div>
        </div>
      </main>
    </BShell>
  );
}

function BPostPage({ dark }) {
  const post = POSTS[0];
  const related = RELATED_IDS.map(id => POSTS.find(p => p.id === id)).filter(Boolean);
  return (
    <BShell dark={dark} page="home">
      <div className="bg-surface border-b">
        <div className="container-lg py-5">
          <div className="row">
            <div className="col-lg-8">
              <a href="#" className="accent" style={{ fontSize: ".78rem", fontWeight: 500 }}>← voltar</a>
              <div className="d-flex gap-2 flex-wrap mt-3 mb-3">
                {post.tags.map(t => <span key={t} className="chip-pill">{t}</span>)}
              </div>
              <h1 style={{ fontSize: "2.3rem", fontWeight: 700, lineHeight: 1.13 }}>{post.title}</h1>
              <div className="text-muted num d-flex gap-2 align-items-center" style={{ fontSize: ".85rem" }}>
                <span style={{ color: "var(--text)", fontWeight: 500 }}>Paulo RB</span>
                <span className="opacity-50">·</span><span>{post.date}</span>
                <span className="opacity-50">·</span><span>{post.readTime} de leitura</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <main className="container-lg py-5">
        <div className="row g-5">
          <div className="col-lg-8">
            <Prose dark={dark}/>
            <Comments/>
          </div>
          <div className="col-lg-4">
            <Sidebar dark={dark} order={["about", "categories", "archive", "recent"]}/>
          </div>
        </div>
      </main>
      <section className="bg-surface border-t py-5">
        <div className="container-lg">
          <h3 className="widget-title">Continue lendo</h3>
          <div className="row g-4">
            {related.map(p => (
              <div className="col-md-4" key={p.id}><BCard post={p} dark={dark} coverH={130}/></div>
            ))}
          </div>
        </div>
      </section>
    </BShell>
  );
}

function BCategoriesPage({ dark }) {
  return (
    <BShell dark={dark} page="categorias">
      <main className="container-lg py-5">
        <div className="row g-5">
          <div className="col-lg-8">
            <div className="accent mb-2" style={{ fontSize: ".7rem", letterSpacing: ".12em", textTransform: "uppercase", fontWeight: 600 }}>Categorias</div>
            <h1 className="mb-1" style={{ fontSize: "1.9rem", fontWeight: 700 }}>Tópicos</h1>
            <p className="text-muted mb-4" style={{ fontSize: ".9rem" }}>
              {CATEGORIES.length} categorias · {CATEGORIES.reduce((s, c) => s + c.count, 0)} posts
            </p>
            <div className="row g-3">
              {CATEGORIES.map(c => (
                <div className="col-sm-6" key={c.slug}>
                  <a href="#" className="widget d-block mb-0 h-100">
                    <div className="d-flex justify-content-between align-items-baseline">
                      <span style={{ fontSize: ".95rem", fontWeight: 600 }}>{c.name}</span>
                      <span className="count">{c.count}</span>
                    </div>
                    <div className="text-muted mt-1" style={{ fontSize: ".76rem", fontFamily: "var(--mono)" }}>/{c.slug}</div>
                  </a>
                </div>
              ))}
            </div>
            <h3 className="widget-title mt-5">Nuvem de tags</h3>
            <div className="d-flex flex-wrap gap-2">
              {TAGS.map(t => (
                <a key={t.name} href="#" className="widget mb-0 d-inline-flex align-items-baseline gap-2"
                   style={{ padding: ".4rem .8rem", borderRadius: 999, fontSize: `${0.8 + Math.min(t.count, 18) * 0.028}rem`, fontWeight: 500 }}>
                  <span>{t.name}</span><span className="text-muted num" style={{ fontSize: ".7rem" }}>{t.count}</span>
                </a>
              ))}
            </div>
          </div>
          <div className="col-lg-4">
            <Sidebar dark={dark} order={["search", "archive", "recent"]}/>
          </div>
        </div>
      </main>
    </BShell>
  );
}

function BAboutPage({ dark }) {
  return (
    <BShell dark={dark} page="sobre">
      <main className="container-lg py-5">
        <div className="row g-5">
          <div className="col-lg-8">
            <div className="accent mb-2" style={{ fontSize: ".7rem", letterSpacing: ".12em", textTransform: "uppercase", fontWeight: 600 }}>Sobre</div>
            <h1 className="mb-4" style={{ fontSize: "2.1rem", fontWeight: 700, lineHeight: 1.15 }}>
              Engenheiro escrevendo o que aprende.
            </h1>
            <div className="prose">
              <p>Olá. Sou Paulo, engenheiro de software. Trabalho com sistemas distribuídos há mais de uma década e nos últimos anos venho me dedicando à interseção entre engenharia tradicional e modelos de linguagem.</p>
              <p>Este blog é onde escrevo o que aprendo. Não é um curso, não é uma newsletter — é um caderno técnico aberto. A maioria dos posts nasce de problemas reais que tive que resolver e da minha vontade de organizar o raciocínio antes de esquecer.</p>
              <p>Os temas variam: Python, Go, TypeScript, infraestrutura, e cada vez mais sobre como construir produtos em cima de LLMs sem entrar em armadilhas óbvias.</p>
            </div>
            <div className="row g-3">
              <div className="col-sm-6">
                <a href="#" className="widget d-flex gap-3 align-items-center mb-0">
                  <GithubIcon s={18}/>
                  <div>
                    <div style={{ fontSize: ".85rem", fontWeight: 600 }}>GitHub</div>
                    <div className="text-muted" style={{ fontSize: ".75rem" }}>@paulorb</div>
                  </div>
                </a>
              </div>
              <div className="col-sm-6">
                <a href="#" className="widget d-flex gap-3 align-items-center mb-0">
                  <LinkedinIcon s={18}/>
                  <div>
                    <div style={{ fontSize: ".85rem", fontWeight: 600 }}>LinkedIn</div>
                    <div className="text-muted" style={{ fontSize: ".75rem" }}>/in/paulorb</div>
                  </div>
                </a>
              </div>
            </div>
          </div>
          <div className="col-lg-4">
            <Sidebar dark={dark} order={["categories", "archive", "recent"]}/>
          </div>
        </div>
      </main>
    </BShell>
  );
}

Object.assign(window, { BHomePage, BPostPage, BCategoriesPage, BAboutPage, BCard });
