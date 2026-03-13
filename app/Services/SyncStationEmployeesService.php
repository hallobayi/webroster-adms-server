<?php

namespace App\Services;

use App\Models\Agente;
use App\Models\Device;
use App\Models\Oficina;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SyncStationEmployeesService
{
    public function __construct(
        protected GetStationAgentsService $stationAgentsService
    ) {
    }

    public function syncOffice(Oficina $oficina): array
    {
        Log::info('SyncStationEmployeesService started', [
            'idempresa' => $oficina->idempresa,
            'idoficina' => $oficina->idoficina,
        ]);

        $agents = $this->stationAgentsService->getStationAgents($oficina);

        if ($agents && isset($agents->status) && $agents->status === 'failed') {
            Log::error('Failed to get station agents', [
                'idempresa' => $oficina->idempresa,
                'idoficina' => $oficina->idoficina,
                'error' => $agents->message ?? 'Unknown error',
            ]);

            return [
                'pulled' => 0,
                'created' => 0,
                'commands' => 0,
                'failed' => true,
            ];
        }

        $normalizedAgents = $this->normalizeAgents($agents);
        $newEmployees = collect();

        foreach ($normalizedAgents as $agentData) {
            $agent = Agente::updateOrCreate(
                ['idagente' => $agentData['idagente']],
                [
                    'idempresa' => $oficina->idempresa,
                    'idoficina' => $oficina->idoficina,
                    'idagente' => $agentData['idagente'],
                    'shortname' => $agentData['shortname'],
                    'fullname' => trim(($agentData['nombre'] ?? '') . ' ' . ($agentData['apellidos'] ?? '')),
                ]
            );

            if ($agent->wasRecentlyCreated) {
                $newEmployees->push($agent);
            }
        }

        $queuedCommands = $this->queueNewEmployeesForDevices($oficina, $newEmployees);

        Log::info('SyncStationEmployeesService completed', [
            'idempresa' => $oficina->idempresa,
            'idoficina' => $oficina->idoficina,
            'pulled' => count($normalizedAgents),
            'created' => $newEmployees->count(),
            'commands' => $queuedCommands,
        ]);

        return [
            'pulled' => count($normalizedAgents),
            'created' => $newEmployees->count(),
            'commands' => $queuedCommands,
            'failed' => false,
        ];
    }

    protected function queueNewEmployeesForDevices(Oficina $oficina, Collection $newEmployees): int
    {
        if ($newEmployees->isEmpty()) {
            return 0;
        }

        $devices = Device::where('idempresa', $oficina->idempresa)
            ->where('idoficina', $oficina->idoficina)
            ->get();

        $queuedCommands = 0;

        foreach ($devices as $device) {
            $queuedCommands += $device->populate($newEmployees);
        }

        return $queuedCommands;
    }

    protected function normalizeAgents($agents): array
    {
        if ($agents instanceof Collection) {
            return $agents->all();
        }

        if (is_array($agents)) {
            return $agents;
        }

        if ($agents instanceof \Traversable) {
            return iterator_to_array($agents);
        }

        return [];
    }
}
