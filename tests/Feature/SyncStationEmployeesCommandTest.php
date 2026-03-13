<?php

namespace Tests\Feature;

use App\Models\Agente;
use App\Models\Device;
use App\Models\Oficina;
use App\Services\GetStationAgentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncStationEmployeesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_queues_commands_for_new_station_employees(): void
    {
        $office = Oficina::create([
            'idempresa' => 10,
            'idoficina' => 20,
            'ubicacion' => 'Station 20',
            'public_url' => 'https://station.test',
            'token' => 'token',
            'iatacode' => 'CUN',
        ]);

        $device = Device::create([
            'serial_number' => 'SN-001',
            'idempresa' => 10,
            'idoficina' => 20,
            'idreloj' => '1',
        ]);

        Agente::create([
            'idempresa' => 10,
            'idoficina' => 20,
            'idagente' => 1001,
            'shortname' => 'existing',
            'fullname' => 'Existing Employee',
        ]);

        $mock = $this->mock(GetStationAgentsService::class);
        $mock->shouldReceive('getStationAgents')
            ->once()
            ->withArgs(fn (Oficina $arg) => $arg->is($office))
            ->andReturn([
                [
                    'idagente' => 1001,
                    'shortname' => 'existing',
                    'nombre' => 'Existing',
                    'apellidos' => 'Employee',
                ],
                [
                    'idagente' => 2002,
                    'shortname' => 'new',
                    'nombre' => 'New',
                    'apellidos' => 'Employee',
                ],
            ]);

        $this->artisan('employees:sync-stations')
            ->expectsOutput('Office 10/20: pulled 2, new 1, queued 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('agentes', [
            'idempresa' => 10,
            'idoficina' => 20,
            'idagente' => 2002,
            'fullname' => 'New Employee',
        ]);

        $this->assertDatabaseCount('device_commands', 1);

        $command = $device->commands()->first();

        $this->assertNotNull($command);
        $this->assertStringContainsString('PIN=2002', $command->data);
        $this->assertStringNotContainsString('PIN=1001', $command->data);
    }
}
