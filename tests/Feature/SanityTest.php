<?php

use App\Models\User;
use App\Support\Build;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

$panels = ['ops', 'admin', 'support'];

it('sobe e responde na rota inicial', function () {
    get('/')->assertOk();
});

it('mostra na welcome as versões reais do lock e o monitor de atualização', function () {
    // Detecta o estado real do projeto: starter (landing welcome.blade.php)
    // ou personalizado (operacional project-welcome.blade.php). Os asserts
    // especificos do monitor de atualizacao so se aplicam a landing.
    $currentName = (string) config('app.name');
    $isStarter = $currentName === 'nando-lz';

    $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);
    $locked = collect(array_merge($lock['packages'], $lock['packages-dev']))
        ->keyBy('name')->map(fn ($p) => ltrim($p['version'], 'v'));

    $response = get('/')
        ->assertOk()
        ->assertSee($locked['laravel/framework'])
        ->assertSee($locked['filament/filament'])
        ->assertSee($locked['livewire/livewire'])
        ->assertSee(Build::id());

    // Monitor: so renderiza na landing (welcome.blade.php), e so se houver
    // relatorio do ciclo de manutencao. Em projeto personalizado, a
    // project-welcome.blade.php nao tem monitor.
    $reports = glob(base_path('docs/reports/auto-update/*.md')) ?: [];
    if ($reports !== [] && $isStarter) {
        $response->assertSee(basename((string) end($reports), '.md'));
    }
});

it('welcome não renderiza links quebrados quando repositório remoto não foi definido', function () {
    config(['app.github_url' => '']);

    // Detecta o estado: na landing do starter ha comando 'git remote add
    // origin' orientando o usuario a criar o repo. No projeto personalizado
    // (project-welcome.blade.php) esse comando nao existe — so verifica
    // ausencia de href vazio e git clone .git, que valem para qualquer estado.
    $isStarter = (string) config('app.name') === 'nando-lz';

    $response = get('/')
        ->assertOk()
        ->assertDontSee('href=""', false)
        ->assertDontSee('git clone .git');

    if ($isStarter) {
        $response->assertSee('git remote add origin');
    }
});

it('mostra uma welcome operacional quando o projeto está personalizado', function () {
    config(['app.name' => 'Topizeira']);

    get('/')
        ->assertOk()
        ->assertSee('Topizeira')
        ->assertSee('/ops')
        ->assertSee('/admin')
        ->assertSee('/support')
        ->assertSee('php artisan superadmin:create')
        ->assertDontSee('A base premium para seus próximos projetos Laravel');
});

it('mostra a página de login de cada painel', function (string $panel) {
    get("/{$panel}/login")->assertOk();
})->with($panels);

it('exige autenticação para acessar cada painel', function (string $panel) {
    get("/{$panel}")->assertRedirect("/{$panel}/login");
})->with($panels);

it('deixa um usuário autenticado acessar cada painel e mostra o build no rodapé', function (string $panel) {
    actingAs(User::factory()->create());

    get("/{$panel}")
        ->assertOk()
        ->assertSee('build '.Build::id());
})->with($panels);

it('não expõe logout via GET (CSRF)', function (string $panel) {
    actingAs(User::factory()->create());

    get("/{$panel}/logout")->assertStatus(405);
})->with($panels);

it('encerra a sessão e regenera o token CSRF no POST /logout', function (string $panel) {
    actingAs(User::factory()->create());
    get("/{$panel}"); // inicia a sessão

    $tokenAntes = session()->token();

    post("/{$panel}/logout")->assertRedirect();

    expect(auth()->check())->toBeFalse()
        ->and(session()->token())->not->toBe($tokenAntes);
})->with($panels);

