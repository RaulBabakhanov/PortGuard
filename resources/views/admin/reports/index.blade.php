<x-admin-layout>
    <x-slot name="header"><h1>Raporlar</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <div class="pg-stat-grid">
            <div class="pg-stat"><strong>{{ $totals['users'] }}</strong><span>Kullanıcı</span></div>
            <div class="pg-stat"><strong>{{ $totals['scans'] }}</strong><span>Tarama</span></div>
            <div class="pg-stat"><strong>{{ $totals['services'] }}</strong><span>Servis</span></div>
            <div class="pg-stat"><strong>{{ $totals['cves'] }}</strong><span>CVE</span></div>
            <div class="pg-stat"><strong>{{ $totals['targets'] }}</strong><span>Hedef</span></div>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head"><h2>Tarama durumları</h2></header>
                <div class="pg-stat-grid pg-stat-grid-sm">
                    @foreach (['completed' => 'Tamamlanan', 'failed' => 'Başarısız', 'running' => 'Çalışan', 'pending' => 'Bekleyen'] as $key => $label)
                        <div class="pg-stat"><strong>{{ $byStatus[$key] ?? 0 }}</strong><span>{{ $label }}</span></div>
                    @endforeach
                </div>
            </section>

            <section class="pg-section">
                <header class="pg-section-head"><h2>En aktif kullanıcılar</h2></header>
                @forelse ($topUsers as $user)
                    <a href="{{ route('admin.users.show', $user) }}" class="pg-list-row">
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                        </div>
                        <em>{{ $user->scans_count }} tarama</em>
                    </a>
                @empty
                    <p class="pg-empty">Kayıt yok.</p>
                @endforelse
            </section>
        </div>

        <div class="pg-toolbar">
            <a href="{{ route('admin.scans.index') }}" class="pg-btn pg-btn-primary">Taramalara git</a>
            <a href="{{ route('admin.cves.index') }}" class="pg-btn pg-btn-ghost">CVE listesi</a>
            <a href="{{ route('admin.logs.index') }}" class="pg-btn pg-btn-ghost">Aktivite logları</a>
        </div>
    </div>
</x-admin-layout>
