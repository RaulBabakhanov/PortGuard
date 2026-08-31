<x-app-layout>
    <x-slot name="header"><h1>Tarama #{{ $scan->id }}</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <div class="pg-toolbar">
            <a href="{{ route('scans.index') }}" class="pg-btn pg-btn-ghost">← Geçmiş</a>
            @if ($scan->status !== 'awaiting_approval' && $scan->status !== 'rejected')
                <a href="{{ route('scans.pdf', $scan) }}" class="pg-btn pg-btn-primary">PDF indir</a>
            @endif
        </div>

        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>
        @endif

        @if ($scan->status === 'awaiting_approval')
            <div class="pg-alert pg-alert-warn">
                Tarama yönetici onayı bekliyor.
                @if ($scan->approval_reason) Gerekçe: {{ $scan->approval_reason }}@endif
            </div>
        @endif
        @if ($scan->status === 'rejected')
            <div class="pg-alert pg-alert-danger">
                Tarama reddedildi: {{ $scan->rejection_reason ?: 'Gerekçe belirtilmedi' }}
            </div>
        @endif

        @if ($scan->error_message)
            <div class="pg-result-card pg-result-error">
                <h2>Hata</h2>
                <p>{{ $scan->error_message }}</p>
            </div>
        @endif

        <div class="pg-cve-summary">
            <div class="pg-cve-stat"><b>{{ $stats['total'] }}</b><span>Toplam IP</span></div>
            <div class="pg-cve-stat"><b>{{ $stats['active'] }}</b><span>Aktif Host</span></div>
            <div class="pg-cve-stat"><b>{{ $stats['apache'] }}</b><span>Apache</span></div>
            <div class="pg-cve-stat"><b>{{ $stats['mariadb'] }}</b><span>MariaDB</span></div>
            <div class="pg-cve-stat"><b>{{ $stats['openssh'] }}</b><span>OpenSSH</span></div>
        </div>

        @forelse ($onlineHosts as $host)
            @php
                $badges = $hostBadges[$host->id] ?? [];
                $cvesByService = $hostCves[$host->id] ?? collect();
            @endphp

            <div class="pg-result-card">
                <div class="pg-result-head">
                    <div>
                        <h2>Host: {{ $host->ip }}</h2>
                        <p class="pg-result-muted">Aktif cihaz bulundu. Servis ve versiyon bilgileri aşağıda.</p>
                        @foreach (($assetsByIp[$host->ip] ?? collect()) as $asset)
                            <span class="pg-asset-chip">{{ $asset->name }} · {{ $asset->typeLabel() }} · {{ $asset->criticalityLabel() }}@if($asset->department) · {{ $asset->department->name }}@endif</span>
                        @endforeach
                    </div>
                    <div class="pg-online">ONLINE</div>
                </div>

                <div class="pg-svc-badges">
                    @forelse ($badges as $badge)
                        <span class="pg-svc-badge">{{ $badge }}</span>
                    @empty
                        <span class="pg-svc-badge pg-svc-badge-empty">Servis bulunamadı</span>
                    @endforelse
                </div>

                @if ($host->raw_output)
                    <details class="pg-nmap-details" open>
                        <summary>Nmap çıktısını göster</summary>
                        <pre class="pg-nmap-pre">{{ $host->raw_output }}</pre>
                    </details>
                @endif
            </div>

            <div class="pg-result-card">
                <h2>CVE Sonuçları - {{ $host->ip }}</h2>

                @if ($cvesByService->isEmpty())
                    <p class="pg-result-muted">CVE aranacak servis bulunamadı veya bulgu yok.</p>
                @else
                    @foreach ($cvesByService as $serviceName => $findings)
                        <h3 class="pg-cve-service">{{ $serviceName }}</h3>
                        @forelse ($findings as $cve)
                            <div class="pg-cve-vuln">
                                <b>{{ $cve->cve_id }}</b>
                                @if ($cve->severity)
                                    <span class="pg-cve-sev">{{ strtoupper($cve->severity) }}</span>
                                @endif
                                <p>{{ $cve->description }}</p>
                            </div>
                        @empty
                            <p class="pg-result-muted">Bu servis için CVE bulunamadı.</p>
                        @endforelse
                    @endforeach
                @endif
            </div>
        @empty
            <div class="pg-result-card">
                <h2>Sonuç yok</h2>
                <p class="pg-result-muted">
                    @if ($scan->status === 'pending' || $scan->status === 'running')
                        Tarama henüz tamamlanmadı.
                    @else
                        Aktif host bulunamadı.
                    @endif
                </p>
            </div>
        @endforelse

        <p class="pg-result-footer">
            {{ $scan->name }} · {{ $scan->summaryLabel() }} · Portlar: {{ $scan->ports }}
            · <span class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</span>
        </p>
    </div>
</x-app-layout>
