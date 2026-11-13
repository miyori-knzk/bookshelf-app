<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 段階 0-2：docs/routing.md の全ルートが定義されていることを確かめる。
 */
class RouteDefinitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * docs/routing.md のルート（メソッド、URI、ルート名）。URI の `{book}` などは ID の場所。
     *
     * @return array<string, array{string, string, string}>
     */
    public static function routeProvider(): array
    {
        return collect([
            ['GET', '/', 'books.index'],
            ['GET', '/books/create', 'books.create'],
            ['POST', '/books', 'books.store'],
            ['GET', '/books/{book}', 'books.show'],
            ['GET', '/books/{book}/edit', 'books.edit'],
            ['PUT', '/books/{book}', 'books.update'],
            ['DELETE', '/books/{book}', 'books.destroy'],
            ['GET', '/genres', 'genres.index'],
            ['GET', '/genres/create', 'genres.create'],
            ['POST', '/genres', 'genres.store'],
            ['GET', '/genres/{genre}', 'genres.show'],
            ['GET', '/genres/{genre}/edit', 'genres.edit'],
            ['PUT', '/genres/{genre}', 'genres.update'],
            ['DELETE', '/genres/{genre}', 'genres.destroy'],
            ['POST', '/books/{book}/reviews', 'reviews.store'],
            ['GET', '/reviews/{review}/edit', 'reviews.edit'],
            ['PUT', '/reviews/{review}', 'reviews.update'],
            ['DELETE', '/reviews/{review}', 'reviews.destroy'],
            ['POST', '/reviews/{review}/like', 'reviews.like'],
            ['GET', '/favorites', 'favorites.index'],
            ['POST', '/books/{book}/favorites', 'favorites.toggle'],
            ['GET', '/ranking', 'ranking.index'],
            ['GET', '/api/v1/books', 'api.v1.books.index'],
            ['GET', '/api/v1/books/{book}', 'api.v1.books.show'],
            ['POST', '/api/v1/books', 'api.v1.books.store'],
            ['PUT', '/api/v1/books/{book}', 'api.v1.books.update'],
            ['PATCH', '/api/v1/books/{book}', 'api.v1.books.update'],
            ['DELETE', '/api/v1/books/{book}', 'api.v1.books.destroy'],
        ])->mapWithKeys(fn (array $route): array => [
            "{$route[0]} {$route[1]}" => $route,
        ])->all();
    }

    /**
     * ログインが必要な Web ルート。
     *
     * @return array<string, array{string, string}>
     */
    public static function authRouteProvider(): array
    {
        return collect(static::routeProvider())
            ->reject(fn (array $route): bool => in_array($route[2], ['books.index', 'books.show', 'ranking.index'], true))
            ->reject(fn (array $route): bool => str_starts_with($route[2], 'api.'))
            ->map(fn (array $route): array => [$route[0], $route[1]])
            ->all();
    }

    /**
     * ログインが必要な Web ルートのうち、まだ仮実装（abort(501)）のもの。
     *
     * ルートの本体を作った段階で、そのルートをここから外す（`reject` の一覧に足す）。
     *
     * @return array<string, array{string, string}>
     */
    public static function stubRouteProvider(): array
    {
        return collect(static::routeProvider())
            ->only(array_keys(static::authRouteProvider()))
            ->reject(fn (array $route): bool => str_starts_with($route[2], 'genres.'))
            ->reject(fn (array $route): bool => str_starts_with($route[2], 'books.'))
            ->map(fn (array $route): array => [$route[0], $route[1]])
            ->all();
    }

    /**
     * URI の `{book}` などを ID（1）に置き換えて、リクエストに使える URI にする。
     */
    private function concreteUri(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '1', $uri);
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_route_is_defined_with_method_uri_and_name(string $method, string $uri, string $name): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, $name);
        $this->assertContains($method, $route->methods(), $name);
        $this->assertSame($uri, '/' . ltrim($route->uri(), '/'), $name);
    }

    public function test_books_index_route_does_not_exist_at_books_uri(): void
    {
        // POST /books はあるため、GET /books を開くと 404 ではなく 405 になる
        $hasGetBooks = collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route): bool => $route->uri() === 'books' && in_array('GET', $route->methods(), true));

        $this->assertFalse($hasGetBooks);
        $this->get('/books')->assertStatus(405);
    }

    public function test_books_create_is_registered_before_books_show(): void
    {
        $this->assertSame('books.create', Route::getRoutes()->match(request()->create('/books/create'))->getName());
    }

    /**
     * @dataProvider authRouteProvider
     */
    public function test_guest_is_redirected_to_login_for_auth_routes(string $method, string $uri): void
    {
        $this->call($method, $this->concreteUri($uri))->assertRedirect(route('login'));
    }

    /**
     * 仮実装（abort(501)）のルートを確かめる。
     *
     * ルートの本体を作った段階で、そのルートを対象から外し、本来のテストをその段階の
     * *ControllerTest に書く（docs/features.md「段階 0-2 の仮実装のテスト」）。
     * 外し忘れると、このテストが失敗する。
     *
     * @dataProvider stubRouteProvider
     */
    public function test_authenticated_user_gets_not_implemented_for_stub_routes(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->create())
            ->call($method, $this->concreteUri($uri))
            ->assertStatus(501);
    }

    /**
     * ログインが要らないルートが、ログイン画面へ移動させずに開けることを確かめる（今は仮実装の 501）。
     *
     * ルートの本体を作った段階で、そのルートを下の一覧から外し、本来のテストをその段階の
     * *ControllerTest に書く（docs/features.md「段階 0-2 の仮実装のテスト」）。
     * 外し忘れると、このテストが失敗する。
     */
    public function test_public_routes_do_not_require_login(): void
    {
        collect(['/ranking', '/api/v1/books'])
            ->each(fn (string $uri) => $this->get($uri)->assertStatus(501));
    }

    public function test_default_api_user_route_is_removed(): void
    {
        $this->getJson('/api/user')->assertNotFound();
    }

    public function test_all_route_names_used_in_views_are_defined(): void
    {
        $names = collect(File::allFiles(resource_path('views')))
            ->flatMap(function ($file): array {
                preg_match_all("/route\('([^']+)'/", $file->getContents(), $matches);

                return $matches[1];
            })
            ->unique();

        $this->assertNotEmpty($names);
        $names->each(fn (string $name) => $this->assertTrue(Route::has($name), $name));
    }
}
