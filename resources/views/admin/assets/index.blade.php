<x-admin-layout>
    <x-slot name="header"><h1>Varlık envanteri</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>{{ $editing ? 'Varlığı düzenle' : 'Yeni varlık' }}</h2>
                    <p>IP’yi isimlendirin: web sitesi, muhasebe sunucusu, kiosk…</p>
                </div>
            </header>
            <form method="POST" action="{{ $editing ? route('admin.assets.update', $editing) : route('admin.assets.store') }}" class="pg-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label>Ad</label>
                        <input name="name" required value="{{ old('name', $editing?->name) }}">
                    </div>
                    <div class="pg-field">
                        <label>IP</label>
                        <input name="ip" required value="{{ old('ip', $editing?->ip) }}" placeholder="10.0.0.10">
                    </div>
                    <div class="pg-field">
                        <label>Tür</label>
                        <select name="asset_type" class="pg-select">
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('asset_type', $editing?->asset_type ?? 'server') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pg-field">
                        <label>Kritiklik</label>
                        <select name="criticality" class="pg-select">
                            @foreach ($criticalities as $key => $label)
                                <option value="{{ $key }}" @selected(old('criticality', $editing?->criticality ?? 'medium') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                        <label>Sorumlu</label>
                        <input name="owner_name" value="{{ old('owner_name', $editing?->owner_name) }}">
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">{{ $editing ? 'Güncelle' : 'Kaydet' }}</button>
                    @if ($editing)
                        <a href="{{ route('admin.assets.index') }}" class="pg-btn pg-btn-ghost">İptal</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="pg-section">
            <form method="GET" action="{{ route('admin.assets.index') }}" class="pg-form pg-filter-form">
                <div class="pg-filter-grid">
                    <div class="pg-field">
                        <label>Ara</label>
                        <input name="q" value="{{ $q }}" placeholder="Ad veya IP">
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('admin.assets.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>

            <div class="pg-table-wrap" style="margin-top:1rem;">
                <table class="pg-table">
                    <thead><tr><th>Ad</th><th>IP</th><th>Tür</th><th>Kritiklik</th><th>Birim</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td>{{ $asset->name }}</td>
                            <td><code>{{ $asset->ip }}</code></td>
                            <td>{{ $asset->typeLabel() }}</td>
                            <td>{{ $asset->criticalityLabel() }}</td>
                            <td>{{ $asset->department?->name ?? '—' }}</td>
                            <td>
                                <div class="pg-admin-card-actions">
                                    <a href="{{ route('admin.assets.index', ['edit' => $asset->id]) }}" class="pg-btn pg-btn-ghost">Düzenle</a>
                                    <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="pg-btn pg-btn-danger" type="submit">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Varlık yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $assets->links() }}</div>
        </section>
    </div>
</x-admin-layout>
