<x-admin-layout>
    <x-slot name="header"><h1>Servisler</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('admin.services.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Servis, ürün, port">
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
                    <a href="{{ route('admin.services.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Port</th>
                            <th>Servis</th>
                            <th>Ürün</th>
                            <th>Sürüm</th>
                            <th>Kullanıcı</th>
                            <th>Tarama</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($services as $svc)
                        <tr>
                            <td>{{ $svc->port }}/{{ $svc->protocol }}</td>
                            <td>{{ $svc->name }}</td>
                            <td>{{ $svc->product ?: '—' }}</td>
                            <td>{{ $svc->version ?: '—' }}</td>
                            <td>{{ $svc->scan?->user?->email ?? '—' }}</td>
                            <td>
                                @if ($svc->scan)
                                    <a href="{{ route('admin.scans.show', $svc->scan) }}">{{ $svc->scan->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Servis yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $services->links() }}</div>
        </section>
    </div>
</x-admin-layout>
