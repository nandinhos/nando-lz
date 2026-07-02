<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>nando-lz — Laravel + Filament Evergreen Starter</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
{{-- Landing abstraída do Claude Design (Landing.dc.html) com dados reais via App\Support\Stack --}}
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  ::selection { background: color-mix(in oklab, var(--accent) 40%, transparent); }

  :root, [data-theme="dark"] {
    --bg:#070e1c; --grad:#0b1832; --card:#0d1c38; --border:rgba(120,165,220,.14);
    --fg:#e7eef9; --muted:#93a8c8; --nav:rgba(7,14,28,.72); --chip:rgba(255,255,255,.03);
    --accent:#25cfe4; --accent-text:#25cfe4; --cta-fg:#04121f;
  }
  [data-theme="light"] {
    --bg:#eef3fa; --grad:#dde8f6; --card:#ffffff; --border:rgba(12,32,64,.10);
    --fg:#0a1a30; --muted:#4a5d7c; --nav:rgba(238,243,250,.80); --chip:rgba(10,26,48,.03);
    --accent:#25cfe4; --accent-text:#0a7f94; --cta-fg:#04121f;
  }

  body { background:var(--bg); color:var(--fg); min-height:100vh; font-family:'IBM Plex Sans',sans-serif; transition:background .4s ease,color .4s ease; }
  .mono { font-family:'JetBrains Mono',monospace; }
  .display { font-family:'Space Grotesk',sans-serif; }

  @keyframes floatGlow { 0%,100% { transform:translate(-50%,0) scale(1); opacity:.9; } 50% { transform:translate(-50%,-14px) scale(1.05); opacity:1; } }
  @keyframes caret { 0%,49% { opacity:1; } 50%,100% { opacity:0; } }

  .fx { position:fixed; inset:0; pointer-events:none; overflow:hidden; z-index:0; }
  .fx-glow { position:absolute; top:-160px; left:50%; width:min(1100px,120vw); height:520px; border-radius:50%;
    background:radial-gradient(closest-side, color-mix(in oklab, var(--accent) 26%, transparent), transparent);
    filter:blur(30px); animation:floatGlow 9s ease-in-out infinite; }
  .fx-grad { position:absolute; inset:0; background:linear-gradient(180deg, var(--grad) 0%, transparent 34%); }

  header { position:sticky; top:0; z-index:50; backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); background:var(--nav); border-bottom:1px solid var(--border); }
  .shell { max-width:1180px; margin:0 auto; padding:0 24px; }
  .nav-row { padding:14px 0; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
  .brand { display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--fg); }
  .brand-gem { width:16px; height:16px; transform:rotate(45deg); border-radius:3px; background:var(--accent); box-shadow:0 0 16px color-mix(in oklab, var(--accent) 60%, transparent); }
  .brand-name { font-weight:700; font-size:18px; letter-spacing:-.01em; }
  nav.links { display:flex; gap:22px; margin-left:8px; flex-wrap:wrap; }
  nav.links a { text-decoration:none; color:var(--muted); font-size:14px; font-weight:500; }
  .nav-actions { margin-left:auto; display:flex; align-items:center; gap:10px; }
  .seg { display:flex; padding:3px; border-radius:10px; background:var(--chip); border:1px solid var(--border); }
  .seg button { padding:5px 12px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; font-family:'JetBrains Mono',monospace; border:none; transition:all .2s; background:transparent; color:var(--muted); }
  .seg button.on { background:var(--accent); color:var(--cta-fg); }
  .icon-btn { width:38px; height:38px; display:flex; align-items:center; justify-content:center; border-radius:10px; cursor:pointer; color:var(--fg); background:var(--chip); border:1px solid var(--border); }
  .gh-btn { display:flex; align-items:center; gap:6px; padding:8px 15px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:600; color:var(--fg); background:var(--chip); border:1px solid var(--border); }
  .gh-btn span { color:var(--accent-text); }
  [data-theme="dark"] .only-light, [data-theme="light"] .only-dark { display:none; }

  main { position:relative; z-index:1; }

  .hero { padding:clamp(56px,9vw,110px) 0 clamp(40px,6vw,72px); display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:clamp(36px,5vw,64px); align-items:center; }
  .badge { display:inline-flex; align-items:center; gap:8px; padding:6px 13px; border-radius:100px; font-size:12.5px; font-weight:600; letter-spacing:.01em; color:var(--accent-text);
    background:color-mix(in oklab, var(--accent) 12%, transparent); border:1px solid color-mix(in oklab, var(--accent) 30%, transparent); }
  .badge i { width:6px; height:6px; border-radius:50%; background:var(--accent); }
  h1 { font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:clamp(2.2rem,5vw,4.1rem); line-height:1.04; letter-spacing:-.025em; margin:22px 0 0; text-wrap:balance; }
  .hero-sub { font-size:clamp(1rem,1.6vw,1.2rem); line-height:1.6; color:var(--muted); margin:22px 0 0; max-width:38ch; text-wrap:pretty; }
  .cta-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:30px; }
  .btn-primary { padding:13px 24px; border-radius:11px; text-decoration:none; font-weight:600; font-size:15px; color:var(--cta-fg); background:var(--accent); box-shadow:0 8px 26px -8px color-mix(in oklab, var(--accent) 65%, transparent); }
  .btn-ghost { padding:13px 24px; border-radius:11px; text-decoration:none; font-weight:600; font-size:15px; color:var(--fg); background:transparent; border:1px solid var(--border); }
  .hero-bullets { display:flex; gap:18px; flex-wrap:wrap; margin-top:26px; font-family:'JetBrains Mono',monospace; font-size:12.5px; color:var(--muted); }

  /* Monitor: terminal do design com o estado real da última atualização */
  .term { border-radius:16px; overflow:hidden; background:#0a1526; border:1px solid rgba(140,180,230,.16); box-shadow:0 40px 90px -40px rgba(0,0,0,.6); }
  .term-bar { display:flex; align-items:center; gap:8px; padding:13px 16px; border-bottom:1px solid rgba(255,255,255,.07); background:rgba(255,255,255,.02); }
  .term-dot { width:11px; height:11px; border-radius:50%; }
  .term-title { margin-left:8px; font-family:'JetBrains Mono',monospace; font-size:12px; color:#7d90ad; }
  .term-body { padding:20px 20px 24px; font-family:'JetBrains Mono',monospace; font-size:13px; line-height:1.9; color:#cdd9ec; }
  .term-body .p { color:#25cfe4; }
  .term-body .dim { color:#6f83a1; }
  .term-body .ok { color:#28c840; }
  .term-body .warn { color:#febc2e; }
  .term-body a { color:#cdd9ec; }
  .caret { display:inline-block; width:8px; height:15px; margin-left:6px; background:#25cfe4; vertical-align:-2px; animation:caret 1.1s step-end infinite; }

  .copybar-wrap { padding:18px 0 clamp(56px,8vw,96px); }
  .copybar { display:flex; align-items:center; gap:14px; flex-wrap:wrap; padding:16px 20px; border-radius:14px; background:var(--card); border:1px solid var(--border); }
  .copybar .label { font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--muted); white-space:nowrap; }
  .copybar code { flex:1; min-width:220px; font-family:'JetBrains Mono',monospace; font-size:clamp(12.5px,1.5vw,15px); color:var(--fg); }
  .copybar code .p, .step code .p { color:var(--accent-text); }
  .btn-copy { padding:8px 16px; border-radius:9px; cursor:pointer; font-weight:600; font-size:13px; color:var(--cta-fg); background:var(--accent); border:none; white-space:nowrap; }

  section.block { padding:clamp(40px,6vw,72px) 0; }
  .kicker { font-family:'JetBrains Mono',monospace; font-size:12.5px; font-weight:600; letter-spacing:.14em; text-transform:uppercase; color:var(--accent-text); }
  h2 { font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:clamp(1.7rem,3.4vw,2.7rem); letter-spacing:-.02em; line-height:1.1; margin:14px 0 0; text-wrap:balance; }
  .block-sub { font-size:clamp(1rem,1.5vw,1.12rem); color:var(--muted); margin:14px 0 0; line-height:1.55; }
  .block-head { max-width:640px; }

  .grid-features { margin-top:44px; display:grid; grid-template-columns:repeat(auto-fit,minmax(268px,1fr)); gap:16px; }
  .feature { padding:26px 24px 28px; border-radius:16px; background:var(--card); border:1px solid var(--border); transition:transform .25s ease,border-color .25s ease; }
  .feature:hover { transform:translateY(-4px); border-color:color-mix(in oklab, var(--accent) 45%, transparent); }
  .feature .num { font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:600; color:var(--accent-text); }
  .feature .rule { display:block; width:26px; height:2px; margin:12px 0 16px; background:var(--accent); opacity:.7; }
  .feature h3 { font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:1.2rem; margin:0; letter-spacing:-.01em; }
  .feature p { font-size:.95rem; color:var(--muted); margin:10px 0 0; line-height:1.55; }

  .pills { margin-top:36px; display:flex; flex-wrap:wrap; gap:12px; }
  .pill { display:inline-flex; align-items:center; gap:9px; padding:11px 18px; border-radius:100px; font-family:'JetBrains Mono',monospace; font-size:14px; font-weight:500; color:var(--fg); background:var(--card); border:1px solid var(--border); }
  .pill i { width:7px; height:7px; border-radius:50%; background:var(--accent); }
  .pill .v { color:var(--accent-text); }

  .steps { margin-top:40px; display:flex; flex-direction:column; gap:14px; }
  .step { display:flex; align-items:center; gap:18px; flex-wrap:wrap; padding:18px 22px; border-radius:14px; background:var(--card); border:1px solid var(--border); }
  .step .num { width:40px; height:40px; flex-shrink:0; display:flex; align-items:center; justify-content:center; border-radius:11px; font-family:'JetBrains Mono',monospace; font-weight:600; font-size:15px; color:var(--accent-text);
    background:color-mix(in oklab, var(--accent) 12%, transparent); border:1px solid color-mix(in oklab, var(--accent) 28%, transparent); }
  .step .body { flex:1; min-width:200px; }
  .step .t { font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:1.02rem; margin-bottom:6px; }
  .step code { font-family:'JetBrains Mono',monospace; font-size:13.5px; color:var(--muted); }
  .btn-copy-ghost { padding:8px 15px; border-radius:9px; cursor:pointer; font-weight:600; font-size:12.5px; color:var(--fg); background:var(--chip); border:1px solid var(--border); white-space:nowrap; }

  .cta-final-wrap { padding:clamp(48px,7vw,88px) 0; }
  .cta-final { position:relative; overflow:hidden; padding:clamp(40px,6vw,72px) clamp(28px,5vw,64px); border-radius:24px; background:linear-gradient(135deg, var(--card), var(--grad)); border:1px solid color-mix(in oklab, var(--accent) 26%, transparent); text-align:center; }
  .cta-final .halo { position:absolute; top:-120px; left:50%; transform:translateX(-50%); width:520px; height:320px; border-radius:50%; background:radial-gradient(closest-side, color-mix(in oklab, var(--accent) 30%, transparent), transparent); pointer-events:none; }
  .cta-final h2 { font-size:clamp(1.9rem,4vw,3.1rem); letter-spacing:-.025em; line-height:1.06; margin:0; }
  .cta-final .sub { font-size:clamp(1rem,1.6vw,1.2rem); color:var(--muted); margin:16px auto 0; max-width:46ch; line-height:1.55; }
  .cta-final .cmd { display:inline-flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content:center; margin-top:28px; padding:10px 10px 10px 20px; border-radius:14px; background:#0a1526; border:1px solid rgba(140,180,230,.18); }
  .cta-final .cmd code { font-family:'JetBrains Mono',monospace; font-size:clamp(12px,1.4vw,14px); color:#cdd9ec; }
  .cta-final .cmd code .p { color:#25cfe4; }
  .cta-final .gh-link { display:inline-block; margin-top:20px; font-size:14px; font-weight:600; color:var(--accent-text); text-decoration:none; }

  footer { position:relative; z-index:1; border-top:1px solid var(--border); margin-top:20px; }
  .foot-row { padding:34px 0; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; }
  .foot-brand { display:flex; align-items:center; gap:10px; }
  .foot-gem { width:14px; height:14px; transform:rotate(45deg); border-radius:3px; background:var(--accent); }
  .foot-tag { color:var(--muted); font-size:14px; margin-left:6px; }
  .foot-rights { font-family:'JetBrains Mono',monospace; font-size:12.5px; color:var(--muted); }
</style>
</head>
<body>
@php
    $v = $stack['versions'];
    $last = $stack['lastUpdate'];
    $githubUrl = 'https://github.com/nandinhos/nando-lz';
    $cloneCmd = 'git clone https://github.com/nandinhos/nando-lz.git';
@endphp

<div class="fx">
  <div class="fx-glow"></div>
  <div class="fx-grad"></div>
</div>

<header>
  <div class="shell nav-row">
    <a href="#top" class="brand">
      <span class="brand-gem"></span>
      <span class="brand-name display">nando-lz</span>
    </a>
    <nav class="links">
      <a href="#features" data-i18n="navFeatures">Recursos</a>
      <a href="#stack" data-i18n="navStack">Stack</a>
      <a href="#install" data-i18n="navInstall">Instalação</a>
    </nav>
    <div class="nav-actions">
      <div class="seg" id="langSeg">
        <button type="button" data-lang="pt" class="on">PT</button>
        <button type="button" data-lang="en">EN</button>
      </div>
      <button type="button" class="icon-btn" id="themeBtn" aria-label="Alternar tema">
        <svg class="only-dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg>
        <svg class="only-light" width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A8.6 8.6 0 1 1 11.2 3a6.7 6.7 0 0 0 9.8 9.8z"/></svg>
      </button>
      <a href="{{ $githubUrl }}" target="_blank" rel="noopener" class="gh-btn">GitHub <span>↗</span></a>
    </div>
  </div>
</header>

<main id="top" class="shell">

  <section class="hero">
    <div>
      <span class="badge"><i></i><span data-i18n="badge">Laravel + Filament — evergreen e pronto</span></span>
      <h1 data-i18n="heroTitle">A base premium para seus próximos projetos Laravel</h1>
      <p class="hero-sub" data-i18n="heroSub">Starter limpo e reproduzível com três painéis Filament, autenticação segura, testes Pest e manutenção automatizada — pule o boilerplate e comece a construir de verdade.</p>
      <div class="cta-row">
        <a href="#install" class="btn-primary" data-i18n="ctaPrimary">Começar agora</a>
        <a href="{{ $githubUrl }}" target="_blank" rel="noopener" class="btn-ghost" data-i18n="ctaSecondary">Ver no GitHub</a>
      </div>
      <div class="hero-bullets">
        <span>◇ MIT License</span><span>◇ PHP {{ $v['PHP'] ? preg_replace('/^(\d+\.\d+).*/', '$1', $v['PHP']) : '8.3' }}+</span><span>◇ Evergreen</span>
      </div>
    </div>

    {{-- Monitor: estado real da stack e da última atualização instalada --}}
    <div>
      <div class="term">
        <div class="term-bar">
          <span class="term-dot" style="background:#ff5f57"></span>
          <span class="term-dot" style="background:#febc2e"></span>
          <span class="term-dot" style="background:#28c840"></span>
          <span class="term-title">monitor — nando-lz</span>
        </div>
        <div class="term-body">
          <div><span class="p">$</span> resolve-stack.sh</div>
          <div class="dim">Laravel <span style="color:#cdd9ec">{{ $v['Laravel'] ?? '—' }}</span> · Filament <span style="color:#cdd9ec">{{ $v['Filament'] ?? '—' }}</span> · Livewire <span style="color:#cdd9ec">{{ $v['Livewire'] ?? '—' }}</span></div>
          <div class="dim">PHP <span style="color:#cdd9ec">{{ $v['PHP'] }}</span> · PostgreSQL <span style="color:#cdd9ec">{{ $v['PostgreSQL'] ?? 'offline' }}</span> · Pest <span style="color:#cdd9ec">{{ $v['Pest'] ?? '—' }}</span></div>
          <div style="margin-top:6px"><span class="p">$</span> <span data-i18n="monitorCmd">última atualização instalada</span></div>
          @if ($last)
            <div>
              @if ($last['ok'] === false)
                <span class="warn">!</span>
              @else
                <span class="ok">✓</span>
              @endif
              {{ $last['date'] }} — <a href="{{ $githubUrl }}/blob/main/{{ $last['path'] }}" target="_blank" rel="noopener" data-i18n="monitorReport">relatório do ciclo</a>
            </div>
          @else
            <div class="dim" data-i18n="monitorNone">nenhum ciclo registrado ainda</div>
          @endif
          <div class="dim">release <span class="warn">{{ $stack['release'] ? 'v'.$stack['release'] : '—' }}</span> · build <span style="color:#cdd9ec">{{ $stack['build'] }}</span></div>
          <div><span class="p">$</span><span class="caret"></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="copybar-wrap">
    <div class="copybar">
      <span class="label" data-i18n="installLabel">Clone e instale</span>
      <code><span class="p">$</span> <span id="heroCmd">{{ $cloneCmd }}</span></code>
      <button type="button" class="btn-copy" data-copy="#heroCmd" data-i18n="copy">Copiar</button>
    </div>
  </section>

  <section id="features" class="block">
    <div class="block-head">
      <span class="kicker" data-i18n="featuresKicker">Recursos</span>
      <h2 data-i18n="featuresTitle">Tudo que você precisa, já configurado</h2>
      <p class="block-sub" data-i18n="featuresSub">Menos boilerplate, mais produto no ar. Cada peça pensada para você estender sem lutar contra a estrutura.</p>
    </div>
    <div class="grid-features" id="featureGrid">
      {{-- Conteúdo preenchido pelo dicionário i18n (features reais do starter) --}}
    </div>
  </section>

  <section id="stack" class="block">
    <div class="block-head">
      <span class="kicker">Stack</span>
      <h2 data-i18n="stackTitle">Versões instaladas agora, direto do composer.lock</h2>
      <p class="block-sub" data-i18n="stackSub">Nada de números decorativos: o que você vê abaixo é o que está travado e testado neste exato build.</p>
    </div>
    <div class="pills">
      @foreach ($v as $name => $version)
        @continue($version === null)
        <span class="pill"><i></i>{{ $name }} <span class="v">{{ $name === 'PostgreSQL' ? preg_replace('/\s.*/', '', $version) : $version }}</span></span>
      @endforeach
      <span class="pill"><i></i>Tailwind CSS</span>
      <span class="pill"><i></i>Docker</span>
    </div>
  </section>

  <section id="install" class="block">
    <div class="block-head">
      <span class="kicker" data-i18n="installKicker">Instalação</span>
      <h2 data-i18n="installTitle">No ar em três passos</h2>
      <p class="block-sub" data-i18n="installSub">Do clone ao primeiro serve, sem fricção — em modo Local ou Docker.</p>
    </div>
    <div class="steps">
      <div class="step">
        <span class="num">01</span>
        <div class="body">
          <div class="t" data-i18n="step1">Clone o repositório</div>
          <code><span class="p">$</span> <span id="cmd1">{{ $cloneCmd }}</span></code>
        </div>
        <button type="button" class="btn-copy-ghost" data-copy="#cmd1" data-i18n="copy">Copiar</button>
      </div>
      <div class="step">
        <span class="num">02</span>
        <div class="body">
          <div class="t" data-i18n="step2">Instale — Local ou Docker</div>
          <code><span class="p">$</span> <span id="cmd2">cd nando-lz && ./scripts/install.sh</span></code>
        </div>
        <button type="button" class="btn-copy-ghost" data-copy="#cmd2" data-i18n="copy">Copiar</button>
      </div>
      <div class="step">
        <span class="num">03</span>
        <div class="body">
          <div class="t" data-i18n="step3">Suba o servidor</div>
          <code><span class="p">$</span> <span id="cmd3">php artisan serve</span></code>
        </div>
        <button type="button" class="btn-copy-ghost" data-copy="#cmd3" data-i18n="copy">Copiar</button>
      </div>
    </div>
  </section>

  <section class="cta-final-wrap">
    <div class="cta-final">
      <div class="halo"></div>
      <div style="position:relative">
        <h2 class="display" data-i18n="finalTitle">Pronto para construir?</h2>
        <p class="sub" data-i18n="finalSub">Clone, personalize e leve para produção. A estrutura já está resolvida — o resto é com você.</p>
        <div class="cmd">
          <code><span class="p">$</span> <span id="finalCmd">{{ $cloneCmd }}</span></code>
          <button type="button" class="btn-copy" data-copy="#finalCmd" data-i18n="copy">Copiar</button>
        </div>
        <div>
          <a href="{{ $githubUrl }}" target="_blank" rel="noopener" class="gh-link"><span data-i18n="ctaSecondary">Ver no GitHub</span> ↗</a>
        </div>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="shell foot-row">
    <div class="foot-brand">
      <span class="foot-gem"></span>
      <span class="display" style="font-weight:700;font-size:16px">nando-lz</span>
      <span class="foot-tag" data-i18n="footerTag">Starter Laravel + Filament, evergreen por automação.</span>
    </div>
    <span class="foot-rights" data-i18n="footerRights">Feito para a comunidade Laravel.</span>
  </div>
</footer>

<script>
  // Tema (persistido) -------------------------------------------------------
  const root = document.documentElement;
  root.dataset.theme = localStorage.getItem('nlz-theme') || 'dark';
  document.getElementById('themeBtn').addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('nlz-theme', root.dataset.theme);
  });

  // i18n PT/EN --------------------------------------------------------------
  const I18N = {
    pt: {
      navFeatures: 'Recursos', navStack: 'Stack', navInstall: 'Instalação',
      badge: 'Laravel + Filament — evergreen e pronto',
      heroTitle: 'A base premium para seus próximos projetos Laravel',
      heroSub: 'Starter limpo e reproduzível com três painéis Filament, autenticação segura, testes Pest e manutenção automatizada — pule o boilerplate e comece a construir de verdade.',
      ctaPrimary: 'Começar agora', ctaSecondary: 'Ver no GitHub',
      installLabel: 'Clone e instale', copy: 'Copiar', copied: 'Copiado!',
      monitorCmd: 'última atualização instalada', monitorReport: 'relatório do ciclo', monitorNone: 'nenhum ciclo registrado ainda',
      featuresKicker: 'Recursos', featuresTitle: 'Tudo que você precisa, já configurado',
      featuresSub: 'Menos boilerplate, mais produto no ar. Cada peça pensada para você estender sem lutar contra a estrutura.',
      stackTitle: 'Versões instaladas agora, direto do composer.lock',
      stackSub: 'Nada de números decorativos: o que você vê abaixo é o que está travado e testado neste exato build.',
      installKicker: 'Instalação', installTitle: 'No ar em três passos', installSub: 'Do clone ao primeiro serve, sem fricção — em modo Local ou Docker.',
      step1: 'Clone o repositório', step2: 'Instale — Local ou Docker', step3: 'Suba o servidor',
      finalTitle: 'Pronto para construir?', finalSub: 'Clone, personalize e leve para produção. A estrutura já está resolvida — o resto é com você.',
      footerTag: 'Starter Laravel + Filament, evergreen por automação.', footerRights: 'Feito para a comunidade Laravel.',
      features: [
        { n: '01', t: 'Três painéis Filament', d: 'ops, admin e support com autenticação, página de perfil e tema próprio — prontos para receber o seu domínio.' },
        { n: '02', t: 'Logout seguro por padrão', d: 'POST /logout nativo: sessão encerrada e invalidada, token CSRF regenerado. GET responde 405.' },
        { n: '03', t: 'superadmin:create', d: 'Bootstrap do primeiro admin com bloqueio de duplicidade e senha forte obrigatória fora de local.' },
        { n: '04', t: 'Evergreen por automação', d: 'Renovate, agente de IA e CI em três camadas mantêm a stack atualizada — nunca com merge cego.' },
        { n: '05', t: 'Testado com Pest', d: 'Suíte de sanidade sobre PostgreSQL cobrindo painéis, login, logout e migrations, na matriz do CI.' },
        { n: '06', t: 'Local & Docker', d: 'Instalação idempotente nos dois modos; container não-root com porta alta e banco de teste isolado.' }
      ]
    },
    en: {
      navFeatures: 'Features', navStack: 'Stack', navInstall: 'Install',
      badge: 'Laravel + Filament — evergreen & ready',
      heroTitle: 'The premium foundation for your next Laravel projects',
      heroSub: 'A clean, reproducible starter with three Filament panels, secure auth, Pest tests and automated maintenance — skip the boilerplate and start building for real.',
      ctaPrimary: 'Get started', ctaSecondary: 'View on GitHub',
      installLabel: 'Clone & install', copy: 'Copy', copied: 'Copied!',
      monitorCmd: 'latest installed update', monitorReport: 'cycle report', monitorNone: 'no cycle recorded yet',
      featuresKicker: 'Features', featuresTitle: 'Everything you need, already wired',
      featuresSub: 'Less boilerplate, more product shipped. Every piece built to be extended without fighting the structure.',
      stackTitle: 'Versions installed right now, straight from composer.lock',
      stackSub: 'No decorative numbers: what you see below is what is locked and tested in this exact build.',
      installKicker: 'Install', installTitle: 'Live in three steps', installSub: 'From clone to first serve, without friction — Local or Docker.',
      step1: 'Clone the repository', step2: 'Install — Local or Docker', step3: 'Start the server',
      finalTitle: 'Ready to build?', finalSub: 'Clone, customize and ship to production. The structure is solved — the rest is up to you.',
      footerTag: 'Laravel + Filament starter, evergreen by automation.', footerRights: 'Made for the Laravel community.',
      features: [
        { n: '01', t: 'Three Filament panels', d: 'ops, admin and support with auth, profile page and their own theme — ready for your domain.' },
        { n: '02', t: 'Secure logout by default', d: 'Native POST /logout: session ended and invalidated, CSRF token regenerated. GET returns 405.' },
        { n: '03', t: 'superadmin:create', d: 'First-admin bootstrap with duplicate lock and strong password required outside local.' },
        { n: '04', t: 'Evergreen by automation', d: 'Renovate, an AI agent and CI in three layers keep the stack updated — never with blind merges.' },
        { n: '05', t: 'Tested with Pest', d: 'Sanity suite on PostgreSQL covering panels, login, logout and migrations, on the CI matrix.' },
        { n: '06', t: 'Local & Docker', d: 'Idempotent install in both modes; non-root container with a high port and an isolated test database.' }
      ]
    }
  };

  let lang = localStorage.getItem('nlz-lang') || 'pt';
  const langSeg = document.getElementById('langSeg');

  function renderFeatures(t) {
    document.getElementById('featureGrid').innerHTML = t.features.map(f => `
      <div class="feature">
        <span class="num">${f.n}</span>
        <span class="rule"></span>
        <h3>${f.t}</h3>
        <p>${f.d}</p>
      </div>`).join('');
  }

  function applyLang() {
    const t = I18N[lang];
    document.querySelectorAll('[data-i18n]').forEach(el => { if (t[el.dataset.i18n]) el.textContent = t[el.dataset.i18n]; });
    renderFeatures(t);
    langSeg.querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.lang === lang));
    document.documentElement.lang = lang === 'en' ? 'en' : 'pt-BR';
  }

  langSeg.addEventListener('click', e => {
    const btn = e.target.closest('button[data-lang]');
    if (!btn) return;
    lang = btn.dataset.lang;
    localStorage.setItem('nlz-lang', lang);
    applyLang();
  });

  applyLang();

  // Copiar comandos -----------------------------------------------------------
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = document.querySelector(btn.dataset.copy)?.textContent ?? '';
      try { navigator.clipboard.writeText(text); } catch (e) {}
      btn.textContent = I18N[lang].copied;
      clearTimeout(btn._t);
      btn._t = setTimeout(() => { btn.textContent = I18N[lang].copy; }, 1600);
    });
  });
</script>
</body>
</html>
