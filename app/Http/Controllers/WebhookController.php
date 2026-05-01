<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Webhooks';
        $webhooks = Webhook::with('device')->orderBy('id', 'DESC')->get();
        return view('webhooks.index', compact('webhooks', 'title'));
    }

    public function create(Request $request)
    {
        $title = 'Create Webhook';
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

        return redirect()->route('webhooks.index')->with('success', 'Webhook created successfully');
    }

    public function edit($id)
    {
        $webhook = Webhook::find($id);
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', 'Webhook not found');
        }
        $title = 'Edit Webhook';
        $devices = Device::where(function ($query) use ($webhook) {
            $query->whereDoesntHave('webhook')
                ->orWhere('id', $webhook->device_id);
        })->orderBy('serial_number')->get();
        return view('webhooks.edit', compact('webhook', 'devices', 'title'));
    }

    public function update(Request $request, $id)
    {
        $webhook = Webhook::find($id);
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', 'Webhook not found');
        }

        $request->validate([
            'device_id' => 'required|integer|exists:devices,id|unique:webhooks,device_id,' . $webhook->id,
            'url' => 'nullable|url|max:255',
        ]);

        $webhook->device_id = $request->input('device_id');
        $webhook->url = $request->input('url');
        $webhook->save();

        return redirect()->route('webhooks.index')->with('success', 'Webhook updated successfully');
    }

    public function delete($id)
    {
        $validatedId = filter_var($id, FILTER_VALIDATE_INT);
        if ($validatedId === false) {
            return redirect()->route('webhooks.index')->with('error', 'Invalid webhook id');
        }

        $webhook = Webhook::find((int) $validatedId);
        if (!$webhook) {
            return redirect()->route('webhooks.index')->with('error', 'Webhook not found');
        }
        $webhook->delete();
        return redirect()->route('webhooks.index')->with('success', 'Webhook deleted successfully');
    }
}
