<?php

namespace Arabiacode\LaravelFlowBuilder\Http\Controllers;

use Arabiacode\LaravelFlowBuilder\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class IntegrationController extends Controller
{
    public function index()
    {
        $integrations = Integration::latest()->paginate(15);

        return view('flow-builder::integrations.index', compact('integrations'));
    }

    public function create()
    {
        return view('flow-builder::integrations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:webhook,whatsapp,firebase,google_drive',
            'is_active' => 'nullable|boolean',
            'credential_keys' => 'nullable|array',
            'credential_keys.*' => 'nullable|string|max:255',
            'credential_values' => 'nullable|array',
            'credential_values.*' => 'nullable|string|max:2048',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['credentials'] = $this->buildCredentials(
            $request->input('credential_keys', []),
            $request->input('credential_values', [])
        );
        unset($validated['credential_keys'], $validated['credential_values']);

        Integration::create($validated);

        return redirect()->route('flow-builder.integrations.index')
            ->with('success', 'Integration created successfully.');
    }

    public function edit(Integration $integration)
    {
        return view('flow-builder::integrations.edit', compact('integration'));
    }

    public function update(Request $request, Integration $integration)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:webhook,whatsapp,firebase,google_drive',
            'is_active' => 'nullable|boolean',
            'credential_keys' => 'nullable|array',
            'credential_keys.*' => 'nullable|string|max:255',
            'credential_values' => 'nullable|array',
            'credential_values.*' => 'nullable|string|max:2048',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Merge new credentials with existing — blank values keep existing
        $newCreds = $this->buildCredentials(
            $request->input('credential_keys', []),
            $request->input('credential_values', [])
        );
        $existingCreds = $integration->credentials ?? [];

        // For keys that exist in both, keep existing value if new is blank
        $merged = [];
        foreach ($newCreds as $key => $value) {
            $merged[$key] = ($value !== '' && $value !== null) ? $value : ($existingCreds[$key] ?? '');
        }
        $validated['credentials'] = $merged;
        unset($validated['credential_keys'], $validated['credential_values']);

        $integration->update($validated);

        return redirect()->route('flow-builder.integrations.index')
            ->with('success', 'Integration updated successfully.');
    }

    public function destroy(Integration $integration)
    {
        $integration->delete();

        return redirect()->route('flow-builder.integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }

    public function toggle(Integration $integration)
    {
        $integration->update(['is_active' => !$integration->is_active]);

        return back()->with('success', 'Integration ' . ($integration->is_active ? 'activated' : 'deactivated') . '.');
    }

    protected function buildCredentials(array $keys, array $values): array
    {
        $credentials = [];
        foreach ($keys as $i => $key) {
            $key = trim($key);
            if ($key !== '') {
                $credentials[$key] = $values[$i] ?? '';
            }
        }
        return $credentials;
    }
}
