<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Mengalihkan…</title>
<style>
    body { margin:0; background:#1a1a2e; display:flex; align-items:center;
           justify-content:center; min-height:100vh; }
    p { color:#a8b0c8; font-family:sans-serif; font-size:.9rem; }
</style>
</head>
<body>
<p>Mengalihkan…</p>
<script>
    // Gunakan replace() agar login tidak masuk ke browser history.
    // Sehingga tombol Back dari dashboard tidak bisa kembali ke halaman login.
    window.location.replace({{ Js::from($url) }});
</script>
</body>
</html>
