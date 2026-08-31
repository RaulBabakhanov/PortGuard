<x-admin-layout>
    <x-slot name="header"><h1>İzinli ağlar</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>{{ $editing ? 'Ağı düzenle' : 'Onaylı CIDR / IP ekle' }}</h2>
                    <p>Yalnızca bu listedeki adresler taranabilir.</p>
                </div>
            </header>

            <div class="pg-help-box">
                <strong>Ne yazmalısınız?</strong><br>
                • Tek IP: <code>10.20.30.40</code><br>
                • Ağ aralığı (CIDR): <code>10.20.0.0/16</code> veya <code>192.168.1.0/24</code><br>
                • Ayrı ayrı her IP’yi yazmak zorunda değilsiniz — CIDR tüm alt ağı kapsar.
            </div>

            <form method="POST" action="{{ $editing ? route('admin.networks.update', $editing) : route('admin.networks.store') }}" class="pg-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label>Ad</label>
                        <input name="name" required value="{{ old('name', $editing?->name) }}" placeholder="Belediye LAN">
                    </div>
                    <div class="pg-field">
                        <label>CIDR / IP</label>
                        <input name="cidr" required value="{{ old('cidr', $editing?->cidr) }}" placeholder="10.0.0.0/16">
                    </div>
                    <div class="pg-field">
                        <label>Birim</label>
                        <select name="department_id" class="pg-select">
                            <option value="">—</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id', $editing?->department_id) == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pg-field">
                        <label>Not</label>
                        <input name="notes" value="{{ old('notes', $editing?->notes) }}" placeholder="Opsiyonel">
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">{{ $editing ? 'Güncelle' : 'Ekle' }}</button>
                    @if ($editing)
                        <a href="{{ route('admin.networks.index') }}" class="pg-btn pg-btn-ghost">İptal</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Ad</th><th>CIDR / IP</th><th>Birim</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($networks as $network)
                        <tr>
                            <td>{{ $network->name }}</td>
                            <td><code>{{ $network->cidr }}</code></td>
                            <td>{{ $network->department?->name ?? '—' }}</td>
                            <td><span class="pg-status {{ $network->is_active ? 'pg-status-completed' : 'pg-status-failed' }}">{{ $network->is_active ? 'aktif' : 'pasif' }}</span></td>
                            <td>
                                <div class="pg-admin-card-actions">
                                    <a href="{{ route('admin.networks.index', ['edit' => $network->id]) }}" class="pg-btn pg-btn-ghost">Düzenle</a>
                                    <form method="POST" action="{{ route('admin.networks.toggle', $network) }}">@csrf<button class="pg-btn pg-btn-ghost" type="submit">{{ $network->is_active ? 'Pasifleştir' : 'Aktifleştir' }}</button></form>
                                    <form method="POST" action="{{ route('admin.networks.destroy', $network) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="pg-btn pg-btn-danger" type="submit">Sil</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="pg-empty">İzinli ağ yok — tarama yapılamaz.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $networks->links() }}</div>
        </section>
    </div>
</x-admin-layout>