it('superadmin:create cria o primeiro usuário (interativo, senha forte)', function () {
    expect(User::count())->toBe(0);

    artisan('superadmin:create')
        ->expectsQuestion('Nome', 'Nando')
        ->expectsQuestion('E-mail', 'root@nando-dev.test')
        // Fixture de teste (não é segredo): precisa passar na regra forte
        // (mín. 12, maiúsc./minúsc., números, símbolos) fora de `local`.
        ->expectsQuestion('Senha', 'Senha-Fake-De-Teste-123!')
        ->expectsQuestion('Confirme a senha', 'Senha-Fake-De-Teste-123!')
        ->assertSuccessful();

    expect(User::where('email', 'root@nando-dev.test')->exists())->toBeTrue();
});

it('superadmin:create recusa senha trivial fora de local', function () {
    // 'password123' passa na regra de local (mín. 8) mas falha na regra forte
    // (mín. 12 + maiúsculas + símbolos) — distingue as duas regras de verdade.
    artisan('superadmin:create')
        ->expectsQuestion('Nome', 'Nando')
        ->expectsQuestion('E-mail', 'root@nando-dev.test')
        ->expectsQuestion('Senha', 'password123')
        ->expectsQuestion('Confirme a senha', 'password123')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('superadmin:create bloqueia duplicidade', function () {
    User::factory()->create();

    artisan('superadmin:create')->assertFailed();

    expect(User::count())->toBe(1);
});

it('autentica com credenciais válidas na página de login do Filament', function () {
    $user = User::factory()->create(); // senha padrão da factory: "password"

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

it('rejeita credenciais inválidas na página de login do Filament', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'senha-errada'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('roda as migrations em banco limpo (tabela users existe)', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
});

it('README bate exato com a stack instalada (guarda de drift)', function () {
    // Falha qualquer PR que bump de versão sem sincronizar o README.
    artisan('stack:sync', ['--check' => true])->assertSuccessful();
});

it('app:setup permite continuar sem repositório remoto', function () {
    artisan('app:setup', ['--preview' => true, '--force' => true])
        ->expectsQuestion('Nome da aplicação', 'Topizeira')
        ->expectsQuestion('Banco de dados', 'topizeira_db')
        ->expectsConfirmation('Já existe repositório Git remoto?', 'no')
        ->expectsQuestion('Porta pública (Docker)', '19000')
        ->expectsConfirmation('Resetar o histórico git?', 'no')
        ->expectsOutputToContain('somente CI')
        ->doesntExpectOutputToContain('Pacote')
        ->doesntExpectOutputToContain('Renovate')
        ->doesntExpectOutputToContain('mantenedor')
        ->assertSuccessful();
});

it('app:setup nao reescreve scripts/ do starter', function () {
    // Guard: scripts de tooling (install-docker.sh, install.sh, bootstrap-app.sh,
    // etc.) sao do starter, nao do projeto do usuario. Reescreve-los quebra
    // sintaxe shell em alguns casos (strtr substitui tokens em aspas/comentarios
    // de scripts bash, gerando EOF/quote mismatches). Devem ficar intactos.
    artisan('app:setup', ['--preview' => true, '--force' => true])
        ->expectsQuestion('Nome da aplicação', 'Topizeira')
        ->expectsQuestion('Banco de dados', 'topizeira_db')
        ->expectsConfirmation('Já existe repositório Git remoto?', 'no')
        ->expectsQuestion('Porta pública (Docker)', '19000')
        ->expectsConfirmation('Resetar o histórico git?', 'no')
        ->assertSuccessful();

    // Nenhum arquivo de scripts/ deve aparecer na lista de "serao reescritos".
    $preview = \Illuminate\Support\Facades\Artisan::output();
    expect($preview)
        ->not->toContain('scripts/install-docker.sh')
        ->not->toContain('scripts/install.sh')
        ->not->toContain('scripts/install-local.sh')
        ->not->toContain('scripts/bootstrap-app.sh')
        ->not->toContain('scripts/check-requirements.sh')
        ->not->toContain('scripts/reset-app.sh')
        ->not->toContain('scripts/test-app.sh');
});
