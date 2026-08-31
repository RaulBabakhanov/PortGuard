<x-admin-layout>
    <x-slot name="header"><h1>Zamanlanmış taramalar</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('admin.scheduled.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="user_id">Kullanıcı</label>
                        <select id="user_id" name="user_id" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected($userId === $u->id)>{{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('admin.scheduled.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Kullanıcı</th>
                            <th>Sıklık</th>
                            <th>Durum</th>
                            <th>Sonraki</th>
                            <th>Son çalışma</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->user?->email ?? '—' }}</td>
                            <td>{{ $item->frequency }}</td>
                            <td>
                                <span class="pg-status {{ $item->is_active ? 'pg-status-completed' : 'pg-status-failed' }}">
                                    {{ $item->is_active ? 'aktif' : 'pasif' }}
                                </span>
                            </td>
                            <td>{{ $item->next_run_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>{{ $item->last_run_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Zamanlanmış tarama yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $items->links() }}</div>
        </section>
    </div>
</x-admin-layout>
