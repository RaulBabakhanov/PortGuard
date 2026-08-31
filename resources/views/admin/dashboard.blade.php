<x-admin-layout>
    <x-slot name="header"><h1>Yönetim özeti</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <div class="pg-page-intro">
            <h2>PortGuard Yönetim</h2>
            <p>Tüm panel kullanıcıları, taramalar ve aktivite logları buradan izlenir.</p>
        </div>

        <div class="pg-stat-grid">
            <a href="{{ route('admin.users.index') }}" class="pg-stat"><strong>{{ $stats['users'] }}</strong><span>Kullanıcı</span></a>
            <a href="{{ route('admin.admins.index') }}" class="pg-stat"><strong>{{ $stats['admins'] }}</strong><span>Admin</span></a>
            <a href="{{ route('admin.scans.index') }}" class="pg-stat"><strong>{{ $stats['scans'] }}</strong><span>Tarama</span></a>
            <a href="{{ route('admin.cves.index') }}" class="pg-stat"><strong>{{ $stats['cves'] }}</strong><span>CVE</span></a>
            <a href="{{ route('admin.logs.index') }}" class="pg-stat"><strong>{{ $stats['logs'] }}</strong><span>Log kaydı</span></a>
        </div>

        <div class="pg-quick-grid">
            <a class="pg-quick-link" href="{{ route('admin.users.create') }}">
                <strong>Yeni kullanıcı</strong>
                <span>Panel hesabı oluştur</span>
            </a>
            <a class="pg-quick-link" href="{{ route('admin.scans.index') }}">
                <strong>Tüm taramalar</strong>
                <span>Sonuç ve PDF</span>
            </a>
            <a class="pg-quick-link" href="{{ route('admin.logs.index') }}">
                <strong>Aktivite logları</strong>
                <span>Kullanıcı hareketleri</span>
            </a>
            <a class="pg-quick-link" href="{{ route('admin.reports.index') }}">
                <strong>Raporlar</strong>
                <span>Özet istatistikler</span>
            </a>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head">
                    <h2>Son kullanıcılar</h2>
                    <a href="{{ route('admin.users.index') }}" class="pg-btn pg-btn-ghost">Tümü</a>
                </header>
                @forelse ($recentUsers as $user)
                    <a href="{{ route('admin.users.show', $user) }}" class="pg-list-row">
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }} · {{ $user->created_at->format('d.m.Y') }}</span>
                        </div>
                    </a>
                @empty
                    <p class="pg-empty">Kullanıcı yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head">
                    <h2>Son taramalar</h2>
                    <a href="{{ route('admin.scans.index') }}" class="pg-btn pg-btn-ghost">Tümü</a>
                </header>
                @forelse ($recentScans as $scan)
                    <a href="{{ route('admin.scans.show', $scan) }}" class="pg-list-row">
                        <div>
                            <strong>{{ $scan->name }}</strong>
                            <span>{{ $scan->user?->email }} · {{ $scan->summaryLabel() }}</span>
                        </div>
                        <em class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</em>
                    </a>
                @empty
                    <p class="pg-empty">Tarama yok.</p>
                @endforelse
            </section>
        </div>

        <section class="pg-section">
            <header class="pg-section-head">
                <h2>Son aktivite</h2>
                <a href="{{ route('admin.logs.index') }}" class="pg-btn pg-btn-ghost">Tümü</a>
            </header>
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Kullanıcı</th>
                            <th>Aksiyon</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $log->user?->email ?? '—' }}</td>
                            <td><span class="pg-log-action">{{ $log->actionLabel() }}</span></td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="pg-empty">Log yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
