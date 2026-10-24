<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title') · Ruci PRIMA</title>
<link rel="icon" href="{{ asset('favicon.svg') }}">
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f3f6f9;margin:0;color:#172b4d}
.wrap{max-width:680px;margin:auto;padding:20px}
.head{background:#0b3a5c;color:#fff;padding:20px;border-radius:16px 16px 0 0}
.head .brand{font-size:12px;letter-spacing:.08em;opacity:.85;text-transform:uppercase}
.head h1{margin:6px 0;font-size:22px}
.card{background:#fff;padding:20px;border-radius:0 0 16px 16px;box-shadow:0 3px 14px #0002}
.badge{display:inline-block;padding:5px 10px;border-radius:20px;background:#e9f2fa;margin:3px 3px 3px 0;font-size:13px}
.badge.danger{background:#ffebee;color:#b71c1c}.badge.warning{background:#fff3e0;color:#e65100}.badge.success{background:#e8f5e9;color:#1b5e20}
.score{font-size:38px;font-weight:700}
label{display:block;font-weight:600;margin-top:14px;font-size:14px}
select,textarea,input{width:100%;padding:11px;margin-top:6px;border:1px solid #ccd6e0;border-radius:8px;font:inherit;background:#fff}
button,.btn{display:block;width:100%;padding:13px;background:#c9963e;color:#fff;border:0;border-radius:10px;margin-top:18px;font-weight:700;font-size:16px;text-align:center;text-decoration:none;cursor:pointer}
.btn.navy{background:#0b3a5c}
small,.muted{color:#62748a}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}@media(max-width:520px){.grid{grid-template-columns:1fr}}
.err{background:#ffebee;color:#b71c1c;padding:10px;border-radius:8px;margin-top:12px;font-size:14px}
.hp{position:absolute;left:-9999px}
hr{border:0;border-top:1px solid #e6ebf0;margin:18px 0}
</style>
</head>
<body><div class="wrap">@yield('content')</div></body>
</html>
