<section class="pg-section pg-section-danger">
    <header class="pg-section-head">
        <h2>Hesabı sil</h2>
        <p>Hesabınız silindiğinde tarama geçmişi ve kayıtlarınız kalıcı olarak kaldırılır.</p>
    </header>

    <button
        type="button"
        class="pg-btn pg-btn-danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hesabı sil</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="pg-modal-body">
            @csrf
            @method('delete')

            <h2>Hesabınızı silmek istediğinize emin misiniz?</h2>
            <p>Bu işlem geri alınamaz. Onaylamak için şifrenizi girin.</p>

            <div class="pg-field">
                <label for="password" class="sr-only">Şifre</label>
                <input id="password" name="password" type="password" placeholder="Şifreniz">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="pg-form-actions pg-form-actions-end">
                <button type="button" class="pg-btn pg-btn-ghost" x-on:click="$dispatch('close')">Vazgeç</button>
                <button type="submit" class="pg-btn pg-btn-danger">Evet, sil</button>
            </div>
        </form>
    </x-modal>
</section>
