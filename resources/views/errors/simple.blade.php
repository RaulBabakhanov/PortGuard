<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Hata' }} — PortGuard</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:Manrope,Segoe UI,sans-serif; background:#07111f; color:#e8eef7; }
        .box { width:min(92vw,28rem); border:1px solid rgba(148,163,184,.2); background:#0c1829; border-radius:14px; padding:1.5rem; }
        h1 { margin:0 0 .5rem; font-size:1.25rem; }
        p { margin:0; color:#8fa3bb; line-height:1.5; }
        a { display:inline-block; margin-top:1.1rem; color:#2dd4bf; text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
<div class="box">
    <h1>{{ $title ?? 'Bir sorun oluştu' }}</h1>
    <p>{{ $message ?? 'İşlem tamamlanamadı. Lütfen tekrar deneyin.' }}</p>
    <a href="{{ url('/dashboard') }}">Panele dön</a>
</div>
</body>
</html>
