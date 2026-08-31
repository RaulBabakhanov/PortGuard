<x-app-layout>
    <x-slot name="header"><h1>Ayarlar</h1></x-slot>
    <div class="pg-page">
        @if (session('status'))<div class="pg-alert pg-alert-ok">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif
        <section class="pg-section">
            <form method="POST" action="{{ route('settings.update') }}" class="pg-form">
                @csrf @method('PUT')
                <div class="pg-field">
                    <label for="default_ports">Varsayılan portlar</label>
                    <input id="default_ports" name="default_ports" value="{{ old('default_ports', $settings->default_ports) }}" required>
                </div>
                <div class="pg-field">
                    <label for="max_hosts_per_scan">Tarama başına max host</label>
                    <input id="max_hosts_per_scan" name="max_hosts_per_scan" type="number" min="1" max="256" value="{{ old('max_hosts_per_scan', $settings->max_hosts_per_scan) }}" required>
                </div>
                <label class="login-remember">
                    <input type="checkbox" name="notify_on_scan_complete" value="1" @checked(old('notify_on_scan_complete', $settings->notify_on_scan_complete))>
                    <span>Tarama bitince bildir</span>
                </label>
                <label class="login-remember">
                    <input type="checkbox" name="notify_on_cve_found" value="1" @checked(old('notify_on_cve_found', $settings->notify_on_cve_found))>
                    <span>CVE bulununca bildir</span>
                </label>
                <button class="pg-btn pg-btn-primary" type="submit">Kaydet</button>
            </form>
        </section>
    </div>
</x-app-layout>
