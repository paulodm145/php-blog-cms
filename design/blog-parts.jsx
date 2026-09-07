// Peças compartilhadas: cover placeholder, header, sidebar (widgets estilo
// WordPress), footer e um syntax highlight mínimo. Bootstrap 5 dá a grid;
// as classes .blog-* vêm de blog-theme.css.

function Cover({ hue, label, height, radius = 4, dark, className = "", style = {} }) {
  const sat = dark ? 24 : 30, l1 = dark ? 26 : 77, l2 = dark ? 17 : 89;
  const stripe = dark ? 0.08 : 0.11;
  return (
    <div className={`cover ${className}`} style={{
      height, borderRadius: radius,
      background: `linear-gradient(125deg, hsl(${hue} ${sat}% ${l1}%), hsl(${hue} ${sat - 8}% ${l2}%))`,
      ...style,
    }}>
      <div style={{
        position: "absolute", inset: 0,
        backgroundImage: `repeating-linear-gradient(45deg, rgba(0,0,0,${stripe}) 0 1px, transparent 1px 10px)`,
      }}/>
      {label && <div className="cover-label" style={{ color: dark ? "#fff" : "#000" }}>{label}</div>}
    </div>
  );
}

function SunIcon({ s = 14 }) {
  return <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>;
}
function MoonIcon({ s = 14 }) {
  return <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>;
}
function GithubIcon({ s = 15 }) {
  return <svg width={s} height={s} viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.57.1.78-.25.78-.55v-1.93c-3.2.7-3.87-1.54-3.87-1.54-.52-1.33-1.27-1.69-1.27-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.76 2.69 1.25 3.35.96.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.42-2.69 5.4-5.25 5.68.41.36.78 1.06.78 2.13v3.16c0 .31.21.66.79.55C20.21 21.39 23.5 17.08 23.5 12 23.5 5.65 18.35.5 12 .5z"/></svg>;
}
function LinkedinIcon({ s = 15 }) {
  return <svg width={s} height={s} viewBox="0 0 24 24" fill="currentColor"><path d="M19 0H5C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5zM8 19H5V8h3v11zM6.5 6.7c-.97 0-1.75-.78-1.75-1.75S5.53 3.2 6.5 3.2s1.75.78 1.75 1.75S7.47 6.7 6.5 6.7zM20 19h-3v-5.6c0-3.37-4-3.11-4 0V19h-3V8h3v1.77c1.4-2.59 7-2.78 7 2.48V19z"/></svg>;
}
function SearchIcon({ s = 14 }) {
  return <svg width={s} height={s} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>;
}

// Header: navbar Bootstrap enxuta
function SiteHeader({ dark, page = "home", container = "container", mono = false }) {
  const links = [
    { id: "home", label: "Artigos" },
    { id: "categorias", label: "Categorias" },
    { id: "sobre", label: "Sobre" },
  ];
  return (
    <header className="site-head py-3">
      <div className={`${container} d-flex align-items-center justify-content-between`}>
        <a href="#" className="brand" style={mono ? { fontFamily: "var(--mono)" } : undefined}>
          paulorb<span className="accent">.dev</span>
        </a>
        <nav className="d-flex align-items-center gap-4">
          {links.map(l => (
            <a key={l.id} href="#" className={`navlink${l.id === page ? " active" : ""}`}>{l.label}</a>
          ))}
          <button className="icon-btn" title={dark ? "modo claro" : "modo escuro"}>
            {dark ? <SunIcon/> : <MoonIcon/>}
          </button>
        </nav>
      </div>
    </header>
  );
}

function SiteFooter({ container = "container" }) {
  return (
    <footer className="site-foot py-4 mt-0">
      <div className={`${container} d-flex justify-content-between align-items-center`}>
        <span>paulorb.dev © 2026</span>
        <span className="d-flex gap-3">
          <a href="#">github</a><a href="#">linkedin</a><a href="#">rss</a>
        </span>
      </div>
    </footer>
  );
}

// ── Widgets da sidebar ──────────────────────────────────────────────────────

function WidgetSearch() {
  return (
    <div className="widget">
      <div className="searchbox">
        <input placeholder="buscar no blog..."/>
        <button aria-label="buscar"><SearchIcon/></button>
      </div>
    </div>
  );
}

