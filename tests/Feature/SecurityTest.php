<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pengamanan menyeluruh: rute, endpoint JSON, autentikasi, header, dan kredensial.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rute yang memang boleh diakses tanpa login (halaman publik & alur login).
     * Rute baru di luar daftar ini WAJIB memakai middleware "auth", atau tes ini gagal.
     */
    private const RUTE_PUBLIK = [
        '/', 'go-global', 'go-digital', 'semua-brand', 'semua-brand/{umkm}', 'berita', 'berita/{slug}',
        'kemitraan', 'tentang-kami', 'direktori/{slug?}',
        'admin/peta-interaktif', 'admin/peta-interaktif/umkm/{id}', // hanya pengalihan 301 ke rute ber-auth
        'login', 'forgot-password', 'reset-password/{token}', 'reset-password',
        'up', 'storage/{path}',
    ];

    public function test_every_non_public_route_requires_authentication(): void
    {
        $terbuka = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (in_array($uri, self::RUTE_PUBLIK, true) || str_starts_with($uri, '_')) {
                continue;
            }
            $middleware = $route->gatherMiddleware();
            $punyaAuth = collect($middleware)->contains(fn ($m) => $m === 'auth' || str_starts_with($m, 'auth:'));
            if (! $punyaAuth) {
                $terbuka[] = implode('|', $route->methods()) . ' ' . $uri;
            }
        }

        $this->assertSame([], $terbuka, "Rute tanpa autentikasi:\n" . implode("\n", $terbuka));
    }

    public function test_admin_and_superadmin_routes_also_require_a_role(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! preg_match('#^(admin|superadmin)/#', $route->uri()) || str_starts_with($route->uri(), 'admin/peta-interaktif')) {
                continue;
            }
            $this->assertTrue(
                collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'role:')),
                "Rute {$route->uri()} tidak dibatasi peran",
            );
        }
    }

    public function test_json_endpoints_reject_guests_and_unauthorised_users(): void
    {
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $tanpaPeran = User::factory()->create();

        $endpoint = [
            ['GET', '/superadmin/peta-interaktif/data'],
            ['GET', '/admin/produk/list/1'],
            ['PATCH', '/admin/profil-umkm/1'],
            ['PATCH', '/admin/produk/1'],
            ['DELETE', '/admin/produk/1'],
        ];

        foreach ($endpoint as [$metode, $url]) {
            $this->json($metode, $url)->assertUnauthorized();
            $this->actingAs($tanpaPeran)->json($metode, $url)->assertForbidden();
            auth()->logout();
        }
    }

    public function test_no_api_route_file_exposes_unprotected_endpoints(): void
    {
        $this->assertFileDoesNotExist(base_path('routes/api.php'));
        $this->get('/api/user')->assertNotFound();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');

        $this->get('/login')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_admin_pages_are_not_cached(): void
    {
        Role::firstOrCreate(['name' => User::ROLE_ADMIN_OPD, 'guard_name' => 'web']);
        $admin = User::factory()->create()->assignRole(User::ROLE_ADMIN_OPD);

        $cache = $this->actingAs($admin)->get('/admin/dashboard')->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
    }

    public function test_forgot_password_does_not_reveal_registered_emails(): void
    {
        User::factory()->create(['email' => 'admin@umkmlinked.test']);

        $terdaftar = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'admin@umkmlinked.test']);
        $tidak     = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'tidak.ada@umkmlinked.test']);

        $terdaftar->assertSessionHasNoErrors();
        $tidak->assertSessionHasNoErrors();
        $this->assertSame($terdaftar->getSession()->get('status'), $tidak->getSession()->get('status'));
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'admin@umkmlinked.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@umkmlinked.test', 'password' => 'salah-' . $i]);
        }

        $this->post('/login', ['email' => 'admin@umkmlinked.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'x', 'email' => 'x@x.id', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123'])
            ->assertStatus(404);
    }

    public function test_credentials_are_not_tracked_by_git(): void
    {
        $hasil = Process::path(base_path())->run(['git', 'ls-files']);
        if ($hasil->failed()) {
            $this->markTestSkipped('Git tidak tersedia.');
        }
        $files = array_filter(preg_split('/\R/', $hasil->output()));

        foreach ($files as $f) {
            $this->assertDoesNotMatchRegularExpression('#(^|/)\.env($|\.(?!example$))|\.(pem|key|sqlite)$|(^|/)auth\.json$#', $f, "Berkas rahasia ter-commit: {$f}");
        }

        $this->assertStringContainsString('.env', file_get_contents(base_path('.gitignore')));
    }
}
