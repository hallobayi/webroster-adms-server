@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>{{ $title }}</h2>
        <a href="{{ route('webhooks.create') }}" class="btn btn-primary mb-3">Create Webhook</a>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <table class="table table-bordered data-table" id="webhooks">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Device</th>
                    <th>URL</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($webhooks as $webhook)
                    <tr>
                        <td>{{ $webhook->id }}</td>
                        <td>{{ optional($webhook->device)->serial_number ?? '#' . $webhook->device_id }}</td>
                        <td class="text-wrap">{{ $webhook->url }}</td>
                        <td>{{ $webhook->updated_at?->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('webhooks.edit', ['id' => $webhook->id ]) }}" class="btn btn-primary">Edit</a>
                            <a href="{{ route('webhooks.delete', ['id' => $webhook->id ]) }}" class="btn btn-danger delete-btn">Delete</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this webhook?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelModal" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let targetUrl = '';
            document.querySelectorAll('.delete-btn').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    targetUrl = this.href;
                    $('#confirmModal').modal('show');
                });
            });
            document.getElementById('confirmBtn').addEventListener('click', function () {
                window.location.href = targetUrl;
            });
            document.getElementById('cancelModal').addEventListener('click', function () {
                $('#confirmModal').modal('hide');
            });
        });
    </script>
@endsection
