<section class="pg-section">
    <header class="pg-section-head">
        <h2>Profil bilgileri</h2>
        <p>Adınızı ve e-posta adresinizi güncelleyin.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="pg-form">
        @csrf
        @method('patch')

        <div class="pg-field">
            <label for="name">Ad Soyad</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="pg-field">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="pg-help">
                    E-posta adresiniz doğrulanmamış.
                    <button form="send-verification" class="pg-link-btn">Doğrulama maili gönder</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="pg-success">Yeni doğrulama bağlantısı gönderildi.</p>
                @endif
            @endif
        </div>

        <div class="pg-form-actions">
            <button type="submit" class="pg-btn pg-btn-primary">Kaydet</button>

            @if (session('status') === 'profile-updated')
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
