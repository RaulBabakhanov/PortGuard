<x-admin-layout>
    <x-slot name="header"><h1>{{ $user->name }}</h1></x-slot>

    <div class="pg-page pg-page-wide">
        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif

        <div class="pg-page-intro">
            <h2>{{ $user->email }}</h2>
            <p>
                Kayıt: {{ $user->created_at->format('d.m.Y H:i') }}
                · Birim: {{ $user->department?->name ?? '—' }}
            </p>
        </div>

        <div class="pg-stat-grid pg-stat-grid-sm">
            <div class="pg-stat"><strong>{{ $user->scans_count }}</strong><span>Tarama</span></div>
            <div class="pg-stat"><strong>{{ $user->targets_count }}</strong><span>Hedef</span></div>
            <div class="pg-stat"><strong>{{ $user->cve_findings_count }}</strong><span>CVE</span></div>
            <div class="pg-stat"><strong>{{ $user->activity_logs_count }}</strong><span>Log</span></div>
        </div>

        <div class="pg-toolbar">
            <a href="{{ route('admin.users.edit', $user) }}" class="pg-btn pg-btn-primary">Düzenle</a>
            <a href="{{ route('admin.logs.index', ['user_id' => $user->id]) }}" class="pg-btn pg-btn-ghost">Tüm loglar</a>
            <a href="{{ route('admin.scans.index', ['user_id' => $user->id]) }}" class="pg-btn pg-btn-ghost">Tüm taramalar</a>
            <a href="{{ route('admin.users.index') }}" class="pg-btn pg-btn-ghost">Listeye dön</a>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Bu kullanıcı ve ilişkili veriler silinecek. Emin misiniz?')">
                @csrf @method('DELETE')
                <button type="submit" class="pg-btn pg-btn-danger">Sil</button>
            </form>
        </div>

        <div class="pg-split">
            <section class="pg-section">
                <header class="pg-section-head"><h2>Son taramalar</h2></header>
                @forelse ($scans as $scan)
                    <a href="{{ route('admin.scans.show', $scan) }}" class="pg-list-row">
                        <div>
                            <strong>{{ $scan->name }}</strong>
                            <span>{{ $scan->summaryLabel() }} · {{ $scan->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <em class="pg-status pg-status-{{ $scan->status }}">{{ $scan->status }}</em>
                    </a>
                @empty
                    <p class="pg-empty">Tarama yok.</p>
                @endforelse
            </section>

            <section class="pg-section">
                <header class="pg-section-head"><h2>Son loglar</h2></header>
                <div class="pg-table-wrap">
                    <table class="pg-table">
                        <thead><tr><th>Zaman</th><th>Aksiyon</th><th>Açıklama</th></tr></thead>
                        <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ $log->actionLabel() }}</td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="pg-empty">Log yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
