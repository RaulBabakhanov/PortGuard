<x-admin-layout>
    <x-slot name="header"><h1>Yeni kullanıcı</h1></x-slot>

    <div class="pg-page">
        <section class="pg-section">
            <form method="POST" action="{{ route('admin.users.store') }}" class="pg-form">
                @csrf
                <div class="pg-form-grid">
                    <div class="pg-field">
                        <label for="name">Ad</label>
                        <input id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<p class="pg-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pg-field">
                        <label for="email">E-posta</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<p class="pg-field-error">{{ $message }}</p>@enderror
                    </div>

                    <x-admin-password-fields />

                    <div class="pg-field pg-field-span">
                        <label for="department_id">Birim</label>
                        <select id="department_id" name="department_id" class="pg-select">
                            <option value="">—</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<p class="pg-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pg-form-actions">
                    <button type="submit" class="pg-btn pg-btn-primary">Oluştur</button>
                    <a href="{{ route('admin.users.index') }}" class="pg-btn pg-btn-ghost">İptal</a>
                </div>
            </form>
        </section>
    </div>
</x-admin-layout>
