<x-admin-layout>
    <x-slot name="header"><h1>Birimler</h1></x-slot>
    <div class="pg-page">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div><h2>{{ $editing ? 'Birimi düzenle' : 'Yeni birim' }}</h2></div>
            </header>
            <form method="POST" action="{{ $editing ? route('admin.departments.update', $editing) : route('admin.departments.store') }}" class="pg-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="pg-form-grid">
                    <div class="pg-field"><label>Ad</label><input name="name" required value="{{ old('name', $editing?->name) }}" placeholder="Bilgi İşlem"></div>
                    <div class="pg-field"><label>Kod</label><input name="code" required value="{{ old('code', $editing?->code) }}" placeholder="BILGI"></div>
                    <div class="pg-field"><label>Not</label><input name="notes" value="{{ old('notes', $editing?->notes) }}"></div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">{{ $editing ? 'Güncelle' : 'Birim ekle' }}</button>
                    @if ($editing)
                        <a href="{{ route('admin.departments.index') }}" class="pg-btn pg-btn-ghost">İptal</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead><tr><th>Ad</th><th>Kod</th><th>Kullanıcı</th><th>Varlık</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($departments as $dept)
                        <tr>
                            <td>{{ $dept->name }}</td>
                            <td>{{ $dept->code }}</td>
                            <td>{{ $dept->users_count }}</td>
                            <td>{{ $dept->assets_count }}</td>
                            <td>
                                <div class="pg-admin-card-actions">
                                    <a href="{{ route('admin.departments.index', ['edit' => $dept->id]) }}" class="pg-btn pg-btn-ghost">Düzenle</a>
                                    <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="pg-btn pg-btn-danger" type="submit">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="pg-empty">Birim yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $departments->links() }}</div>
        </section>
    </div>
</x-admin-layout>
