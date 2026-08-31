<x-admin-layout>
    <x-slot name="header"><h1>Tarama onayları</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Onay bekleyen taramalar</h2>
                    <p>Geniş aralık veya kritik varlık içeren istekler burada onaylanır / reddedilir.</p>
                </div>
            </header>

            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ad</th>
                            <th>Kullanıcı</th>
                            <th>Hedef</th>
                            <th>Host</th>
                            <th>Gerekçe</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($pending as $scan)
                        <tr>
                            <td>{{ $scan->id }}</td>
                            <td><a href="{{ route('admin.scans.show', $scan) }}">{{ $scan->name }}</a></td>
                            <td>{{ $scan->user?->email }}</td>
                            <td>{{ $scan->summaryLabel() }}</td>
                            <td>{{ $scan->total_hosts }}</td>
                            <td>{{ $scan->approval_reason }}</td>
                            <td>
                                <div class="pg-admin-card-actions">
                                    <form method="POST" action="{{ route('admin.approvals.approve', $scan) }}">
                                        @csrf
                                        <button class="pg-btn pg-btn-primary" type="submit">Onayla ve çalıştır</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.approvals.reject', $scan) }}" class="pg-inline-form">
                                        @csrf
                                        <input name="rejection_reason" placeholder="Red gerekçesi" required>
                                        <button class="pg-btn pg-btn-danger" type="submit">Reddet</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pg-empty">Onay bekleyen tarama yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $pending->links() }}</div>
        </section>
    </div>
</x-admin-layout>
