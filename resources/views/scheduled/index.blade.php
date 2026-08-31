<x-app-layout>
    <x-slot name="header"><h1>Zamanlanmış Taramalar</h1></x-slot>
    <div class="pg-page pg-page-wide">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head"><h2>Yeni zamanlama</h2></header>
            @if ($targets->isEmpty())
                <p class="pg-empty">Önce bir <a href="{{ route('targets.create') }}">hedef ekleyin</a>.</p>
            @else
                <form method="POST" action="{{ route('scheduled.store') }}" class="pg-form">
                    @csrf
                    <div class="pg-form-grid">
                        <div class="pg-field"><label for="name">Ad</label><input id="name" name="name" required value="{{ old('name') }}"></div>
                        <div class="pg-field">
                            <label for="target_id">Hedef</label>
                            <select id="target_id" name="target_id" class="pg-select" required>
                                @foreach ($targets as $target)
                                    <option value="{{ $target->id }}" @selected(old('target_id')==$target->id)>{{ $target->name }} ({{ $target->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pg-field">
                            <label for="frequency">Sıklık</label>
                            <select id="frequency" name="frequency" class="pg-select">
                                <option value="daily" @selected(old('frequency')==='daily')>Günlük</option>
                                <option value="weekly" @selected(old('frequency')==='weekly')>Haftalık</option>
                                <option value="monthly" @selected(old('frequency')==='monthly')>Aylık</option>
                            </select>
                        </div>
                        <div class="pg-field"><label for="ports">Portlar</label><input id="ports" name="ports" value="{{ old('ports') }}" placeholder="Boş = hedeften al"></div>
                    </div>
                    <button class="pg-btn pg-btn-primary" type="submit">Kaydet</button>
                </form>
            @endif
        </section>

        <section class="pg-section">
            <header class="pg-section-head"><h2>Kayıtlar</h2></header>
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Ad</th><th>Hedef</th><th>Sıklık</th><th>Son çalıştırma</th><th>Sonraki</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><strong>{{ $item->name }}</strong><div class="pg-result-muted">{{ $item->ports }}</div></td>
                            <td>{{ $item->target?->name }}</td>
                            <td>{{ $item->frequency }}</td>
                            <td>{{ $item->last_run_at?->format('d.m.Y H:i') ?: '—' }}</td>
                            <td>{{ $item->next_run_at?->format('d.m.Y H:i') ?: '—' }}</td>
                            <td>
                                <span class="pg-status {{ $item->is_active ? 'pg-status-completed' : 'pg-status-failed' }}">
                                    {{ $item->is_active ? 'aktif' : 'pasif' }}
                                </span>
                            </td>
                            <td class="pg-actions">
                                <form method="POST" action="{{ route('scheduled.run', $item) }}">@csrf<button class="pg-btn pg-btn-primary" type="submit">Şimdi çalıştır</button></form>
                                <form method="POST" action="{{ route('scheduled.toggle', $item) }}">@csrf<button class="pg-btn pg-btn-ghost" type="submit">{{ $item->is_active ? 'Durdur' : 'Aç' }}</button></form>
                                <form method="POST" action="{{ route('scheduled.destroy', $item) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="pg-btn pg-btn-ghost" type="submit">Sil</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pg-empty">Zamanlanmış tarama yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $items->links() }}</div>
        </section>
    </div>
</x-app-layout>
