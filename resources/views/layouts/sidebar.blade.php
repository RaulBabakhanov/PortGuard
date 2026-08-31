{{-- Kullanıcı paneli sidebar --}}
@php
    $user = $pgUser ?? Auth::user();
    $initial = strtoupper(mb_substr($user->name ?? 'U', 0, 1));
    $unread = $pgUnread ?? 0;
@endphp

<aside
    class="pg-sidebar"
    :class="{ 'is-open': sidebarOpen, 'is-collapsed': sidebarCollapsed }"
    aria-label="Kullanıcı menüsü"
>
    <div class="pg-sidebar-brand">
        <a href="{{ route('dashboard') }}" class="pg-sidebar-logo" title="PortGuard">
            <span class="pg-sidebar-mark" aria-hidden="true"></span>
            <span class="pg-sidebar-name">PortGuard</span>
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
            <a href="{{ route('dashboard') }}" class="pg-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" title="Panel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z" stroke-linejoin="round"/></svg>
                <span class="pg-nav-text">Panel</span>
            </a>
            <a href="{{ route('notifications.index') }}" class="pg-nav-link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}" title="Bildirimler">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" stroke-linecap="round"/><path d="M9 17a3 3 0 0 0 6 0" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Bildirimler</span>
                @if ($unread > 0)
                    <em class="pg-nav-count">{{ $unread }}</em>
                @endif
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Tarama</p>
            <a href="{{ route('scans.create') }}" class="pg-nav-link {{ request()->routeIs('scans.create') ? 'is-active' : '' }}" title="Yeni Tarama">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/><path d="M8 11h6M11 8v6" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Yeni Tarama</span>
            </a>
            <a href="{{ route('scans.index') }}" class="pg-nav-link {{ request()->routeIs('scans.index') || request()->routeIs('scans.show') ? 'is-active' : '' }}" title="Tarama Geçmişi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Tarama Geçmişi</span>
            </a>
            <a href="{{ route('targets.index') }}" class="pg-nav-link {{ request()->routeIs('targets.*') ? 'is-active' : '' }}" title="Hedefler">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Hedefler</span>
            </a>
            <a href="{{ route('scheduled.index') }}" class="pg-nav-link {{ request()->routeIs('scheduled.*') ? 'is-active' : '' }}" title="Zamanlanmış">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Zamanlanmış</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Analiz</p>
            <a href="{{ route('services.index') }}" class="pg-nav-link {{ request()->routeIs('services.*') ? 'is-active' : '' }}" title="Servisler">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="4" height="8" rx="1"/><rect x="10" y="6" width="4" height="12" rx="1"/><rect x="16" y="3" width="4" height="15" rx="1"/></svg>
                <span class="pg-nav-text">Servisler</span>
            </a>
            <a href="{{ route('cves.index') }}" class="pg-nav-link {{ request()->routeIs('cves.*') ? 'is-active' : '' }}" title="CVE Bulguları">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke-linejoin="round"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">CVE Bulguları</span>
            </a>
            <a href="{{ route('reports.index') }}" class="pg-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" title="Raporlar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke-linejoin="round"/><path d="M14 3v5h5M8 13h8M8 17h6" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Raporlar</span>
            </a>
        </div>

        <div class="pg-nav-group">
            <p class="pg-nav-label">Hesap</p>
            <a href="{{ route('activity.index') }}" class="pg-nav-link {{ request()->routeIs('activity.*') ? 'is-active' : '' }}" title="Aktivite Logları">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19V5M8 19V9M12 19v-6M16 19V8M20 19v-4" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Aktivite Logları</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="pg-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" title="Profil">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 19.5c1.5-3.2 4-4.8 7-4.8s5.5 1.6 7 4.8" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Profil</span>
            </a>
            <a href="{{ route('settings.edit') }}" class="pg-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}" title="Ayarlar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4" stroke-linecap="round"/></svg>
                <span class="pg-nav-text">Ayarlar</span>
            </a>
        </div>
    </nav>

    <div class="pg-sidebar-foot">
        <div class="pg-user-chip" :title="sidebarCollapsed ? '{{ e($user->name) }}' : null">
            <div class="pg-user-avatar" aria-hidden="true">{{ $initial }}</div>
            <div class="pg-user-meta">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->email }}</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="pg-logout-btn" title="Çıkış yap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 7V6a2 2 0 0 1 2-2h7v16h-7a2 2 0 0 1-2-2v-1" stroke-linecap="round"/><path d="M15 12H4m0 0 3-3m-3 3 3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="pg-nav-text">Çıkış yap</span>
            </button>
        </form>
    </div>
</aside>
