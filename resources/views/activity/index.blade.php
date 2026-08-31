<x-app-layout>
    <x-slot name="header"><h1>Aktivite Logları</h1></x-slot>
    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('activity.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Açıklama, aksiyon, IP">
                    </div>
                    <div class="pg-field">
                        <label for="action">Aksiyon</label>
                        <select id="action" name="action" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($actions as $act)
                                <option value="{{ $act }}" @selected($action === $act)>
                                    {{ \App\Models\ActivityLog::actionLabels()[$act] ?? $act }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('activity.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Zaman</th><th>Aksiyon</th><th>Açıklama</th><th>IP</th></tr></thead>
                    <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td><span class="pg-log-action">{{ $log->actionLabel() }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ipLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="pg-empty">Log yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $logs->links() }}</div>
        </section>
    </div>
</x-app-layout>
