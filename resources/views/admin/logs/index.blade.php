<x-admin-layout>
    <x-slot name="header"><h1>Aktivite logları</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('admin.logs.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Açıklama, aksiyon, IP, kullanıcı">
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
                    <div class="pg-field">
                        <label for="user_id">Kullanıcı</label>
                        <select id="user_id" name="user_id" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected($userId === $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('admin.logs.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Kullanıcı</th>
                            <th>Aksiyon</th>
                            <th>Açıklama</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td>
                                @if ($log->user)
                                    <a href="{{ route('admin.users.show', $log->user) }}">{{ $log->user->email }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="pg-log-action">{{ $log->actionLabel() }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ipLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="pg-empty">Log yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $logs->links() }}</div>
        </section>
    </div>
</x-admin-layout>
