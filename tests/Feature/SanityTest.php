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
    $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);
    $locked = collect(array_merge($lock['packages'], $lock['packages-dev']))
        ->keyBy('name')->map(fn ($p) => ltrim($p['version'], 'v'));

    $response = get('/')
        ->assertOk()
        ->assertSee($locked['laravel/framework'])
        ->assertSee($locked['filament/filament'])
        ->assertSee($locked['livewire/livewire'])
        ->assertSee(Build::id());

    // Monitor: só há relatório se a automação de manutenção estiver presente
    // (o wizard app:setup pode tê-la desanexado num projeto de usuário).
    $reports = glob(base_path('docs/reports/auto-update/*.md')) ?: [];
    if ($reports !== []) {
        $response->assertSee(basename((string) end($reports), '.md'));
    }
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
        ->expectsQuestion('E-mail', 'root@nando-lz.test')
        // Fixture de teste (não é segredo): precisa passar na regra forte
        // (mín. 12, maiúsc./minúsc., números, símbolos) fora de `local`.
        ->expectsQuestion('Senha', 'Senha-Fake-De-Teste-123!')
        ->expectsQuestion('Confirme a senha', 'Senha-Fake-De-Teste-123!')
        ->assertSuccessful();

    expect(User::where('email', 'root@nando-lz.test')->exists())->toBeTrue();
});

it('superadmin:create recusa senha trivial fora de local', function () {
    // 'password123' passa na regra de local (mín. 8) mas falha na regra forte
    // (mín. 12 + maiúsculas + símbolos) — distingue as duas regras de verdade.
    artisan('superadmin:create')
        ->expectsQuestion('Nome', 'Nando')
        ->expectsQuestion('E-mail', 'root@nando-lz.test')
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
