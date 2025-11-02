<?php

namespace Tests\Feature;

use App\Models\Strategy;
use App\Models\StrategyRun;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StrategyControlTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default strategy
        Strategy::create([
            'name' => 'SMA Cross',
            'class' => \App\Trading\Strategies\SMA::class,
            'config' => [
                'symbol' => 'AAPL',
                'qty' => 10,
                'fast' => 9,
                'slow' => 21,
                'bar_interval' => '1Min',
            ],
            'is_enabled' => true,
        ]);
    }

    public function test_show_returns_strategy_config()
    {
        $response = $this->getJson('/api/strategy/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'class',
                'config',
                'is_enabled',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'name' => 'SMA Cross',
                'config' => [
                    'symbol' => 'AAPL',
                    'qty' => 10,
                    'fast' => 9,
                    'slow' => 21,
                ],
            ]);
    }

    public function test_show_creates_default_strategy_if_none_exists()
    {
        Strategy::truncate();

        $response = $this->getJson('/api/strategy/config');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'config']);

        $this->assertDatabaseHas('strategies', [
            'name' => 'SMA Cross',
        ]);
    }

    public function test_update_validates_config_fields()
    {
        $response = $this->postJson('/api/strategy/config', [
            'config' => [
                'symbol' => '', // Invalid: empty symbol
                'qty' => 10,
                'fast' => 9,
                'slow' => 21,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['config.symbol']);
    }

    public function test_update_validates_qty_is_numeric_and_positive()
    {
        $response = $this->postJson('/api/strategy/config', [
            'config' => [
                'symbol' => 'AAPL',
                'qty' => 0, // Invalid: must be min:1
                'fast' => 9,
                'slow' => 21,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['config.qty']);
    }

    public function test_update_saves_new_config()
    {
        $response = $this->postJson('/api/strategy/config', [
            'config' => [
                'symbol' => 'TSLA',
                'qty' => 20,
                'fast' => 5,
                'slow' => 15,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'config' => [
                    'symbol' => 'TSLA',
                    'qty' => 20,
                    'fast' => 5,
                    'slow' => 15,
                ],
            ]);

        $this->assertDatabaseHas('strategies', [
            'config->symbol' => 'TSLA',
            'config->qty' => 20,
        ]);
    }

    public function test_start_creates_new_strategy_run()
    {
        config(['trading.mode' => 'paper']);

        $response = $this->postJson('/api/strategy/start');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'run' => [
                    'id',
                    'strategy_id',
                    'status',
                    'mode',
                    'started_at',
                ],
            ])
            ->assertJson([
                'run' => [
                    'status' => 'running',
                    'mode' => 'paper',
                ],
            ]);

        $this->assertDatabaseHas('strategy_runs', [
            'status' => 'running',
            'mode' => 'paper',
        ]);
    }

    public function test_start_stops_existing_running_strategies()
    {
        $strategy = Strategy::first();

        // Create an existing running strategy
        $existingRun = StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => 'paper',
            'started_at' => now()->subHours(2),
        ]);

        $response = $this->postJson('/api/strategy/start');

        $response->assertStatus(200);

        // Old run should be stopped
        $existingRun->refresh();
        $this->assertEquals('stopped', $existingRun->status);
        $this->assertNotNull($existingRun->stopped_at);

        // New run should be running
        $this->assertDatabaseHas('strategy_runs', [
            'status' => 'running',
        ]);

        $this->assertDatabaseCount('strategy_runs', 2);
    }

    public function test_stop_updates_running_strategies_to_stopped()
    {
        $strategy = Strategy::first();

        StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => 'paper',
            'started_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson('/api/strategy/stop');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Strategy stopped',
                'count' => 1,
            ]);

        $this->assertDatabaseHas('strategy_runs', [
            'status' => 'stopped',
        ]);

        $this->assertDatabaseMissing('strategy_runs', [
            'status' => 'running',
        ]);
    }

    public function test_stop_handles_multiple_running_strategies()
    {
        $strategy = Strategy::first();

        StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => 'paper',
            'started_at' => now()->subMinutes(30),
        ]);

        StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => 'paper',
            'started_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson('/api/strategy/stop');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 2,
            ]);

        $this->assertEquals(0, StrategyRun::where('status', 'running')->count());
        $this->assertEquals(2, StrategyRun::where('status', 'stopped')->count());
    }

    public function test_stop_returns_zero_count_when_no_running_strategies()
    {
        $response = $this->postJson('/api/strategy/stop');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Strategy stopped',
                'count' => 0,
            ]);
    }
}
