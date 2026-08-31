<x-app-layout>
    <x-slot name="header"><h1>Bildirimler</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        <div class="pg-toolbar">
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="pg-btn pg-btn-ghost">Tümünü okundu yap</button></form>
        </div>
        <section class="pg-section">
            @forelse ($notifications as $notification)
                @php $scanId = data_get($notification->data, 'scan_id'); @endphp
                <div class="pg-list-row {{ $notification->read_at ? '' : 'is-unread' }}">
                    <div>
                        <strong>{{ $notification->title }}</strong>
                        <span>
                            {{ $notification->body }}
                            · {{ $notification->created_at->format('d.m.Y H:i') }}
                            @if ($notification->type)
                                · <code>{{ $notification->type }}</code>
                            @endif
                        </span>
                    </div>
                    <div class="pg-actions">
                        @if ($scanId)
                            <a class="pg-btn pg-btn-ghost" href="{{ route('scans.show', $scanId) }}">Taramaya git</a>
                        @endif
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf<button class="pg-btn pg-btn-primary" type="submit">{{ $scanId ? 'Okundu + aç' : 'Okundu' }}</button></form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="pg-empty">Bildirim yok. Tarama tamamlanınca burada görünür.</p>
            @endforelse
            <div class="pg-pagination">{{ $notifications->links() }}</div>
        </section>
    </div>
</x-app-layout>
