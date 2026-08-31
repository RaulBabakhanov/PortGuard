<x-app-layout>
    <x-slot name="header"><h1>Hedefler</h1></x-slot>
    <div class="pg-page pg-page-wide">
        <div class="pg-toolbar">
            <a href="{{ route('targets.create') }}" class="pg-btn pg-btn-primary">Hedef ekle</a>
        </div>
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Ad</th><th>Tür</th><th>Değer</th><th>Portlar</th><th>Not</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($targets as $target)
                        <tr>
                            <td><strong>{{ $target->name }}</strong></td>
                            <td>{{ $target->type }}</td>
                            <td>{{ $target->label() }}</td>
                            <td>{{ $target->ports }}</td>
                            <td>{{ $target->notes ? \Illuminate\Support\Str::limit($target->notes, 40) : '—' }}</td>
                            <td class="pg-actions">
                                <a class="pg-btn pg-btn-ghost" href="{{ route('scans.create', ['target_id' => $target->id]) }}">Tara</a>
                                <a class="pg-btn pg-btn-ghost" href="{{ route('targets.edit', $target) }}">Düzenle</a>
                                <form method="POST" action="{{ route('targets.destroy', $target) }}" onsubmit="return confirm('Silinsin mi?')">
                                    @csrf @method('DELETE')
                                    <button class="pg-btn pg-btn-ghost" type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pg-empty">Henüz hedef yok. Yeni hedef ekleyip hızlı tarama başlatabilirsiniz.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $targets->links() }}</div>
        </section>
    </div>
</x-app-layout>
