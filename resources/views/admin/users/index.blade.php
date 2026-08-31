<x-admin-layout>
    <x-slot name="header"><h1>Kullanıcılar</h1></x-slot>

    <div
        class="pg-page pg-page-wide"
        x-data
        x-init="@if (!empty($openModal)) $dispatch('open-modal', '{{ $openModal }}') @endif"
    >
        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif

        <div class="pg-toolbar">
            <button type="button" class="pg-btn pg-btn-primary" @click="$dispatch('open-modal', 'user-create')">
                Yeni kullanıcı
            </button>
        </div>

        <section class="pg-section">
            <form method="GET" action="{{ route('admin.users.index') }}" class="pg-form pg-filter-form">
                <div class="pg-filter-grid">
                    <div class="pg-field">
                        <label for="q">Ara</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Ad veya e-posta">
                    </div>
                    <div class="pg-field">
                        <label for="department_id">Birim</label>
                        <select id="department_id" name="department_id" class="pg-select">
                            <option value="">Tümü</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected($departmentId === $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Filtrele</button>
                    <a href="{{ route('admin.users.index') }}" class="pg-btn pg-btn-ghost">Temizle</a>
                </div>
            </form>
        </section>

        <section class="pg-section">
            <div class="pg-table-wrap">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>E-posta</th>
                            <th>Birim</th>
                            <th>Tarama</th>
                            <th>CVE</th>
                            <th>Kayıt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->department?->name ?? '—' }}</td>
                            <td>{{ $user->scans_count }}</td>
                            <td>{{ $user->cve_findings_count }}</td>
                            <td>{{ $user->created_at->format('d.m.Y') }}</td>
                            <td>
                                <a
                                    href="{{ route('admin.users.index', array_merge(request()->query(), ['open' => 'user-'.$user->id])) }}"
                                    class="pg-btn pg-btn-ghost"
                                >
                                    Detay / düzenle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pg-empty">Kullanıcı bulunamadı.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pg-pagination">{{ $users->links() }}</div>
        </section>
    </div>

    <x-admin-modal name="user-create" title="Yeni kullanıcı" subtitle="Panel üyesi oluşturur." wide>
        <form method="POST" action="{{ route('admin.users.store') }}" class="pg-form">
            @csrf
            <input type="hidden" name="_modal" value="user-create">
            <div class="pg-form-grid">
                <div class="pg-field">
                    <label for="create_user_name">Ad</label>
                    <input id="create_user_name" name="name" value="{{ old('name') }}" required>
                    @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="pg-field">
                    <label for="create_user_email">E-posta</label>
                    <input id="create_user_email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                </div>
                <x-admin-password-fields
                    password-id="create_user_password"
                    confirm-id="create_user_password_confirmation"
                    modal-key="user-create"
                />
                <div class="pg-field pg-field-span">
                    <label for="create_user_department_id">Birim</label>
                    <select id="create_user_department_id" name="department_id" class="pg-select">
                        <option value="">—</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="pg-form-actions">
                <button type="submit" class="pg-btn pg-btn-primary">Oluştur</button>
                <button type="button" class="pg-btn pg-btn-ghost" @click="$dispatch('close-modal', 'user-create')">İptal</button>
            </div>
        </form>
    </x-admin-modal>

    @foreach ($users as $user)
        @if ($openModal === 'user-'.$user->id)
        <x-admin-modal
            name="user-{{ $user->id }}"
            :title="$user->name"
            :subtitle="$user->email"
            wide
            :show="true"
        >
            <div class="pg-stat-grid pg-stat-grid-sm">
                <div class="pg-stat"><strong>{{ $user->scans_count }}</strong><span>Tarama</span></div>
                <div class="pg-stat"><strong>{{ $user->targets_count }}</strong><span>Hedef</span></div>
                <div class="pg-stat"><strong>{{ $user->cve_findings_count }}</strong><span>CVE</span></div>
                <div class="pg-stat"><strong>{{ $user->activity_logs_count }}</strong><span>Log</span></div>
            </div>

            <div class="pg-modal-meta">
                <div>Kayıt: <strong>{{ $user->created_at->format('d.m.Y H:i') }}</strong></div>
                <div>Birim: <strong>{{ $user->department?->name ?? '—' }}</strong></div>
            </div>

            <div class="pg-toolbar" style="margin-top: 0.75rem;">
                <a href="{{ route('admin.scans.index', ['user_id' => $user->id]) }}" class="pg-btn pg-btn-ghost">Taramalar</a>
                <a href="{{ route('admin.logs.index', ['user_id' => $user->id]) }}" class="pg-btn pg-btn-ghost">Loglar</a>
            </div>

            <div class="pg-modal-section">
                <h3>Bilgileri düzenle</h3>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="pg-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal" value="user-{{ $user->id }}">
                    <div class="pg-form-grid">
                        <div class="pg-field">
                            <label for="user_name_{{ $user->id }}">Ad</label>
                            <input id="user_name_{{ $user->id }}" name="name" value="{{ old('_modal') === 'user-'.$user->id ? old('name') : $user->name }}" required>
                            @if (old('_modal') === 'user-'.$user->id)
                                @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                        <div class="pg-field">
                            <label for="user_email_{{ $user->id }}">E-posta</label>
                            <input id="user_email_{{ $user->id }}" type="email" name="email" value="{{ old('_modal') === 'user-'.$user->id ? old('email') : $user->email }}" required>
                            @if (old('_modal') === 'user-'.$user->id)
                                @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                        <div class="pg-field pg-field-span">
                            <label for="user_department_{{ $user->id }}">Birim</label>
                            <select id="user_department_{{ $user->id }}" name="department_id" class="pg-select">
                                <option value="">—</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}" @selected((old('_modal') === 'user-'.$user->id ? old('department_id') : $user->department_id) == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="pg-form-actions">
                        <button type="submit" class="pg-btn pg-btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>

            <div class="pg-modal-section">
                <h3>Şifre sıfırla</h3>
                <form method="POST" action="{{ route('admin.users.password', $user) }}" class="pg-form">
                    @csrf
                    <input type="hidden" name="_modal" value="user-{{ $user->id }}">
                    <x-admin-password-fields
                        password-id="user-{{ $user->id }}-password"
                        confirm-id="user-{{ $user->id }}-password_confirmation"
                        password-label="Yeni şifre"
                        confirm-label="Yeni şifre (tekrar)"
                        modal-key="user-{{ $user->id }}"
                    />
                    <div class="pg-form-actions">
                        <button type="submit" class="pg-btn pg-btn-primary">Şifreyi güncelle</button>
                    </div>
                </form>
            </div>

            <div class="pg-modal-section">
                <h3>Tehlikeli alan</h3>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ $user->email }} silinecek. İlişkili taramalar da silinir. Emin misiniz?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="pg-btn pg-btn-danger">Kullanıcıyı sil</button>
                </form>
            </div>
        </x-admin-modal>
        @endif
    @endforeach
</x-admin-layout>
