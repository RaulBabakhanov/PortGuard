<x-admin-layout>
    <x-slot name="header"><h1>Kullanıcı düzenle</h1></x-slot>

    <div class="pg-page">
        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>{{ $user->email }}</h2>
                    <p>Ad, e-posta ve birim bilgilerini güncelleyin.</p>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="pg-form">
                @csrf
                @method('PUT')
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="name">Ad</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pg-field">
                        <label for="email">E-posta</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pg-field">
                        <label for="department_id">Birim</label>
                        <select id="department_id" name="department_id" class="pg-select">
                            <option value="">—</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id', $user->department_id) == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button type="submit" class="pg-btn pg-btn-primary">Kaydet</button>
                    <a href="{{ route('admin.users.show', $user) }}" class="pg-btn pg-btn-ghost">İptal</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Şifre sıfırla</h2>
                    <p>Kullanıcı yeni şifreyle giriş yapabilir.</p>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="pg-form">
                @csrf
                <div class="pg-form-grid">
                    <x-admin-password-fields
                        password-label="Yeni şifre"
                        confirm-label="Yeni şifre (tekrar)"
                    />
                </div>
                <div class="pg-form-actions">
                    <button type="submit" class="pg-btn pg-btn-primary">Şifreyi güncelle</button>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <header class="pg-section-head">
                <div>
                    <h2>Tehlikeli alan</h2>
                    <p>Kullanıcı silinince taramaları, CVE’leri ve logları da silinir.</p>
                </div>
            </header>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Bu kullanıcı kalıcı olarak silinecek. Emin misiniz?')">
                @csrf @method('DELETE')
                <button type="submit" class="pg-btn pg-btn-danger">Kullanıcıyı sil</button>
            </form>
        </section>
    </div>
</x-admin-layout>
