@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $title ?? 'Create Webhook' }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('webhooks.store') }}">
            @csrf
            <div class="form-group mb-3">
                <label for="device_id">Device</label>
                <select name="device_id" id="device_id" class="form-control" required>
                    <option value="">-- Select device --</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                            {{ $device->serial_number }}@if($device->idreloj) ({{ $device->idreloj }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="url">Webhook URL</label>
                <input type="url" name="url" class="form-control" id="url" value="{{ old('url') }}" placeholder="https://example.com/hooks/attendance">
                <small class="form-text text-muted">Endpoint that will receive a POST with attendance data on every push.</small>
            </div>
            <button type="submit" class="btn btn-primary">Create</button>
            <a href="{{ route('webhooks.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
