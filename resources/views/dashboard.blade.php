<x-app-layout>
    <x-slot name="header"><h1>Panel</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <div class="pg-page-intro">
            <h2>Merhaba, {{ Auth::user()->name }}</h2>
            <p>Tarama, servis ve CVE özetiniz. Her işlem aktivite loglarına yazılır.</p>
        </div>

        @if (($nmap['mode'] ?? '') === 'nmap')
            <div class="pg-alert pg-alert-ok">
                <span class="pg-engine-chip pg-engine-nmap">Nmap</span>
                Hazır{{ !empty($nmap['version']) ? ' · v'.$nmap['version'] : '' }}
            </div>
        @elseif (! empty($nmap['reason']))
            <div class="pg-alert pg-alert-warn">
                <span class="pg-engine-chip pg-engine-php">PHP</span>
                {{ $nmap['reason'] }}
            </div>
        @endif

        <div class="pg-stat-grid">
            <div class="pg-stat"><strong>{{ $stats['scans'] }}</strong><span>Tarama</span></div>
            <div class="pg-stat"><strong>{{ $stats['targets'] }}</strong><span>Hedef</span></div>
            <div class="pg-stat"><strong>{{ $stats['services'] }}</strong><span>Servis</span></div>
            <div class="pg-stat"><strong>{{ $stats['cves'] }}</strong><span>CVE</span></div>
            <div class="pg-stat"><strong>{{ $stats['unread'] }}</strong><span>Okunmamış bildirim</span></div>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head">
                    <h2>Son taramalar</h2>
                    <a href="{{ route('scans.create') }}" class="pg-btn pg-btn-primary">Yeni tarama</a>
                </header>
                @forelse ($recentScans as $scan)
                    <a href="{{ route('scans.show', $scan) }}" class="pg-list-row">
                        <div>
                            <strong>{{ $scan->name }}</strong>
                            <span>{{ $scan->summaryLabel() }} · {{ $scan->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <em class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</em>
                    </a>
                @empty
                    <p class="pg-empty">Henüz tarama yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head"><h2>Son CVE’ler</h2></header>
                @forelse ($recentCves as $cve)
                    <div class="pg-list-row">
                        <div>
                            <strong>{{ $cve->cve_id }}</strong>
                            <span>{{ $cve->service_name }} · {{ \Illuminate\Support\Str::limit($cve->description, 80) }}</span>
                        </div>
                        <em>{{ $cve->severity ?: 'N/A' }}</em>
                    </div>
                @empty
                    <p class="pg-empty">CVE kaydı yok.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
