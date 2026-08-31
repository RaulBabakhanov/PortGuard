<x-app-layout>
    <x-slot name="header"><h1>Yeni Tarama</h1></x-slot>

    <div class="pg-page pg-page-wide">
        @if (session('status'))
            <div class="pg-alert pg-alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>
        @endif
        @if (($nmap['mode'] ?? '') === 'nmap')
            <div class="pg-alert pg-alert-ok">
                <span class="pg-engine-chip pg-engine-nmap">Nmap</span>
                Hazır{{ !empty($nmap['version']) ? ' · v'.$nmap['version'] : '' }}
            </div>
        @else
            <div class="pg-alert pg-alert-warn">
                <span class="pg-engine-chip pg-engine-php">PHP</span>
                {{ $nmap['reason'] ?? 'PHP soket taraması kullanılacak.' }}
                Natro shared’de 192.168.x hedefler çalışmaz; test için <code>scanme.nmap.org</code> kullanın.
            </div>
        @endif

        <section class="pg-section">
            <header class="pg-section-head">
                <h2>Hedef bilgileri</h2>
                <p>Tek IP, CIDR veya aralık. Hosting’den yalnızca internetten erişilen hedefler taranabilir.</p>
            </header>

            <form method="POST" action="{{ route('scans.store') }}" class="pg-form">
                @csrf

                <div class="pg-form-grid">
                    <div class="pg-field pg-field-span">
                        <label for="name">Tarama adı</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Örn: Public test taraması">
                    </div>

                    @if ($targets->isNotEmpty())
                        <div class="pg-field pg-field-span">
                            <label for="target_id">Kayıtlı hedef (opsiyonel)</label>
                            <select id="target_id" name="target_id" class="pg-select">
                                <option value="">— Manuel gir —</option>
                                @foreach ($targets as $target)
                                    <option value="{{ $target->id }}" @selected(old('target_id', $prefill?->id) == $target->id)>
                                        {{ $target->name }} ({{ $target->label() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="pg-field">
                        <label for="ip">Tek IP / host</label>
                        <input id="ip" name="ip" type="text" value="{{ old('ip', $prefill?->ip) }}" placeholder="scanme.nmap.org veya 8.8.8.8">
                        @error('ip')<div class="pg-alert pg-alert-danger" style="margin-top:.45rem;">{{ $message }}</div>@enderror
                    </div>

                    <div class="pg-field">
                        <label for="cidr">CIDR</label>
                        <input id="cidr" name="cidr" type="text" value="{{ old('cidr', $prefill?->cidr) }}" placeholder="192.168.32.0/24 (yalnızca Nmap+LAN)">
                    </div>

                    <div class="pg-field">
                        <label for="start_ip">Başlangıç IP</label>
                        <input id="start_ip" name="start_ip" type="text" value="{{ old('start_ip', $prefill?->start_ip) }}">
                    </div>
                    <div class="pg-field">
                        <label for="end_ip">Bitiş IP</label>
                        <input id="end_ip" name="end_ip" type="text" value="{{ old('end_ip', $prefill?->end_ip) }}">
                    </div>

                    <div class="pg-field pg-field-span">
                        <label for="ports">Portlar</label>
                        <input id="ports" name="ports" type="text" value="{{ old('ports', $prefill?->ports ?: $defaultPorts) }}">
                    </div>
                </div>

                <div class="pg-form-actions">
                    <button type="submit" class="pg-btn pg-btn-primary pg-btn-scan">Taramayı Başlat</button>
                    <a href="{{ route('scans.index') }}" class="pg-btn pg-btn-ghost">Geçmiş</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
