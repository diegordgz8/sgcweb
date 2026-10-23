<?php

namespace Tests\Feature;

use App\Models\Comunicado;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * What used to live in the Laravel 8 skeleton (kernels, RouteServiceProvider,
 * EventServiceProvider) is still wired up after moving to bootstrap/app.php.
 */
class EsqueletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_and_cache_tasks_are_scheduled(): void
    {
        $comandos = collect(app(Schedule::class)->events())->map->command->implode("\n");

        $this->assertStringContainsString('backup:clean', $comandos);
        $this->assertStringContainsString('backup:run --only-db', $comandos);
        $this->assertStringContainsString('cache:prune-stale-tags', $comandos);
    }

    public function test_project_commands_are_registered(): void
    {
        $this->artisan('list')->expectsOutputToContain('db:restore')->assertSuccessful();
    }

    public function test_comunicado_observer_is_registered(): void
    {
        // #[ObservedBy] registers the observer when the model boots
        new Comunicado;

        $this->assertTrue(Event::hasListeners('eloquent.creating: '.Comunicado::class));
    }

    public function test_api_rate_limiter_and_spanish_resource_verbs_are_defined(): void
    {
        $this->assertNotNull(RateLimiter::limiter('api'));
        $this->assertSame(['create' => 'crear', 'edit' => 'modificar'], Route::resourceVerbs());
    }

    public function test_signed_in_users_are_sent_home_from_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
    }
}
