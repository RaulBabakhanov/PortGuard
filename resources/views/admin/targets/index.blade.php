<x-admin-layout>
    <x-slot name="header"><h1>Hedefler</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('admin.targets.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Ad, IP, CIDR">
                    </div>
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
                    <a href="{{ route('admin.targets.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
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
                            <th>Hedef</th>
                            <th>Portlar</th>
                            <th>Tarama</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($targets as $target)
                        <tr>
                            <td>{{ $target->name }}</td>
                            <td>{{ $target->user?->email ?? '—' }}</td>
                            <td>{{ $target->label() }}</td>
                            <td>{{ $target->ports }}</td>
                            <td>{{ $target->scans_count }}</td>
                            <td>{{ $target->created_at?->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Hedef yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $targets->links() }}</div>
        </section>
    </div>
</x-admin-layout>
