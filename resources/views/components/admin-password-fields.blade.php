@props([
    'passwordName' => 'password',
    'confirmName' => 'password_confirmation',
    'passwordLabel' => 'Şifre',
    'confirmLabel' => 'Şifre (tekrar)',
    'passwordId' => 'password',
    'confirmId' => 'password_confirmation',
    'required' => true,
    'showRules' => true,
    'modalKey' => null,
])

@php
    $showErrors = $modalKey === null || old('_modal') === $modalKey;
@endphp

<div
    class="pg-password-block pg-field-span"
    x-data="passwordFields('{{ $passwordId }}', '{{ $confirmId }}')"
>
    <div class="pg-password-grid">
        <div class="pg-field">
            <label for="{{ $passwordId }}">{{ $passwordLabel }}</label>
            <div class="pg-input-action">
                <input
                    id="{{ $passwordId }}"
                    name="{{ $passwordName }}"
                    :type="visible ? 'text' : 'password'"
                    @if ($required) required @endif
                    autocomplete="new-password"
                    minlength="8"
                >
                <div class="pg-input-action-btns">
                    <button type="button" class="pg-btn pg-btn-ghost pg-btn-sm" @click="generate()">Oluştur</button>
                    <button type="button" class="pg-btn pg-btn-ghost pg-btn-sm" @click="toggle()" x-text="visible ? 'Gizle' : 'Göster'"></button>
                </div>
            </div>
            @if ($showErrors)
                @error($passwordName)
                    <p class="pg-field-error">{{ $message }}</p>
                @enderror
            @endif
        </div>

        <div class="pg-field">
            <label for="{{ $confirmId }}">{{ $confirmLabel }}</label>
            <input
                id="{{ $confirmId }}"
                name="{{ $confirmName }}"
                :type="visible ? 'text' : 'password'"
                @if ($required) required @endif
                autocomplete="new-password"
                minlength="8"
            >
            @if ($showErrors)
                @error($confirmName)
                    <p class="pg-field-error">{{ $message }}</p>
                @enderror
            @endif
        </div>
    </div>

    @if ($showRules)
        <div class="pg-help-box pg-help-box-compact">
            <strong>Şifre kuralları:</strong>
            en az 8 karakter · büyük/küçük harf · rakam · özel karakter (! @ # $ % &amp; * vb.)
        </div>
    @endif
</div>