function WidgetCategories() {
  return (
    <div className="widget">
      <h4 className="widget-title">Categorias</h4>
      <ul className="widget-list">
        {CATEGORIES.map(c => (
          <li key={c.slug}>
            <a href="#">{c.name}</a>
            <span className="count">{c.count}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function WidgetArchive() {
  return (
    <div className="widget">
      <h4 className="widget-title">Arquivo</h4>
      {ARCHIVE.map(y => (
        <div key={y.year}>
          <div className="archive-year">{y.year}</div>
          {y.months.map(m => (
            <a key={m.label} href="#" className="archive-month">
              <span>{m.label}</span>
              <span className="text-muted num">({m.count})</span>
            </a>
          ))}
        </div>
      ))}
    </div>
  );
}

function WidgetRecent({ dark }) {
  return (
    <div className="widget">
      <h4 className="widget-title">Posts recentes</h4>
      {RECENT.map(p => (
        <a key={p.id} href="#" className="recent-item">
          <Cover hue={p.hue} height={44} radius={4} dark={dark} className="recent-thumb" style={{ width: 44 }}/>
          <div className="min-w-0">
            <div className="recent-title">{p.title}</div>
            <div className="recent-date">{p.date}</div>
          </div>
        </a>
      ))}
    </div>
  );
}

function WidgetTags() {
  return (
    <div className="widget">
      <h4 className="widget-title">Tags</h4>
      <div className="d-flex flex-wrap gap-2">
        {TAGS.slice(0, 9).map(t => (
          <a key={t.name} href="#" className="chip-pill">{t.name}</a>
        ))}
      </div>
    </div>
  );
}

function WidgetAbout({ dark }) {
  return (
    <div className="widget">
      <h4 className="widget-title">Sobre o autor</h4>
      <div className="d-flex gap-3 align-items-start mb-3">
        <Cover hue={200} height={48} radius={24} dark={dark} style={{ width: 48, flexShrink: 0 }}/>
        <div>
          <div style={{ fontSize: ".9rem", fontWeight: 600 }}>Paulo RB</div>
          <div className="text-muted" style={{ fontSize: ".78rem", lineHeight: 1.45 }}>
            Engenheiro de software. Escrevo sobre IA e programação.
          </div>
        </div>
      </div>
      <div className="d-flex gap-3">
        <a href="#" className="d-flex align-items-center gap-2 text-muted" style={{ fontSize: ".78rem" }}><GithubIcon s={13}/> github</a>
        <a href="#" className="d-flex align-items-center gap-2 text-muted" style={{ fontSize: ".78rem" }}><LinkedinIcon s={13}/> linkedin</a>
      </div>
    </div>
  );
}

// Sidebar completa. `order` define quais widgets e em que sequência.
function Sidebar({ dark, order = ["search", "categories", "archive", "recent", "tags"] }) {
  const map = {
    search: <WidgetSearch key="search"/>,
    about: <WidgetAbout key="about" dark={dark}/>,
    categories: <WidgetCategories key="categories"/>,
    archive: <WidgetArchive key="archive"/>,
    recent: <WidgetRecent key="recent" dark={dark}/>,
    tags: <WidgetTags key="tags"/>,
  };
  return <aside>{order.map(k => map[k])}</aside>;
}

// ── Syntax highlight mínimo (sem dependências) ──────────────────────────────
function syntax(code, dark) {
  const colors = {
    kw: dark ? "#c8a8ff" : "#b48ef7", str: dark ? "#a8d8a8" : "#9fd39f",
    num: dark ? "#f5b87f" : "#f0b078", com: dark ? "#6b6660" : "#7d7871",
    base: "#e6e1d6",
  };
  let parts = [{ t: code, c: "base" }];
  const apply = (regex, color) => {
    const out = [];
    for (const p of parts) {
      if (p.c !== "base") { out.push(p); continue; }
      let last = 0, m; regex.lastIndex = 0;
      while ((m = regex.exec(p.t)) !== null) {
        if (m.index > last) out.push({ t: p.t.slice(last, m.index), c: "base" });
        out.push({ t: m[0], c: color });
        last = m.index + m[0].length;
      }
      if (last < p.t.length) out.push({ t: p.t.slice(last), c: "base" });
    }
    parts = out;
  };
  apply(/#[^\n]*/g, "com");
  apply(/"[^"]*"|'[^']*'/g, "str");
  apply(/\b(from|import|def|class|return|if|else|for|while|in|as|with|try|except|None|True|False|lambda|yield|async|await)\b/g, "kw");
  apply(/\b\d+(\.\d+)?\b/g, "num");
  return parts.map((p, i) => <span key={i} style={{ color: colors[p.c] }}>{p.t}</span>);
}

// Corpo do post renderizado a partir de POST_BODY
function Prose({ dark }) {
  return (
    <div className="prose">
      {POST_BODY.map((b, i) => {
        if (b.type === "p") return <p key={i}>{b.text}</p>;
        if (b.type === "h2") return <h2 key={i}>{b.text}</h2>;
        if (b.type === "blockquote") return <blockquote key={i}>{b.text}</blockquote>;
        if (b.type === "code") return (
          <div key={i} className="codeblock">
            <div className="lang">{b.lang}</div>
            <pre>{syntax(b.text, dark)}</pre>
          </div>
        );
        return null;
      })}
    </div>
  );
}

const COMMENTS = [
  { name: "Marina S.", time: "há 2 dias", text: "Excelente! Fiquei curioso sobre como você escolheu o tamanho do chunk na prática — foi empírico ou tem alguma heurística?" },
  { name: "Diego T.", time: "há 1 dia", text: "Dica valiosa sobre overlap. Quebrou pra mim em uns FAQs internos onde a resposta sempre cruzava parágrafos." },
  { name: "Ana C.", time: "há 4 horas", text: "Espero o post sobre avaliação. Esse é o gargalo aqui na empresa." },
];

function Comments() {
  return (
    <section className="mt-5 pt-4 border-t">
      <h3 className="widget-title">Comentários ({COMMENTS.length})</h3>
      {COMMENTS.map((c, i) => (
        <div key={i} className="comment">
          <div className="avatar">{c.name[0]}</div>
          <div className="flex-fill min-w-0">
            <div style={{ fontSize: ".8rem", marginBottom: ".25rem" }}>
              <strong style={{ fontWeight: 600 }}>{c.name}</strong>
              <span className="text-muted ms-2">{c.time}</span>
            </div>
            <p className="mb-0" style={{ fontSize: ".875rem", lineHeight: 1.6 }}>{c.text}</p>
          </div>
        </div>
      ))}
      <div className="mt-3">
        <textarea className="field" placeholder="deixe seu comentário..."/>
        <div className="d-flex justify-content-end mt-2">
          <button className="btn-accent">publicar</button>
        </div>
      </div>
    </section>
  );
}

Object.assign(window, {
  Cover, SiteHeader, SiteFooter, Sidebar, Prose, Comments, syntax,
  WidgetSearch, WidgetCategories, WidgetArchive, WidgetRecent, WidgetTags, WidgetAbout,
  SunIcon, MoonIcon, GithubIcon, LinkedinIcon, SearchIcon,
});
