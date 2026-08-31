<x-admin-layout>
    <x-slot name="header"><h1>Karşılaştırma</h1></x-slot>

    @php
        $a = $diff['period_a'];
        $b = $diff['period_b'];
        $d = $diff['deltas'];
        $fmtDelta = function (int $n): string {
            if ($n > 0) return '+'.$n;
            if ($n < 0) return (string) $n;
            return '0';
        };
        $deltaClass = function (int $n, bool $worseWhenUp = true): string {
            if ($n === 0) return 'is-flat';
            $up = $n > 0;
            if ($worseWhenUp) {
                return $up ? 'is-worse' : 'is-better';
            }
            return $up ? 'is-better' : 'is-worse';
        };
        $sevClass = function (string $sev): string {
            $sev = strtoupper($sev);
            return match (true) {
                in_array($sev, ['CRITICAL', 'HIGH'], true) => 'is-high',
                $sev === 'MEDIUM' => 'is-medium',
                $sev === 'LOW' => 'is-low',
                default => 'is-na',
            };
        };
    @endphp

    <div class="pg-page pg-page-wide">
        <div class="pg-page-intro">
            <h2>Anlık dönem karşılaştırması</h2>
            <p>{{ $diff['summary'] }}</p>
        </div>

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Dönem seçin</h2>
                    <p>Ay bitmesini beklemez — seçtiğiniz gün sayısı kadar “şimdi” ile “hemen önceki” dönemi kıyaslar.</p>
                </div>
            </header>
            <div class="pg-period-tabs">
                @foreach ($windows as $w)
                    <a href="{{ route('admin.audit.comparison', ['days' => $w]) }}"
                       class="pg-period-tab {{ $days === $w ? 'is-active' : '' }}">
                        Son {{ $w }} gün
                    </a>
                @endforeach
            </div>
        </section>

        <div class="pg-compare-hero">
            <article class="pg-compare-panel is-prev">
                <div class="pg-compare-panel-label">Önceki dönem</div>
                <h3>{{ $a['label'] }}</h3>
                <p class="pg-compare-range">{{ $a['from'] }} → {{ $a['to'] }}</p>
                <dl class="pg-compare-metrics">
                    <div><dt>Tarama</dt><dd>{{ $a['scans'] }}</dd></div>
                    <div><dt>Servis kaydı</dt><dd>{{ $a['services'] }}</dd></div>
                    <div><dt>CVE kaydı</dt><dd>{{ $a['cves'] }}</dd></div>
                    <div><dt>Kritik / yüksek</dt><dd>{{ $a['critical_cves'] }}</dd></div>
                </dl>
            </article>

            <div class="pg-compare-vs" aria-hidden="true">vs</div>

            <article class="pg-compare-panel is-curr">
                <div class="pg-compare-panel-label">Güncel dönem</div>
                <h3>{{ $b['label'] }}</h3>
                <p class="pg-compare-range">{{ $b['from'] }} → {{ $b['to'] }}</p>
                <dl class="pg-compare-metrics">
                    <div><dt>Tarama</dt><dd>{{ $b['scans'] }}</dd></div>
                    <div><dt>Servis kaydı</dt><dd>{{ $b['services'] }}</dd></div>
                    <div><dt>CVE kaydı</dt><dd>{{ $b['cves'] }}</dd></div>
                    <div><dt>Kritik / yüksek</dt><dd>{{ $b['critical_cves'] }}</dd></div>
                </dl>
            </article>
        </div>

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Değişim özeti</h2>
                    <p>Pozitif sayı artış demektir. CVE ve kritik bulgularda artış genellikle risk artışı anlamına gelir.</p>
                </div>
            </header>
            <div class="pg-delta-grid">
                <div class="pg-delta-card {{ $deltaClass($d['scans'], false) }}">
                    <span>Tarama</span>
                    <strong>{{ $fmtDelta($d['scans']) }}</strong>
                </div>
                <div class="pg-delta-card {{ $deltaClass($d['services'], false) }}">
                    <span>Servis kaydı</span>
                    <strong>{{ $fmtDelta($d['services']) }}</strong>
                </div>
                <div class="pg-delta-card {{ $deltaClass($d['cves']) }}">
                    <span>CVE kaydı</span>
                    <strong>{{ $fmtDelta($d['cves']) }}</strong>
                </div>
                <div class="pg-delta-card {{ $deltaClass($d['critical_cves']) }}">
                    <span>Kritik / yüksek</span>
                    <strong>{{ $fmtDelta($d['critical_cves']) }}</strong>
                </div>
                <div class="pg-delta-card is-info">
                    <span>Yeni servis</span>
                    <strong>{{ $diff['new_services']->count() }}</strong>
                </div>
                <div class="pg-delta-card is-info">
                    <span>Yeni CVE</span>
                    <strong>{{ $diff['new_cves']->count() }}</strong>
                </div>
            </div>
        </section>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head">
                    <div>
                        <h2>Yeni görülen servisler</h2>
                        <p>Önceki dönemde yoktu, son {{ $days }} günde çıktı.</p>
                    </div>
                </header>
                @forelse ($diff['new_services'] as $svc)
                    <div class="pg-diff-row is-new">
                        <div>
                            <strong>{{ $svc['name'] }}</strong>
                            <span class="pg-diff-meta">Port {{ $svc['port'] ?: '—' }} · sürüm {{ $svc['version'] }}</span>
                        </div>
                        <span class="pg-diff-badge is-new">Yeni</span>
                    </div>
                @empty
                    <p class="pg-empty">Yeni servis yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head">
                    <div>
                        <h2>Artık görülmeyen servisler</h2>
                        <p>Önceki dönemde vardı, son {{ $days }} günde yok.</p>
                    </div>
                </header>
                @forelse ($diff['gone_services'] as $svc)
                    <div class="pg-diff-row is-gone">
                        <div>
                            <strong>{{ $svc['name'] }}</strong>
                            <span class="pg-diff-meta">Port {{ $svc['port'] ?: '—' }} · sürüm {{ $svc['version'] }}</span>
                        </div>
                        <span class="pg-diff-badge is-gone">Kayboldu</span>
                    </div>
                @empty
                    <p class="pg-empty">Kaybolan servis yok.</p>
                @endforelse
            </section>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head">
                    <div>
                        <h2>Yeni CVE’ler</h2>
                        <p>Son {{ $days }} günde ilk kez görülen bulgular (önem sırasıyla).</p>
                    </div>
                </header>
                @forelse ($diff['new_cves'] as $cve)
                    <article class="pg-finding pg-finding-compact">
                        <div class="pg-finding-top">
                            <div class="pg-finding-title">
                                <h3>{{ $cve['cve_id'] }}</h3>
                                <span class="pg-sev {{ $sevClass($cve['severity']) }}">{{ $cve['severity'] }}</span>
                            </div>
                            <span class="pg-diff-badge is-new">Yeni</span>
                        </div>
                        <dl class="pg-finding-meta">
                            <div>
                                <dt>Servis</dt>
                                <dd>{{ $cve['service_name'] ?: '—' }}</dd>
                            </div>
                        </dl>
                        @if ($cve['description'])
                            <p class="pg-finding-desc">{{ $cve['description'] }}</p>
                        @endif
                    </article>
                @empty
                    <p class="pg-empty">Yeni CVE yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head">
                    <div>
                        <h2>Artık görülmeyen CVE’ler</h2>
                        <p>Önceki dönemde vardı, son {{ $days }} günde tekrarlanmadı.</p>
                    </div>
                </header>
                @forelse ($diff['gone_cves'] as $cve)
                    <article class="pg-finding pg-finding-compact">
                        <div class="pg-finding-top">
                            <div class="pg-finding-title">
                                <h3>{{ $cve['cve_id'] }}</h3>
                                <span class="pg-sev {{ $sevClass($cve['severity']) }}">{{ $cve['severity'] }}</span>
                            </div>
                            <span class="pg-diff-badge is-gone">Kayboldu</span>
                        </div>
                        <dl class="pg-finding-meta">
                            <div>
                                <dt>Servis</dt>
                                <dd>{{ $cve['service_name'] ?: '—' }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <p class="pg-empty">Artık görülmeyen CVE yok.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-admin-layout>
