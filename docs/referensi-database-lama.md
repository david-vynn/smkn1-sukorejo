# Referensi Struktur Database Lama (BUKAN database final)

Sumber: berkas SQL di folder `database/` (dipertahankan sebagai referensi). Dokumen ini hanya
menjelaskan fungsi tiap tabel. Rancang ulang struktur milik Anda sendiri; jangan menyalin mentah.

Catatan berkas: `schema.sql` adalah versi lengkap; `migration-siswa-admin.sql`,
`migration-spp.sql`, dan `migration-v2.sql` adalah perubahan bertahap yang dijalankan berurutan
pada database yang sudah ada. Yang saya baca adalah berkas SQL, bukan database yang berjalan di komputer Anda,
jadi struktur riil bisa berbeda.

| Tabel | Fungsi | Kolom penting |
|---|---|---|
| `users` | Semua akun: siswa, kasir, admin | nama, NISN (siswa), kelas, jurusan, email (unik), hash kata sandi, peran, status aktif |
| `spp_settings` | Pengaturan pasangan kunci-nilai | nominal SPP, tahun ajaran aktif |
| `spp_bills` | Tagihan per siswa per bulan | siswa, tahun ajaran, bulan, tahun kalender, nominal, jatuh tempo, status, status aktif; unik per (siswa, tahun ajaran, bulan) |
| `spp_payments` | Transaksi pembayaran | tagihan, siswa, nomor transaksi (unik), nominal, metode, tanggal, status, pembuat, pemverifikasi, waktu verifikasi, catatan |
| `spp_payment_logs` | Jejak audit perubahan status | pembayaran, pengubah, status lama, status baru, catatan, waktu |
| `spp_counters` | Penghitung nomor transaksi harian | tanggal, nomor terakhir |
| `login_attempts` | Pembatasan percobaan login | email, IP, waktu |

## Relasi

- Tagihan → siswa (`spp_bills.student_id` → `users.id`)
- Pembayaran → tagihan (`spp_payments.bill_id` → `spp_bills.id`) dan → siswa (`spp_payments.student_id` → `users.id`)
- Log → pembayaran (`spp_payment_logs.payment_id` → `spp_payments.id`)
- Kolom pembuat/pemverifikasi pada pembayaran dan pengubah pada log merujuk ke `users` secara logis (tanpa kunci asing di berkas SQL).

## Aturan yang ditegakkan di level database (lama)

- Email unik dan NISN unik per akun.
- Tidak boleh ada dua tagihan untuk siswa + tahun ajaran + bulan yang sama.
- Nomor transaksi unik.
- Satu tagihan hanya boleh punya satu pembayaran aktif (menunggu/berhasil): dijaga dengan kolom turunan yang unik.

## Keputusan desain yang patut Anda pikirkan ulang

- Apakah kelas/jurusan perlu tabel sendiri, atau cukup teks seperti sistem lama?
- Status "menunggak" dihitung saat tampil (tidak disimpan). Bagaimana pendekatan Anda?
- Perlukah memisahkan tabel siswa dari tabel akun login?
- Nominal tagihan disimpan per tagihan (bukan hanya di pengaturan): apa alasannya?
