<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oficina;
use App\Models\Agente;
use App\Services\SyncStationEmployeesService;
use Illuminate\Support\Facades\Log;

class AgentesController extends Controller
{
    public function index(Request $request)
    {        
        $selectedOficina = $request->query('selectedOficina');
		$idempresa = $request->query('idempresa');
        if ($selectedOficina) {
			$q = Agente::where('idoficina', $selectedOficina);
			if (!empty($idempresa)) {
				$q->where('idempresa', $idempresa);
			}
			$agentes = $q->get()->sortBy('idagente');
        } else {
            $agentes = Agente::all()->sortBy('idagente');
        }
        $oficinas = Oficina::all();
        return view('agentes.index', compact('agentes', 'oficinas', 'selectedOficina'));
    }

    public function pullAgentes(Request $request)
    {
        $oficinas = Oficina::all();
        return view('agentes.pull', compact('oficinas'));
    }

    public function runPullAgentes(Request $request, SyncStationEmployeesService $service)
    {
		$idoficina = $request->input('oficina');
		$idempresa = $request->input('idempresa');
		$oficinaQuery = Oficina::where('idoficina', $idoficina);
		if (!empty($idempresa)) {
			$oficinaQuery->where('idempresa', $idempresa);
		}
		$oficina = $oficinaQuery->first();

		if (!$oficina) {
			Log::warning('runPullAgentes: office not found', [
				'idoficina' => $idoficina,
				'idempresa' => $idempresa,
			]);

			$request->session()->flash('pull_result', [
				'failed' => true,
				'message' => 'Office not found.',
			]);

			return redirect()->route('agentes.index');
		}

		Log::debug('runPullAgentes: manual pull triggered', [
			'idoficina' => $oficina->idoficina,
			'idempresa' => $oficina->idempresa,
			'triggered_by' => optional($request->user())->email ?? optional($request->user())->id,
		]);

		// Run synchronously so we can report accurate counts back to the user
		// immediately, instead of a fire-and-forget queued job whose result
		// nobody sees.
		$result = $service->syncOffice($oficina);

		Log::info('runPullAgentes: manual pull finished', array_merge($result, [
			'idoficina' => $oficina->idoficina,
			'idempresa' => $oficina->idempresa,
		]));

		$request->session()->flash('pull_result', array_merge($result, [
			'oficina' => $oficina->ubicacion,
		]));

        return redirect()->route('agentes.index');
    }
}
