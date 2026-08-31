<x-app-layout>
    <x-slot name="header"><h1>CVE Bulguları</h1></x-slot>
    <div class="pg-page pg-page-wide">
        <div class="pg-stat-grid pg-stat-grid-sm">
            <div class="pg-stat"><strong>{{ $stats['total'] }}</strong><span>Toplam bulgu</span></div>
            <div class="pg-stat"><strong>{{ $stats['unique'] }}</strong><span>Benzersiz CVE</span></div>
            <div class="pg-stat"><strong>{{ $stats['high'] }}</strong><span>HIGH / CRITICAL</span></div>
        </div>

        <section class="pg-section">
            <header class="pg-section-head">
                <h2>Filtrele</h2>
                <p>CVE, IP, servis veya severity ile daraltın.</p>
            </header>
            <form method="GET" action="{{ route('cves.index') }}" class="pg-form pg-filter-form">
                <div class="pg-filter-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="CVE-ID veya açıklama">
                    </div>
                    <div class="pg-field">
                        <label for="ip">IP adresi</label>
                        <input id="ip" name="ip" value="{{ $ip }}" placeholder="Örn: 104.255.174.94">
                    </div>
                    <div class="pg-field">
                        <label for="severity">Severity</label>
                        <select id="severity" name="severity" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($severities as $sev)
                                <option value="{{ $sev }}" @selected($severity === $sev)>{{ strtoupper($sev) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pg-field">
                        <label for="service">Servis</label>
                        <select id="service" name="service" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($services as $svc)
                                <option value="{{ $svc }}" @selected($service === $svc)>{{ $svc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('cves.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                    <a href="{{ route('reports.export', ['type' => 'cves'] + request()->only(['from','to','scan_id'])) }}" class="pg-btn pg-btn-ghost">CSV indir</a>
                </div>
            </form>
        </section>

        <section class="pg-section pg-findings">
            <header class="pg-section-head">
                <h2>Bulgular</h2>
                <p>Her kayıtta hedef IP, servis ve tarama bilgisi ayrı gösterilir.</p>
            </header>

            @forelse ($cves as $cve)
                @php
                    $hostIp = $cve->service?->host?->ip;
                    $port = $cve->service?->port;
                    $product = $cve->service?->product ?: $cve->service_name;
                    $version = $cve->service?->version;
                    $sev = strtoupper((string) ($cve->severity ?: 'N/A'));
                    $sevClass = match (true) {
                        in_array($sev, ['CRITICAL', 'HIGH'], true) => 'is-high',
                        in_array($sev, ['MEDIUM'], true) => 'is-medium',
                        in_array($sev, ['LOW'], true) => 'is-low',
                        default => 'is-na',
                    };
                @endphp

                <article class="pg-finding">
                    <div class="pg-finding-top">
                        <div class="pg-finding-title">
                            <h3>{{ $cve->cve_id }}</h3>
                            <span class="pg-sev {{ $sevClass }}">{{ $sev }}</span>
                        </div>
                        <a class="pg-btn pg-btn-ghost" href="{{ route('scans.show', $cve->scan_id) }}">Tarama #{{ $cve->scan_id }}</a>
                    </div>

                    <dl class="pg-finding-meta">
                        <div>
                            <dt>Hedef IP</dt>
                            <dd>
                                @if ($hostIp)
                                    <strong class="pg-ip">{{ $hostIp }}</strong>
                                @else
                                    <span class="pg-result-muted">IP yok</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Servis</dt>
                            <dd>{{ $product ?: '—' }}@if($version) <span class="pg-result-muted">{{ $version }}</span>@endif</dd>
                        </div>
                        <div>
                            <dt>Port</dt>
                            <dd>{{ $port ? $port.'/tcp' : '—' }}</dd>
                        </div>
                        <div>
                            <dt>Tarih</dt>
                            <dd>{{ $cve->created_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    </dl>

                    <p class="pg-finding-desc">{{ $cve->description }}</p>
                </article>
            @empty
                <p class="pg-empty">Bu filtrelere uygun CVE yok. Önce bir tarama çalıştırın.</p>
            @endforelse

            <div class="pg-pagination">{{ $cves->links() }}</div>
        </section>
    </div>
</x-app-layout>
