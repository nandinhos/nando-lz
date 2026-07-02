<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $stack['name'] }} — Painéis</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    min-height: 100vh;
    color: #e7eef9;
    background:
      radial-gradient(circle at 18% 12%, rgba(37, 207, 228, .22), transparent 32rem),
      linear-gradient(180deg, #0b1832 0%, #070e1c 44%, #07101f 100%);
    font-family: 'IBM Plex Sans', sans-serif;
  }
  .shell { width: min(1120px, calc(100% - 40px)); margin: 0 auto; }
  header { padding: 26px 0 18px; }
  .brand { display: flex; align-items: center; gap: 10px; color: #e7eef9; text-decoration: none; }
  .gem { width: 16px; height: 16px; transform: rotate(45deg); border-radius: 3px; background: #25cfe4; box-shadow: 0 0 18px rgba(37, 207, 228, .55); }
  .brand span:last-child { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 19px; }
  main { padding: 42px 0 64px; }
  .hero { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(300px, .92fr); gap: clamp(28px, 5vw, 60px); align-items: center; }
  .badge { display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px; border-radius: 999px; color: #25cfe4; border: 1px solid rgba(37, 207, 228, .28); background: rgba(37, 207, 228, .10); font-size: 12px; font-weight: 600; }
  .badge i { width: 7px; height: 7px; border-radius: 50%; background: #25cfe4; }
  h1 { margin: 22px 0 0; font-family: 'Space Grotesk', sans-serif; font-size: 3.95rem; line-height: 1.04; letter-spacing: 0; }
  .lead { margin: 20px 0 0; max-width: 56ch; color: #9eb1cf; font-size: 1.08rem; line-height: 1.65; }
  .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
  .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 18px; border-radius: 8px; color: #04121f; background: #25cfe4; text-decoration: none; font-weight: 700; }
  .btn.secondary { color: #e7eef9; background: rgba(255,255,255,.04); border: 1px solid rgba(140,180,230,.16); }
  .panel-grid { display: grid; gap: 12px; }
  .panel-card, .manual, .status {
    border: 1px solid rgba(140,180,230,.16);
    background: rgba(13, 28, 56, .78);
    border-radius: 8px;
    box-shadow: 0 28px 70px -44px rgba(0,0,0,.75);
  }
  .panel-card { padding: 18px; text-decoration: none; color: #e7eef9; display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
  .panel-card strong { font-family: 'Space Grotesk', sans-serif; font-size: 1.08rem; }
  .panel-card span { color: #93a8c8; font-size: .94rem; }
  .panel-card code { color: #25cfe4; font-family: 'JetBrains Mono', monospace; font-size: .9rem; }
  .section { margin-top: 46px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, .7fr); gap: 18px; align-items: start; }
  .manual { padding: 24px; }
  .manual h2, .status h2 { margin: 0; font-family: 'Space Grotesk', sans-serif; font-size: 1.35rem; }
  .steps { margin: 18px 0 0; padding: 0; list-style: none; display: grid; gap: 12px; }
  .steps li { padding: 14px 0; border-top: 1px solid rgba(140,180,230,.12); color: #cdd9ec; line-height: 1.55; }
  .steps code, .status code { font-family: 'JetBrains Mono', monospace; color: #25cfe4; }
  .status { padding: 24px; }
  .kv { margin-top: 18px; display: grid; gap: 10px; }
  .kv div { display: flex; justify-content: space-between; gap: 16px; color: #93a8c8; font-size: .95rem; }
  .kv strong { color: #e7eef9; font-weight: 600; text-align: right; }
  footer { padding: 28px 0 36px; color: #7f93b2; font-size: .92rem; }
  @media (max-width: 820px) {
    .hero, .section { grid-template-columns: 1fr; }
    .shell { width: min(100% - 28px, 1120px); }
    h1 { font-size: 2.75rem; }
  }
  @media (max-width: 460px) {
    h1 { font-size: 2.35rem; }
  }
</style>
</head>
<body>
@php
    $v = $stack['versions'];
@endphp
<header>
  <div class="shell">
    <a class="brand" href="/">
      <span class="gem"></span>
      <span>{{ $stack['name'] }}</span>
    </a>
  </div>
</header>
<main class="shell">
  <section class="hero">
    <div>
      <span class="badge"><i></i> Projeto pronto para operar</span>
      <h1>{{ $stack['name'] }}</h1>
      <p class="lead">Esta instalação já vem com Laravel, Filament, autenticação, três painéis e testes de sanidade. Use esta página como ponto de partida para operar, validar e continuar o desenvolvimento.</p>
      <div class="actions">
        <a class="btn" href="/admin">Abrir Admin</a>
        <a class="btn secondary" href="/ops">Ops</a>
        <a class="btn secondary" href="/support">Support</a>
      </div>
    </div>
    <div class="panel-grid" aria-label="Links dos painéis">
      <a class="panel-card" href="/ops">
        <span><strong>Ops</strong><br>Administração global e manutenção.</span>
        <code>/ops</code>
      </a>
      <a class="panel-card" href="/admin">
        <span><strong>Admin</strong><br>Painel principal da aplicação.</span>
        <code>/admin</code>
      </a>
      <a class="panel-card" href="/support">
        <span><strong>Support</strong><br>Suporte e operação assistida.</span>
        <code>/support</code>
      </a>
    </div>
  </section>

  <section class="section">
    <div class="manual">
      <h2>Manual inicial</h2>
      <ul class="steps">
        <li>Crie o primeiro usuário com <code>php artisan superadmin:create</code> e acesse um dos painéis.</li>
        <li>Revise o <code>.env</code>: banco, URL pública, mailer, queue e credenciais de produção.</li>
        <li>Valide a instalação com <code>php artisan test</code> antes de iniciar mudanças de domínio.</li>
        <li>Depois de criar regras de negócio, restrinja o acesso em <code>User::canAccessPanel()</code>.</li>
      </ul>
    </div>
    <aside class="status">
      <h2>Stack instalada</h2>
      <div class="kv">
        <div><span>Laravel</span><strong>{{ $v['Laravel'] ?? '—' }}</strong></div>
        <div><span>Filament</span><strong>{{ $v['Filament'] ?? '—' }}</strong></div>
        <div><span>Livewire</span><strong>{{ $v['Livewire'] ?? '—' }}</strong></div>
        <div><span>Pest</span><strong>{{ $v['Pest'] ?? '—' }}</strong></div>
        <div><span>Build</span><strong>{{ $stack['build'] }}</strong></div>
      </div>
    </aside>
  </section>
</main>
<footer>
  <div class="shell">Base criada a partir do nando-lz. A automação do starter foi desanexada deste projeto.</div>
</footer>
</body>
</html>
