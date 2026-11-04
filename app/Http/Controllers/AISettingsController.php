<?php

namespace App\Http\Controllers;

use App\Models\AISetting;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AISettingsController extends Controller
{
    public function edit(Request $request, AIProviderManager $ai)
    {
        return view('settings.ai', ['setting' => $ai->settingsFor($request->user()), 'providers' => AISetting::PROVIDERS]);
    }

    public function update(Request $request)
    {
        $setting = $this->fill($request);
        $setting->save();
        return back()->with('status', 'AI settings saved.');
    }

    public function testConnection(Request $request, AIProviderManager $ai)
    {
        try {
            $result = $ai->make($this->fill($request))->testConnection();
        } catch (AIProviderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->withInput()->with($result->ok ? 'status' : 'error', $result->message)->with('models', $result->models);
    }

    protected function fill(Request $request): AISetting
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(AISetting::PROVIDERS))],
            'base_url' => ['nullable', 'url', 'max:500'],
            'model' => ['required', 'string', 'max:150'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'clear_api_key' => ['sometimes', 'boolean'],
        ]);
        $setting = $request->user()->aiSetting ?? $request->user()->aiSetting()->make();
        $setting->fill(collect($data)->only(['provider', 'base_url', 'model'])->all());
        if ($request->boolean('clear_api_key')) $setting->api_key_encrypted = null;
        elseif (filled($data['api_key'] ?? null)) $setting->api_key_encrypted = $data['api_key'];
        return $setting;
    }
}
