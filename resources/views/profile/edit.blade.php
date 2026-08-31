<x-app-layout>
    <x-slot name="header">
        <h1>Profil</h1>
    </x-slot>

    <div class="pg-page pg-stack">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    </div>
</x-app-layout>
