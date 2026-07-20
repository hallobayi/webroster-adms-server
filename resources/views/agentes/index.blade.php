@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ __('agentes.title') }}</h2>
        <a href="{{ route('agentes.pull') }}" class="btn btn-primary mb-3">{{ __('agentes.pull_employees') }}</a>

        @if(session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if(session('pull_result'))
            @php $pullResult = session('pull_result'); @endphp
            <div class="modal fade" id="pullResultModal" tabindex="-1" aria-labelledby="pullResultModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header {{ !empty($pullResult['failed']) ? 'bg-danger text-white' : 'bg-success text-white' }}">
                            <h5 class="modal-title" id="pullResultModalLabel">
                                {{ !empty($pullResult['failed']) ? __('agentes.pull_failed') : __('agentes.pull_complete') }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if(!empty($pullResult['failed']))
                                <p class="mb-0">{{ $pullResult['message'] ?? __('agentes.pull_failed') }}</p>
                            @else
                                @if(!empty($pullResult['oficina']))
                                    <p>{{ __('devices.oficina') }}: <strong>{{ $pullResult['oficina'] }}</strong></p>
                                @endif
                                <ul class="mb-0">
                                    <li>{{ __('agentes.pulled') }}: <strong>{{ $pullResult['pulled'] ?? 0 }}</strong></li>
                                    <li>{{ __('agentes.new_agents') }}: <strong>{{ $pullResult['created'] ?? 0 }}</strong></li>
                                    <li>{{ __('agentes.updated_agents') }}: <strong>{{ $pullResult['updated'] ?? 0 }}</strong></li>
                                    <li>{{ __('agentes.commands_queued') }}: <strong>{{ $pullResult['commands'] ?? 0 }}</strong></li>
                                </ul>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('agentes.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="form-group">
            <label for="oficina">{{ __('navigation.oficinas') }}</label>
            <form method="GET" action="{{ route('agentes.index', ['selectedOficina' => $selectedOficina]) }}" id="oficinaForm">
                <input type="hidden" name="idempresa" id="idempresa" value="{{ request('idempresa') }}">
                <select name="selectedOficina" class="form-control" id="selectedOficina">
                    @foreach ($oficinas as $oficina)
                        <option value="{{ $oficina->idoficina }}" data-idempresa="{{ $oficina->idempresa }}" 
                            {{ $oficina->idoficina == $selectedOficina ? 'selected' : '' }}>
                            {{ $oficina->ubicacion }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <br>
        <table class="table table-bordered data-table" id="employees">
            <thead>
                <tr>
                    <th>{{ __('agentes.id_empresa') }}</th>
                    <th>{{ __('agentes.id_oficina') }}</th>
                    <th>{{ __('agentes.id_agente') }}</th>
                    <th>{{ __('agentes.shortname') }}</th>
                    <th>{{ __('agentes.fullname') }}</th>
                    <th>{{ __('agentes.last_update') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($agentes as $agente)
                    <tr>
                        <td>{{ $agente->idempresa }}</td>
                        <td>{{ $agente->idoficina }}</td>
                        <td>{{ $agente->idagente }}</td>
                        <td>{{ $agente->shortname }}</td>
                        <td>{{ $agente->fullname }}</td>
                        <td>{{ $agente->updated_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            @if(session('pull_result'))
                var pullResultModalEl = document.getElementById('pullResultModal');
                if (pullResultModalEl) {
                    var pullResultModal = new bootstrap.Modal(pullResultModalEl);
                    pullResultModal.show();
                }
            @endif

            function syncEmpresa() {
                var idempresa = $('#selectedOficina option:selected').data('idempresa');
                $('#idempresa').val(idempresa || '');
            }
            // sync on load
            syncEmpresa();
            $('#selectedOficina').change(function() {
                syncEmpresa();
                $('#oficinaForm').submit();
            });
        });
    </script>

@endsection