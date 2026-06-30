<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AhoIconNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_renders_custom_icons_without_querying_the_icon_tables(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $iconQueries = [];

        DB::connection('warehouse')->listen(function (QueryExecuted $query) use (&$iconQueries): void {
            if (str_contains(strtolower($query->sql), 'stg_fontawesome_icons')) {
                $iconQueries[] = $query->sql;
            }
        });

        $this
            ->actingAs($user)
            ->get('/admin/af')
            ->assertOk()
            ->assertSee('aho-custom-icon fa-solid fa-chart-line', false)
            ->assertSee('aho-custom-icon fa-solid fa-hospital', false)
            ->assertSee('vendor/fontawesome-free/6.5.2/css/solid.min.css', false);

        $this
            ->get('/admin/af/indicators/values')
            ->assertOk()
            ->assertSee('/admin/af/indicators/custom-icons', false);

        $this->assertSame([], $iconQueries);
    }
}
