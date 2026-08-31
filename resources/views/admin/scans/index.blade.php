<x-admin-layout>
    <x-slot name="header"><h1>Tüm taramalar</h1></x-slot>

    <div class="pg-page pg-page-wide">
        <section class="pg-section">
            <form method="GET" action="{{ route('admin.scans.index') }}" class="pg-form">
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Ad, IP, CIDR">
                    </div>
                    <div class="pg-field">
                        <label for="status">Durum</label>
                        <select id="status" name="status" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach (['pending', 'running', 'completed', 'failed'] as $st)
                                <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
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
                    <a href="{{ route('admin.scans.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
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
                            <th>Durum</th>
                            <th>Host</th>
                            <th>CVE</th>
                            <th>Tarih</th>
                            <th>PDF hash</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($scans as $scan)
                        <tr>
                            <td><a href="{{ route('admin.scans.show', $scan) }}">{{ $scan->name }}</a></td>
                            <td>{{ $scan->user?->email ?? '—' }}</td>
                            <td>{{ $scan->summaryLabel() }}</td>
                            <td><span class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</span></td>
                            <td>{{ $scan->active_hosts }}/{{ $scan->total_hosts }}</td>
                            <td>{{ $scan->cve_count }}</td>
                            <td>{{ $scan->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                @if ($scan->report)
                                    <code title="{{ $scan->report->content_sha256 }}">{{ $scan->report->shortHash() }}…</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('admin.scans.show', $scan) }}" class="pg-btn pg-btn-ghost">Sonuç</a>
                                <a href="{{ route('admin.scans.pdf', $scan) }}" class="pg-btn pg-btn-ghost">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="pg-empty">Tarama yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $scans->links() }}</div>
        </section>
    </div>
</x-admin-layout>
