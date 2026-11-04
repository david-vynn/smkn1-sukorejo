# SMKN 1 Sukorejo — Frontend Starter

Website profil sekolah dan antarmuka (UI) sistem SPP. **Hanya frontend:** HTML, CSS, JavaScript vanilla.
Backend (PHP Native + MySQL) sengaja dikosongkan untuk dibangun ulang dari nol.

## Cara membuka

Tidak butuh XAMPP, Apache, PHP, atau database. Buka `index.html` langsung di browser,
atau pakai server statis apa saja (mis. ekstensi Live Server di VS Code).
Font memakai Google Fonts, jadi tampilan terbaik saat ada internet.

## Struktur

```text
.
├── index.html, profil-sekolah.html, program-keahlian.html, ekstrakurikuler.html,
│   staf-pengajar.html, agenda.html, prestasi.html, contact.html   ← website profil
├── pages/spp/
│   ├── spp.html          ← dashboard siswa
│   ├── kasir-spp.php    ← dashboard kasir
│   └── admin-spp.html    ← dashboard admin (tagihan, laporan, data siswa)
├── css/   variables.css, reset.css, style.css, spp.css
├── js/    main.js, contact.js, script.js (modal login + menu akun), spp-ui.js
├── assets/images/
├── database/   berkas SQL lama (REFERENSI SAJA, jangan dijalankan sebagai database final)
└── docs/       referensi alur bisnis SPP dan struktur database lama
```

## Status halaman SPP

Semua data di `pages/spp/*.html` adalah **placeholder** (diberi label "Tampilan contoh").
Tidak ada backend tiruan: form dan tombol hanya menampilkan pemberitahuan bahwa belum terhubung.

## Titik sambung ke backend (saat Anda siap)

| Kebutuhan                               | Cara                                                                                                               |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------ | ----- | ------------------------------------------------------------------ |
| Kirim form login                        | Tambahkan `method="post"` dan `action="..."` pada `<form id="login-form">`. Tanpa `action`, form tidak dikirim.    |
| Tampilkan kondisi sudah login           | PHP memasang `<body data-auth="in">`; tombol akun lalu membuka dropdown, bukan modal login.                        |
| Kirim form SPP                          | Pada form `data-backend="pending"`: isi `action` dan hapus atribut itu. Semua kolom sudah punya `name`.            |
| Isi tabel dari database                 | Ganti baris `<tr>` contoh dengan perulangan PHP. Setiap `<td>` sudah punya `data-label` (dipakai CSS tampilan HP). |
| Tombol aksi (Setujui, Tolak, Edit, ...) | Bertanda `data-action="..."`; ganti dengan form kecil atau tangani di JS milik Anda.                               |
| Guard halaman per peran                 | `<main data-spp-role="user                                                                                         | kasir | admin">` menandai peran halaman. Penjagaan aksesnya tugas backend. |

## Catatan

- Nama kelas CSS `spp-card` dan `spp-grid` merupakan nama warisan, tetapi masih dipakai oleh halaman SPP. Pertahankan bersama `info-list`, `score-table`, `stat-block`, dan `table-wrap`.
- Aset yang dirujuk tetapi belum ada: lihat laporan audit (favicon, video beranda, gambar ekskul di beranda).
- Alur bisnis dan struktur data lama: lihat folder `docs/`.
