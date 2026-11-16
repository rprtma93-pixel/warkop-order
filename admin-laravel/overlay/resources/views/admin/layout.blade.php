<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Admin Warkop')</title>
@yield('head')
<style>
  :root { --brown:#6f4e37; --cream:#faf5ef; --accent:#c8873a; --ink:#2b2118; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:system-ui,sans-serif; background:var(--cream); color:var(--ink); }
  header { background:var(--brown); color:#fff; padding:12px 16px; display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; align-items:center; }
  header h1 { margin:0; font-size:1.15rem; }
  header small { opacity:.7; font-weight:400; }
  nav { display:flex; gap:8px; align-items:center; }
  nav a { color:#fff; text-decoration:none; padding:6px 12px; border-radius:8px; background:rgba(255,255,255,.15); font-size:.9rem; }
  main { max-width:1000px; margin:0 auto; padding:16px; }
  .box { background:#fff; border-radius:12px; padding:14px; margin:12px 0; box-shadow:0 1px 3px rgba(0,0,0,.08); overflow-x:auto; }
  table { width:100%; border-collapse:collapse; }
  th, td { text-align:left; padding:8px 6px; border-bottom:1px solid #eee; font-size:.9rem; vertical-align:top; }
  button, .btn { cursor:pointer; border:0; border-radius:8px; padding:8px 14px; background:var(--brown); color:#fff; font-size:.9rem; text-decoration:none; display:inline-block; }
  button.sm, .btn.sm { padding:4px 10px; font-size:.8rem; }
  .danger { background:#b3392f; }
  .ghost { background:#e5dccf; color:var(--ink); }
  input, select { padding:8px; border:1px solid #ddd; border-radius:8px; font:inherit; }
  .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:8px; }
  .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
  .stat { background:#fff; border-radius:12px; padding:14px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
  .stat b { display:block; font-size:1.4rem; color:var(--accent); }
  .badge { padding:2px 8px; border-radius:99px; color:#fff; font-size:.75rem; background:#888; }
  .baru{background:#d9534f}.diproses{background:#f0ad4e}.siap{background:#5bc0de}.selesai{background:#5cb85c}.batal{background:#777}
  .flash { padding:10px 14px; border-radius:8px; margin:12px 0; }
  .flash.ok { background:#e6f4e6; } .flash.err { background:#fde8e8; }
  form.inline { display:inline; margin:0; }
</style>
</head>
<body>
@if(session('admin'))
<header>
  <h1>⚙️ Admin Warkop <small>Laravel</small></h1>
  <nav>
    <a href="/admin">Pesanan</a>
    <a href="/admin/menu">Menu</a>
    <form class="inline" method="POST" action="/logout">@csrf<button class="sm ghost">Keluar</button></form>
  </nav>
</header>
@endif
<main>
  @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
  @yield('content')
</main>
</body>
</html>
