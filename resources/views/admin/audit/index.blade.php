<x-admin-layout>
    <x-slot name="header"><h1>Denetim paketi</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>KVKK / politika ayarları</h2>
                    <p>Saklama süresi {{ $settings->kvkk_retention_days }} gün · eşik altı tarihten eski raporlar: {{ $retentionCutoff->format('d.m.Y') }} · yakında süresi dolacak: {{ $expiringSoon }}</p>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.audit.settings') }}" class="pg-form">
                @csrf
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="kvkk_retention_days">KVKK saklama (gün)</label>
                        <input id="kvkk_retention_days" type="number" name="kvkk_retention_days" value="{{ $settings->kvkk_retention_days }}" min="30" max="3650" required>
                    </div>
                    <div class="pg-field">
                        <label for="approval_host_threshold">Onay eşiği (host sayısı)</label>
                        <input id="approval_host_threshold" type="number" name="approval_host_threshold" value="{{ $settings->approval_host_threshold }}" min="2" max="256" required>
                    </div>
                </div>

                <div class="pg-toggle-list">
                    <label class="pg-toggle-row">
                        <div>
                            <strong>İzinli ağ zorunlu</strong>
                            <span>Açıkken yalnızca envanterdeki IP/CIDR’ler taranabilir.</span>
                        </div>
                        <span class="pg-switch">
                            <input type="checkbox" name="enforce_allowed_networks" value="1" @checked($settings->enforce_allowed_networks)>
                            <span class="pg-switch-slider"></span>
                        </span>
                    </label>
                    <label class="pg-toggle-row">
                        <div>
                            <strong>Kritik varlık için onay</strong>
                            <span>Yüksek/kritik varlıklara tarama yönetici onayı ister.</span>
                        </div>
                        <span class="pg-switch">
                            <input type="checkbox" name="require_approval_for_critical" value="1" @checked($settings->require_approval_for_critical)>
                            <span class="pg-switch-slider"></span>
                        </span>
                    </label>
                </div>

                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary pg-btn-block-sm" type="submit">Kaydet</button>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Kayıtlar</h2>
                    <p>Aynı anda yalnızca seçili sekme yüklenir — sayfa daha hızlı açılır.</p>
                </div>
            </header>

            <div class="pg-period-tabs">
                <a href="{{ route('admin.audit.index', ['tab' => 'admin_logs']) }}"
                   class="pg-period-tab {{ $tab === 'admin_logs' ? 'is-active' : '' }}">Admin logu</a>
                <a href="{{ route('admin.audit.index', ['tab' => 'downloads']) }}"
                   class="pg-period-tab {{ $tab === 'downloads' ? 'is-active' : '' }}">PDF indirmeleri</a>
                <a href="{{ route('admin.audit.index', ['tab' => 'reports']) }}"
                   class="pg-period-tab {{ $tab === 'reports' ? 'is-active' : '' }}">PDF arşivi</a>
            </div>

            @if ($tab === 'admin_logs' && $adminLogs)
                <div class="pg-table-wrap" style="margin-top:1rem;">
                    <table class="pg-table">
                        <thead><tr><th>Zaman</th><th>Admin</th><th>Aksiyon</th><th>Açıklama</th><th>IP</th></tr></thead>
                        <tbody>
                        @forelse ($adminLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                <td>{{ $log->admin?->email ?? '—' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="pg-empty">Admin logu yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pg-pagination">{{ $adminLogs->links() }}</div>
            @endif

            @if ($tab === 'downloads' && $downloads)
                <div class="pg-table-wrap" style="margin-top:1rem;">
                    <table class="pg-table">
                        <thead><tr><th>Zaman</th><th>Tarama</th><th>Kim</th><th>Tür</th><th>SHA-256</th><th>IP</th></tr></thead>
                        <tbody>
                        @forelse ($downloads as $dl)
                            <tr>
                                <td>{{ $dl->created_at->format('d.m.Y H:i:s') }}</td>
                                <td>#{{ $dl->scan_id }} {{ $dl->scan?->name }}</td>
                                <td>{{ $dl->actor_email ?? '—' }}</td>
                                <td>{{ $dl->actor_type }}</td>
                                <td><code class="pg-code">{{ $dl->content_sha256 ? substr($dl->content_sha256, 0, 16).'…' : '—' }}</code></td>
                                <td>{{ $dl->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="pg-empty">İndirme yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pg-pagination">{{ $downloads->links() }}</div>
            @endif

            @if ($tab === 'reports' && $immutableReports)
                <div class="pg-table-wrap" style="margin-top:1rem;">
                    <table class="pg-table">
                        <thead><tr><th>Tarama</th><th>Dosya</th><th>SHA-256</th><th>Boyut</th><th>Tarih</th></tr></thead>
                        <tbody>
                        @forelse ($immutableReports as $rep)
                            <tr>
                                <td><a href="{{ route('admin.scans.show', $rep->scan_id) }}">#{{ $rep->scan_id }}</a></td>
                                <td>{{ $rep->filename }}</td>
                                <td><code class="pg-code">{{ $rep->content_sha256 }}</code></td>
                                <td>{{ number_format($rep->byte_size / 1024, 1) }} KB</td>
                                <td>{{ $rep->created_at?->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="pg-empty">Arşiv boş.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pg-pagination">{{ $immutableReports->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
