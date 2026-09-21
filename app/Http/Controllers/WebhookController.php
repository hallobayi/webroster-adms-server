<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        $title = __('webhooks.title');
        $webhooks = Webhook::with('device')->orderBy('id', 'DESC')->get();
        return view('webhooks.index', compact('webhooks', 'title'));
    }

    public function create(Request $request)
    {
        $title = __('webhooks.create_webhook');
        $devices = Device::whereDoesntHave('webhook')->orderBy('serial_number')->get();
        return view('webhooks.create', compact('devices', 'title'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer|exists:devices,id|unique:webhooks,device_id',
            'url' => 'nullable|url|max:255',
        ]);

        Webhook::create([
            'device_id' => $request->input('device_id'),
            'url' => $request->input('url'),
        ]);

        return redirect()->route('webhooks.index')->with('success', __('webhooks.created_successfully'));
    }

    public function edit($id)
    {
        $webhook = Webhook::find($id);
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', __('webhooks.not_found'));
        }
        $title = __('webhooks.edit_webhook');
        $devices = Device::orderBy('serial_number')->get();
        return view('webhooks.edit', compact('webhook', 'devices', 'title'));
    }

    public function update(Request $request, $id)
    {
        $webhook = Webhook::find($id);
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', __('webhooks.not_found'));
        }

        $request->validate([
            'device_id' => 'required|integer|exists:devices,id|unique:webhooks,device_id,' . $webhook->id,
            'url' => 'nullable|url|max:255',
        ]);

        $webhook->device_id = $request->input('device_id');
        $webhook->url = $request->input('url');
        $webhook->save();

        return redirect()->route('webhooks.index')->with('success', __('webhooks.updated_successfully'));
    }

    public function delete(Request $request)
    {
        $webhook = Webhook::find($request->input('id'));
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', __('webhooks.not_found'));
        }
        $webhook->delete();
        return redirect()->route('webhooks.index')->with('success', __('webhooks.deleted_successfully'));
    }
}
