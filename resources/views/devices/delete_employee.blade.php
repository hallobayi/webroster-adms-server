@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $title }}</h2>
        <form method="post" action="{{ route('devices.runDeleteFingerRecord') }}">
            @csrf
            <div class="form-group mb-3">
                <label for="oficina">{{ __('devices.oficina') }}</label>
                <select name="oficina" class="form-control" id="oficina" required>
                    <option value="">Select Office</option>
                    @foreach ($oficinas as $oficina)
                        <option value="{{ $oficina->idoficina }}">{{ $oficina->ubicacion }} ({{ $oficina->idoficina }})</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">The delete command will be sent to all devices in this office.</small>
            </div>

            <div class="form-group mb-3">
                <label for="idagente">Employee PIN (ID Agente)</label>
                <input type="text" name="idagente" class="form-control" id="idagente" placeholder="Enter PIN to delete" required>
            </div>

            <div class="alert alert-warning">
                <strong>Warning!</strong> This will queue a command to remove the user from the biometric devices. This action cannot be undone from the server easily.
            </div>

            <button type="submit" class="btn btn-danger">Delete from Devices</button>
            <a href="{{ route('devices.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
