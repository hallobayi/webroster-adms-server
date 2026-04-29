@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $title ?? 'Edit Webhook' }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('webhooks.update', ['id' => $webhook->id ]) }}">
            @csrf
            <div class="form-group mb-3">
                <label for="device_id">Device</label>
                <select name="device_id" id="device_id" class="form-control" required>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" {{ $webhook->device_id == $device->id ? 'selected' : '' }}>
                            {{ $device->serial_number }}@if($device->idreloj) ({{ $device->idreloj }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="url">Webhook URL</label>
                <input type="url" name="url" class="form-control" id="url" value="{{ old('url', $webhook->url) }}" placeholder="https://example.com/hooks/attendance">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('webhooks.delete', ['id' => $webhook->id ]) }}" class="btn btn-danger">Delete</a>
            <a href="{{ route('webhooks.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
