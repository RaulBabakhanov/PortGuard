<x-app-layout>
    <x-slot name="header"><h1>Servisler</h1></x-slot>
    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('services.index') }}" class="pg-form pg-filter-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Servis, ürün, IP">
                    </div>
                    <div class="pg-field">
                        <label for="name">Servis adı</label>
                        <select id="name" name="name" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($names as $n)
                                <option value="{{ $n }}" @selected($name === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('services.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                    <a href="{{ route('reports.export', ['type' => 'services']) }}" class="pg-btn pg-btn-ghost">CSV indir</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <header class="pg-section-head"><h2>Özet</h2><p>Taramalarda görülen servis dağılımı</p></header>
            <div class="pg-chip-row">
                @forelse ($services as $service)
                    <a class="pg-chip" href="{{ route('services.index', ['name' => $service->name]) }}">
                        <strong>{{ $service->name }}</strong>
                        <span>{{ $service->total }} kayıt · {{ $service->host_total }} host · {{ $service->scan_total }} tarama</span>
                    </a>
                @empty
                    <p class="pg-empty">Servis yok.</p>
                @endforelse
            </div>
        </section>

        <section class="pg-section">
            <header class="pg-section-head"><h2>Son bulunanlar</h2></header>
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Servis</th><th>Port</th><th>Host</th><th>CVE</th><th>Tarama</th><th>Tarih</th></tr></thead>
                    <tbody>
                    @forelse ($latest as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->product ?: $row->name }}</strong>
                                @if ($row->version)<span class="pg-result-muted"> {{ $row->version }}</span>@endif
                            </td>
                            <td>{{ $row->port ?: '-' }}</td>
                            <td>{{ $row->host?->ip ?: '-' }}</td>
                            <td>{{ $row->cve_findings_count }}</td>
                            <td><a href="{{ route('scans.show', $row->scan_id) }}">#{{ $row->scan_id }}</a></td>
                            <td>{{ $row->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $latest->links() }}</div>
        </section>
    </div>
</x-app-layout>
