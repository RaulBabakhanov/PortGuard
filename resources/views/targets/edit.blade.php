<x-app-layout>
    <x-slot name="header"><h1>Hedef Düzenle</h1></x-slot>
    <div class="pg-page">
        @if ($errors->any())<div class="pg-alert pg-alert-danger">{{ $errors->first() }}</div>@endif
        <section class="pg-section">
            <form method="POST" action="{{ route('targets.update', $target) }}" class="pg-form">
                @csrf
                @method('PUT')
                <div class="pg-field">
                    <label for="name">Ad</label>
                    <input id="name" name="name" required value="{{ old('name', $target->name) }}">
                </div>
                <div class="pg-field">
                    <label for="type">Tür</label>
                    <select id="type" name="type" class="pg-select" required>
                        <option value="ip" @selected(old('type', $target->type)==='ip')>Tek IP</option>
                        <option value="cidr" @selected(old('type', $target->type)==='cidr')>CIDR</option>
                        <option value="range" @selected(old('type', $target->type)==='range')>IP Aralığı</option>
                    </select>
                </div>
                <div class="pg-field"><label for="ip">IP</label><input id="ip" name="ip" value="{{ old('ip', $target->ip) }}"></div>
                <div class="pg-field"><label for="cidr">CIDR</label><input id="cidr" name="cidr" value="{{ old('cidr', $target->cidr) }}"></div>
                <div class="pg-grid-2">
                    <div class="pg-field"><label for="start_ip">Başlangıç</label><input id="start_ip" name="start_ip" value="{{ old('start_ip', $target->start_ip) }}"></div>
                    <div class="pg-field"><label for="end_ip">Bitiş</label><input id="end_ip" name="end_ip" value="{{ old('end_ip', $target->end_ip) }}"></div>
                </div>
                <div class="pg-field"><label for="ports">Portlar</label><input id="ports" name="ports" value="{{ old('ports', $target->ports) }}"></div>
                <div class="pg-field"><label for="notes">Not</label><textarea id="notes" name="notes" class="pg-textarea" rows="3">{{ old('notes', $target->notes) }}</textarea></div>
                <div class="pg-form-actions">
                    <button class="pg-btn pg-btn-primary" type="submit">Güncelle</button>
                    <a href="{{ route('targets.index') }}" class="pg-btn pg-btn-ghost">Geri</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
