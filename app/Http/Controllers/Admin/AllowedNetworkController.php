<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllowedNetwork;
use App\Models\Department;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllowedNetworkController extends Controller
{
    public function index(Request $request): View
    {
        $editId = $request->integer('edit') ?: null;

        return view('admin.networks.index', [
            'networks' => AllowedNetwork::query()->with('department:id,name')->latest('id')->paginate(10),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'editing' => $editId ? AllowedNetwork::query()->find($editId) : null,
        ]);
    }

    public function store(Request $request, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $network = AllowedNetwork::query()->create([...$data, 'is_active' => true]);
        $logger->log('network.created', "İzinli ağ eklendi: {$network->cidr}", $network);

        return redirect()->route('admin.networks.index')->with('status', 'İzinli ağ eklendi.');
    }

    public function update(Request $request, AllowedNetwork $network, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $network->update($data);
        $logger->log('network.updated', "İzinli ağ güncellendi: {$network->cidr}", $network);

        return redirect()->route('admin.networks.index')->with('status', 'İzinli ağ güncellendi.');
    }

    public function toggle(AllowedNetwork $network, AdminActivityLogger $logger): RedirectResponse
    {
        $network->update(['is_active' => ! $network->is_active]);
        $logger->log('network.toggle', 'İzinli ağ '.($network->is_active ? 'aktif' : 'pasif').": {$network->cidr}", $network);

        return back()->with('status', 'Ağ durumu güncellendi.');
    }

    public function destroy(AllowedNetwork $network, AdminActivityLogger $logger): RedirectResponse
    {
        $cidr = $network->cidr;
        $network->delete();
        $logger->log('network.deleted', "İzinli ağ silindi: {$cidr}");

        return back()->with('status', 'İzinli ağ silindi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cidr' => ['required', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cidr = trim($data['cidr']);
        if (! $this->isValidNetwork($cidr)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cidr' => 'Geçersiz format. Tek IP (10.0.0.5) veya CIDR (10.0.0.0/24) girin.',
            ]);
        }

        $data['cidr'] = $cidr;

        return $data;
    }

    private function isValidNetwork(string $cidr): bool
    {
        if (filter_var($cidr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        if (! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || ! ctype_digit($mask)) {
            return false;
        }

        $mask = (int) $mask;

        return $mask >= 8 && $mask <= 32;
    }
}
