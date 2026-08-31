<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Tarama #{{ $scan->id }} — PortGuard</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 10px 0 4px; }
        .meta { color: #444; margin-bottom: 14px; }
        .stats { width: 100%; margin: 10px 0 16px; border-collapse: collapse; }
        .stats td { border: 1px solid #ddd; padding: 8px; text-align: center; width: 20%; }
        .stats b { display: block; font-size: 16px; }
        .badge { display: inline-block; border: 1px solid #999; border-radius: 3px; padding: 2px 6px; margin: 2px; font-size: 10px; }
        .cve { margin: 6px 0; padding: 6px 8px; border: 1px solid #e5e5e5; }
        .sev { font-weight: bold; color: #a00; }
        .muted { color: #666; }
        .footer { margin-top: 24px; font-size: 10px; color: #666; }
        pre { font-size: 9px; white-space: pre-wrap; background: #f7f7f7; padding: 8px; border: 1px solid #eee; }
    </style>
</head>
<body>
    <h1>PortGuard — Tarama #{{ $scan->id }}</h1>
    <div class="meta">
        <strong>{{ $scan->name }}</strong><br>
        Kullanıcı: {{ $scan->user?->email ?? '—' }} · Hedef: {{ $scan->summaryLabel() }} · Portlar: {{ $scan->ports }} · Durum: {{ $scan->status }}<br>
        Tarih: {{ $scan->created_at?->format('d.m.Y H:i') }}
    </div>

    <table class="stats">
        <tr>
            <td><b>{{ $stats['total'] }}</b>Toplam IP</td>
            <td><b>{{ $stats['active'] }}</b>Aktif Host</td>
            <td><b>{{ $stats['apache'] }}</b>Apache</td>
            <td><b>{{ $stats['mariadb'] }}</b>MariaDB</td>
            <td><b>{{ $stats['openssh'] }}</b>OpenSSH</td>
        </tr>
    </table>

    @forelse ($onlineHosts as $host)
        @php
            $badges = $hostBadges[$host->id] ?? [];
            $cvesByService = $hostCves[$host->id] ?? collect();
        @endphp

        <h2>Host: {{ $host->ip }} (ONLINE)</h2>
        <div>
            @forelse ($badges as $badge)
                <span class="badge">{{ $badge }}</span>
            @empty
                <span class="muted">Servis bulunamadı</span>
            @endforelse
        </div>

        @if ($host->services->isNotEmpty())
            <p>
                @foreach ($host->services as $svc)
                    {{ $svc->port }}/{{ $svc->protocol }} {{ $svc->name }}
                    @if ($svc->product) ({{ $svc->product }}{{ $svc->version ? ' '.$svc->version : '' }})@endif
                    @if (! $loop->last) · @endif
                @endforeach
            </p>
        @endif

        <h3>CVE Sonuçları</h3>
        @if ($cvesByService->isEmpty())
            <p class="muted">Bulgu yok.</p>
        @else
            @foreach ($cvesByService as $serviceName => $findings)
                <h3>{{ $serviceName }}</h3>
                @foreach ($findings as $cve)
                    <div class="cve">
                        <strong>{{ $cve->cve_id }}</strong>
                        @if ($cve->severity)
                            <span class="sev">{{ strtoupper($cve->severity) }}</span>
                        @endif
                        <div>{{ $cve->description }}</div>
                    </div>
                @endforeach
            @endforeach
        @endif
    @empty
        <p class="muted">Aktif host bulunamadı.</p>
    @endforelse

    <div class="footer">PortGuard yönetim raporu · {{ now()->format('d.m.Y H:i') }}</div>
</body>
</html>
