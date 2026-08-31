<x-app-layout>
    <x-slot name="header"><h1>Tarama Geçmişi</h1></x-slot>

    <div class="pg-page">
        <div class="pg-toolbar">
            <a href="{{ route('scans.create') }}" class="pg-btn pg-btn-primary">Yeni tarama</a>
        </div>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Hedef</th>
                            <th>Durum</th>
                            <th>Host</th>
                            <th>CVE</th>
                            <th>Tarih</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scans as $scan)
                            <tr>
                                <td><a href="{{ route('scans.show', $scan) }}">{{ $scan->name }}</a></td>
                                <td>{{ $scan->summaryLabel() }}</td>
                                <td><span class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</span></td>
                                <td>{{ $scan->active_hosts }}/{{ $scan->total_hosts }}</td>
                                <td>{{ $scan->cve_count }}</td>
                                <td>{{ $scan->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('scans.destroy', $scan) }}" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="pg-btn pg-btn-ghost" type="submit">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="pg-empty">Kayıt yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $scans->links() }}</div>
        </section>
    </div>
</x-app-layout>
