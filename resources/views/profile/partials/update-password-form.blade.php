<section class="pg-section">
    <header class="pg-section-head">
        <h2>Şifre güncelle</h2>
        <p>Hesabınızı korumak için güçlü bir şifre kullanın.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="pg-form">
        @csrf
        @method('put')

        <div class="pg-field">
            <label for="update_password_current_password">Mevcut şifre</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="pg-field">
            <label for="update_password_password">Yeni şifre</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="pg-field">
            <label for="update_password_password_confirmation">Yeni şifre (tekrar)</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pg-form-actions">
            <button type="submit" class="pg-btn pg-btn-primary">Kaydet</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="pg-success"
                >Kaydedildi.</p>
            @endif
        </div>
    </form>
</section>
