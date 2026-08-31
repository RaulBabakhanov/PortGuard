<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Department;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $editId = $request->integer('edit') ?: null;

        $assets = Asset::query()
            ->with('department:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%")
                        ->orWhere('owner_name', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.assets.index', [
            'assets' => $assets,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'q' => $q,
            'types' => Asset::TYPES,
            'criticalities' => Asset::CRITICALITIES,
            'editing' => $editId ? Asset::query()->find($editId) : null,
        ]);
    }

    public function store(Request $request, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $asset = Asset::query()->create([...$data, 'is_active' => true]);
        $logger->log('asset.created', "Varlık eklendi: {$asset->name} ({$asset->ip})", $asset);

        return redirect()->route('admin.assets.index')->with('status', 'Varlık eklendi.');
    }

    public function update(Request $request, Asset $asset, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $asset->update($data);
        $logger->log('asset.updated', "Varlık güncellendi: {$asset->name} ({$asset->ip})", $asset);

        return redirect()->route('admin.assets.index')->with('status', 'Varlık güncellendi.');
    }

    public function destroy(Asset $asset, AdminActivityLogger $logger): RedirectResponse
    {
        $label = $asset->name.' ('.$asset->ip.')';
        $asset->delete();
        $logger->log('asset.deleted', "Varlık silindi: {$label}");

        return back()->with('status', 'Varlık silindi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'ip' => ['required', 'ip'],
            'asset_type' => ['required', Rule::in(array_keys(Asset::TYPES))],
            'criticality' => ['required', Rule::in(array_keys(Asset::CRITICALITIES))],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
