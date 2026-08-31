<x-app-layout>
    <x-slot name="header"><h1>Raporlar</h1></x-slot>
    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <header class="pg-section-head">
                <h2>Filtre ve dışa aktarma</h2>
                <p>Tarih aralığı ve tarama seçerek özet alın, CSV indirin.</p>
            </header>
            <form method="GET" action="{{ route('reports.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="from">Başlangıç</label>
                        <input id="from" type="date" name="from" value="{{ $from }}">
                    </div>
                    <div class="pg-field">
                        <label for="to">Bitiş</label>
                        <input id="to" type="date" name="to" value="{{ $to }}">
                    </div>
                    <div class="pg-field pg-field-span">
                        <label for="scan_id">Tarama (opsiyonel)</label>
                        <select id="scan_id" name="scan_id" class="pg-select">
                            <option value="">Tüm taramalar</option>
                            @foreach ($scanList as $scan)
                                <option value="{{ $scan->id }}" @selected((int) $scanId === (int) $scan->id)>
                                    #{{ $scan->id }} — {{ $scan->name }} ({{ $scan->summaryLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Uygula</button>
                    <a class="pg-btn pg-btn-ghost" href="{{ route('reports.index') }}">Temizle</a>
                    <a class="pg-btn pg-btn-ghost" href="{{ route('reports.export', ['type' => 'cves'] + request()->only(['from','to','scan_id'])) }}">CVE CSV</a>
                    <a class="pg-btn pg-btn-ghost" href="{{ route('reports.export', ['type' => 'scans'] + request()->only(['from','to','scan_id'])) }}">Tarama CSV</a>
                    <a class="pg-btn pg-btn-ghost" href="{{ route('reports.export', ['type' => 'services'] + request()->only(['from','to','scan_id'])) }}">Servis CSV</a>
                </div>
            </form>
        </section>

        <div class="pg-stat-grid">
            <div class="pg-stat"><strong>{{ $totals['scans'] }}</strong><span>Tarama</span></div>
            <div class="pg-stat"><strong>{{ $totals['services'] }}</strong><span>Servis kaydı</span></div>
            <div class="pg-stat"><strong>{{ $totals['cves'] }}</strong><span>CVE bulgusu</span></div>
            @foreach (['completed' => 'Tamamlanan', 'failed' => 'Başarısız'] as $key => $label)
                <div class="pg-stat"><strong>{{ $byStatus[$key] ?? 0 }}</strong><span>{{ $label }}</span></div>
            @endforeach
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head"><h2>Severity dağılımı</h2></header>
                @forelse ($severityStats as $row)
                    <div class="pg-list-row">
                        <div><strong>{{ $row->severity ?: 'N/A' }}</strong></div>
                        <em>{{ $row->total }}</em>
                    </div>
                @empty
                    <p class="pg-empty">Veri yok.</p>
                @endforelse
            </section>
            <section class="pg-section">
                <header class="pg-section-head"><h2>En çok görülen servisler</h2></header>
                @forelse ($topServices as $service)
                    <div class="pg-list-row"><div><strong>{{ $service->name }}</strong></div><em>{{ $service->total }}</em></div>
                @empty
                    <p class="pg-empty">Veri yok.</p>
                @endforelse
            </section>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head"><h2>En çok görülen CVE’ler</h2></header>
                @forelse ($topCves as $cve)
                    <div class="pg-list-row"><div><strong>{{ $cve->cve_id }}</strong></div><em>{{ $cve->total }}</em></div>
                @empty
                    <p class="pg-empty">Veri yok.</p>
                @endforelse
            </section>
            <section class="pg-section">
                <header class="pg-section-head"><h2>Taramalar</h2></header>
                @forelse ($recentScans as $scan)
                    <a class="pg-list-row" href="{{ route('scans.show', $scan) }}">
                        <div>
                            <strong>{{ $scan->name }}</strong>
                            <span>{{ $scan->summaryLabel() }} · {{ $scan->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <em>{{ $scan->cve_count }} CVE</em>
                    </a>
                @empty
                    <p class="pg-empty">Tarama yok.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
