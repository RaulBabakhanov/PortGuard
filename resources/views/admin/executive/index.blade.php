<x-admin-layout>
    <x-slot name="header"><h1>Yönetici özeti</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <div class="pg-page-intro">
            <h2>Belediye genel görünüm</h2>
            <p>Açık servisler, kritik CVE’ler ve birim bazlı durum — tek bakışta.</p>
        </div>

        <div class="pg-stat-grid">
            <div class="pg-stat"><strong>{{ $totals['open_services'] }}</strong><span>Açık servis kaydı</span></div>
            <div class="pg-stat"><strong>{{ $totals['critical_cves'] }}</strong><span>Kritik / yüksek CVE</span></div>
            <div class="pg-stat"><strong>{{ $totals['active_hosts'] }}</strong><span>Aktif host</span></div>
            <div class="pg-stat"><strong>{{ $totals['assets'] }}</strong><span>Varlık</span></div>
            <div class="pg-stat"><strong>{{ $totals['critical_assets'] }}</strong><span>Kritik varlık</span></div>
            <div class="pg-stat"><strong>{{ $totals['awaiting_approval'] }}</strong><span>Onay bekleyen</span></div>
        </div>

        <div class="pg-toolbar">
            <a href="{{ route('admin.approvals.index') }}" class="pg-btn pg-btn-primary">Onay kuyruğu</a>
            <a href="{{ route('admin.audit.comparison') }}" class="pg-btn pg-btn-ghost">Karşılaştırma</a>
            <a href="{{ route('admin.audit.index') }}" class="pg-btn pg-btn-ghost">Denetim paketi</a>
        </div>

        <section class="pg-section">
            <header class="pg-section-head"><h2>Birim durumu</h2></header>
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Birim</th>
                            <th>Kullanıcı</th>
                            <th>Varlık</th>
                            <th>Tarama</th>
                            <th>CVE</th>
                            <th>Kritik</th>
                            <th>Son tarama</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($departments as $dept)
                        <tr>
                            <td>{{ $dept['name'] }}</td>
                            <td>{{ $dept['users'] }}</td>
                            <td>{{ $dept['assets'] }}</td>
                            <td>{{ $dept['scans'] }}</td>
                            <td>{{ $dept['cves'] }}</td>
                            <td>{{ $dept['critical_cves'] }}</td>
                            <td>{{ $dept['last_scan']?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                <span class="pg-status {{ $dept['behind'] ? 'pg-status-failed' : 'pg-status-completed' }}">
                                    {{ $dept['behind'] ? 'geride' : 'güncel' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pg-empty">Birim yok. Önce birim ekleyin.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head"><h2>Varlık riski</h2></header>
                @forelse ($asset_risk as $row)
                    <div class="pg-list-row">
                        <div>
                            <strong>{{ $row['asset']->name }}</strong>
                            <span>{{ $row['asset']->ip }} · {{ $row['asset']->typeLabel() }} · {{ $row['asset']->criticalityLabel() }}</span>
                        </div>
                        <em>{{ $row['cve_count'] }} CVE</em>
                    </div>
                @empty
                    <p class="pg-empty">Varlık yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head"><h2>Son kritik CVE’ler</h2></header>
                @forelse ($recent_critical_cves as $cve)
                    <div class="pg-list-row">
                        <div>
                            <strong>{{ $cve->cve_id }}</strong>
                            <span>{{ $cve->user?->email }} · {{ \Illuminate\Support\Str::limit($cve->description, 70) }}</span>
                        </div>
                        <em>{{ strtoupper((string) $cve->severity) }}</em>
                    </div>
                @empty
                    <p class="pg-empty">Kritik CVE yok.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-admin-layout>
