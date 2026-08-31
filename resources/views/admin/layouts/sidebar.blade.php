@php
    $admin = Auth::guard('admin')->user();
    $initial = strtoupper(mb_substr($admin->name ?? 'A', 0, 1));
    $pendingApprovals = \App\Models\Scan::query()->where('status', 'awaiting_approval')->count();
@endphp

<aside
    class="pg-sidebar admin-sidebar"
    :class="{ 'is-open': sidebarOpen, 'is-collapsed': sidebarCollapsed }"
    aria-label="Yönetim menüsü"
>
    <div class="pg-sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="pg-sidebar-logo" title="PortGuard Admin">
            <span class="pg-sidebar-mark" aria-hidden="true"></span>
            <span class="pg-sidebar-name">PortGuard Admin</span>
        </a>
        <button type="button" class="pg-sidebar-close" @click="sidebarOpen = false" aria-label="Menüyü kapat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="pg-sidebar-nav">
        <div class="pg-nav-group">
            <p class="pg-nav-label">Genel</p>
            <a href="{{ route('admin.dashboard') }}" class="pg-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" title="Özet">
                <span class="pg-nav-text">Özet</span>
            </a>
            <a href="{{ route('admin.executive') }}" class="pg-nav-link {{ request()->routeIs('admin.executive') ? 'is-active' : '' }}" title="Yönetici özeti">
                <span class="pg-nav-text">Yönetici özeti</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="pg-nav-link {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" title="Raporlar">
                <span class="pg-nav-text">Raporlar</span>
            </a>
            <a href="{{ route('admin.audit.comparison') }}" class="pg-nav-link {{ request()->routeIs('admin.audit.comparison') ? 'is-active' : '' }}" title="Karşılaştırma">
                <span class="pg-nav-text">Karşılaştırma</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Belediye</p>
            <a href="{{ route('admin.departments.index') }}" class="pg-nav-link {{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Birimler</span>
            </a>
            <a href="{{ route('admin.networks.index') }}" class="pg-nav-link {{ request()->routeIs('admin.networks.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">İzinli ağlar</span>
            </a>
            <a href="{{ route('admin.assets.index') }}" class="pg-nav-link {{ request()->routeIs('admin.assets.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Varlık envanteri</span>
            </a>
            <a href="{{ route('admin.approvals.index') }}" class="pg-nav-link {{ request()->routeIs('admin.approvals.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Tarama onayları</span>
                @if ($pendingApprovals > 0)
                    <em class="pg-nav-count">{{ $pendingApprovals }}</em>
                @endif
            </a>
            <a href="{{ route('admin.audit.index') }}" class="pg-nav-link {{ request()->routeIs('admin.audit.index') || request()->routeIs('admin.audit.settings') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Denetim paketi</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Kullanıcılar</p>
            <a href="{{ route('admin.users.index') }}" class="pg-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Kullanıcılar</span>
            </a>
            <a href="{{ route('admin.logs.index') }}" class="pg-nav-link {{ request()->routeIs('admin.logs.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Aktivite logları</span>
            </a>
            <a href="{{ route('admin.admins.index') }}" class="pg-nav-link {{ request()->routeIs('admin.admins.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Admin hesapları</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Tarama</p>
            <a href="{{ route('admin.scans.index') }}" class="pg-nav-link {{ request()->routeIs('admin.scans.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Tüm taramalar</span>
            </a>
            <a href="{{ route('admin.targets.index') }}" class="pg-nav-link {{ request()->routeIs('admin.targets.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Hedefler</span>
            </a>
            <a href="{{ route('admin.scheduled.index') }}" class="pg-nav-link {{ request()->routeIs('admin.scheduled.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Zamanlanmış</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Analiz</p>
            <a href="{{ route('admin.services.index') }}" class="pg-nav-link {{ request()->routeIs('admin.services.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">Servisler</span>
            </a>
            <a href="{{ route('admin.cves.index') }}" class="pg-nav-link {{ request()->routeIs('admin.cves.*') ? 'is-active' : '' }}">
                <span class="pg-nav-text">CVE bulguları</span>
            </a>
        </div>
    </nav>

    <div class="pg-sidebar-foot">
        <div class="pg-user-chip">
            <div class="pg-user-avatar">{{ $initial }}</div>
            <div class="pg-user-meta">
                <strong>{{ $admin->name }}</strong>
                <span>{{ $admin->email }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="pg-logout-btn"><span class="pg-nav-text">Çıkış yap</span></button>
        </form>
    </div>
</aside>
