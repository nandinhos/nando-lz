<?php

use App\Models\User;
use App\Support\Build;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

$panels = ['ops', 'admin', 'support'];

it('sobe e responde na rota inicial', function () {
    get('/')->assertOk();
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
        ->expectsQuestion('Senha', 'Str0ngPass!2026')
        ->expectsQuestion('Confirme a senha', 'Str0ngPass!2026')
        ->assertSuccessful();

    expect(User::where('email', 'root@nando-lz.test')->exists())->toBeTrue();
});

it('superadmin:create recusa senha trivial fora de local', function () {
    artisan('superadmin:create')
        ->expectsQuestion('Nome', 'Nando')
        ->expectsQuestion('E-mail', 'root@nando-lz.test')
        ->expectsQuestion('Senha', 'senha')
        ->expectsQuestion('Confirme a senha', 'senha')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('superadmin:create bloqueia duplicidade', function () {
    User::factory()->create();

    artisan('superadmin:create')->assertFailed();

    expect(User::count())->toBe(1);
});

it('roda as migrations em banco limpo (tabela users existe)', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
});
