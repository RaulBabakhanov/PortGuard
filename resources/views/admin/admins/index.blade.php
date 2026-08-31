<x-admin-layout>
    <x-slot name="header"><h1>Admin hesapları</h1></x-slot>

    <div
        class="pg-page pg-page-wide"
        x-data
        x-init="@if (!empty($openModal)) $dispatch('open-modal', '{{ $openModal }}') @endif"
    >
        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->has('admin'))
            <div class="pg-alert pg-alert-danger">{{ $errors->first('admin') }}</div>
        @endif

        <div class="pg-toolbar">
            <button type="button" class="pg-btn pg-btn-primary" @click="$dispatch('open-modal', 'admin-create')">
                Yeni admin
            </button>
        </div>

        <section class="pg-section">
            <header class="pg-section-head"><h2>Mevcut adminler</h2></header>

            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>E-posta</th>
                            <th>Durum</th>
                            <th>Son giriş</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($admins as $admin)
                        <tr>
                            <td>{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                <span class="pg-status {{ $admin->is_active ? 'pg-status-completed' : 'pg-status-failed' }}">
                                    {{ $admin->is_active ? 'aktif' : 'pasif' }}
                                </span>
                            </td>
                            <td>{{ $admin->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                <a
                                    href="{{ route('admin.admins.index', ['open' => 'admin-'.$admin->id]) }}"
                                    class="pg-btn pg-btn-ghost"
                                >
                                    Detay / düzenle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="pg-empty">Admin yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pg-pagination">{{ $admins->links() }}</div>
        </section>
    </div>

    <x-admin-modal name="admin-create" title="Yeni admin" subtitle="Yönetim paneline giriş yetkisi verir." wide>
        <form method="POST" action="{{ route('admin.admins.store') }}" class="pg-form">
            @csrf
            <input type="hidden" name="_modal" value="admin-create">
            <div class="pg-form-grid">
                <div class="pg-field">
                    <label for="create_admin_name">Ad</label>
                    <input id="create_admin_name" name="name" value="{{ old('name') }}" required autocomplete="name">
                    @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="pg-field">
                    <label for="create_admin_email">E-posta</label>
                    <input id="create_admin_email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                    @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                </div>
                <x-admin-password-fields
                    password-id="create_admin_password"
                    confirm-id="create_admin_password_confirmation"
                    modal-key="admin-create"
                />
            </div>
            <div class="pg-form-actions">
                <button type="submit" class="pg-btn pg-btn-primary">Admin oluştur</button>
                <button type="button" class="pg-btn pg-btn-ghost" @click="$dispatch('close-modal', 'admin-create')">İptal</button>
            </div>
        </form>
    </x-admin-modal>

    @foreach ($admins as $admin)
        @if ($openModal === 'admin-'.$admin->id)
        <x-admin-modal
            name="admin-{{ $admin->id }}"
            :title="$admin->name"
            :subtitle="$admin->email"
            wide
            :show="true"
        >
            <div class="pg-modal-meta">
                <div>Durum: <strong>{{ $admin->is_active ? 'Aktif' : 'Pasif' }}</strong></div>
                <div>Son giriş: <strong>{{ $admin->last_login_at?->format('d.m.Y H:i') ?? '—' }}</strong></div>
            </div>

            <div class="pg-modal-section">
                <h3>Bilgileri düzenle</h3>
                <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="pg-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="admin-{{ $admin->id }}">
                    <div class="pg-form-grid">
                        <div class="pg-field">
                            <label for="admin_name_{{ $admin->id }}">Ad</label>
                            <input id="admin_name_{{ $admin->id }}" name="name" value="{{ old('_modal') === 'admin-'.$admin->id ? old('name') : $admin->name }}" required>
                            @if (old('_modal') === 'admin-'.$admin->id)
                                @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                        <div class="pg-field">
                            <label for="admin_email_{{ $admin->id }}">E-posta</label>
                            <input id="admin_email_{{ $admin->id }}" type="email" name="email" value="{{ old('_modal') === 'admin-'.$admin->id ? old('email') : $admin->email }}" required>
                            @if (old('_modal') === 'admin-'.$admin->id)
                                @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    </div>
                    <div class="pg-form-actions">
                        <button type="submit" class="pg-btn pg-btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>

            <div class="pg-modal-section">
                <h3>Şifre sıfırla</h3>
                <form method="POST" action="{{ route('admin.admins.password', $admin) }}" class="pg-form">
                    @csrf
                    <input type="hidden" name="_modal" value="admin-{{ $admin->id }}">
                    <x-admin-password-fields
                        password-id="admin-{{ $admin->id }}-password"
                        confirm-id="admin-{{ $admin->id }}-password_confirmation"
                        modal-key="admin-{{ $admin->id }}"
                    />
                    <div class="pg-form-actions">
                        <button type="submit" class="pg-btn pg-btn-primary">Şifreyi güncelle</button>
                    </div>
                </form>
            </div>

            <div class="pg-modal-section">
                <h3>Hesap işlemleri</h3>
                <div class="pg-admin-card-actions">
                    <form method="POST" action="{{ route('admin.admins.toggle', $admin) }}">
                        @csrf
                        <button type="submit" class="pg-btn pg-btn-ghost">
                            {{ $admin->is_active ? 'Pasifleştir' : 'Aktifleştir' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" onsubmit="return confirm('Admin silinsin mi?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="pg-btn pg-btn-danger">Sil</button>
                    </form>
                </div>
            </div>
        </x-admin-modal>
        @endif
    @endforeach
</x-admin-layout>
