# Referensi Alur Bisnis SPP (dari backend lama)

Dokumen ini merangkum aturan yang dijalankan backend PHP lama, supaya bisa Anda jadikan
bahan belajar saat membangun backend baru. Isinya **kebutuhan dan aturan**, bukan kode.
Cara mengimplementasikannya (struktur tabel, query, session) adalah keputusan Anda.

## 1. Peran pengguna

| Peran | Halaman UI | Boleh melakukan |
|---|---|---|
| Siswa (`user`) | `pages/spp/spp.html` | Melihat tagihan sendiri, mengajukan pembayaran online, membatalkan pengajuan yang masih menunggu, melihat riwayat dan bukti. |
| Kasir (`kasir`) | `pages/spp/kasir-spp.html` | Mencari siswa, mencatat pembayaran langsung (termasuk tunai), menyetujui/menolak pengajuan online, melihat statistik harian/bulanan. |
| Admin (`admin`) | `pages/spp/admin-spp.html` | Mengatur nominal dan tahun ajaran, membuat/mengubah tagihan, laporan + ekspor CSV, mengelola akun siswa. |

Prinsip dari sistem lama: **peran dan identitas selalu ditentukan di server**, bukan dari
apa yang dikirim browser. Siswa hanya boleh melihat/mengubah data miliknya sendiri.

## 2. Autentikasi (aturan lama)

- Login memakai **email + kata sandi**; ada opsi "Ingat saya".
- Pesan error sengaja sama untuk "email tidak ada" dan "kata sandi salah" (mencegah penebakan akun).
- Pembatasan percobaan: maksimal 5 gagal per kombinasi email+IP dan 30 gagal per IP dalam 15 menit.
- Akun nonaktif tidak boleh login. Admin menonaktifkan akun, bukan menghapusnya.
- Kata sandi disimpan sebagai hash (bukan teks asli).
- Sesi: berakhir setelah 8 jam tidak aktif, atau 30 hari bila "Ingat saya".
- Setelah login, ID sesi diganti (anti session fixation). Setiap permintaan POST membawa token CSRF.
- Pendaftaran publik **dinonaktifkan**: akun siswa hanya dibuat admin. Akun admin/kasir pertama dibuat lewat terminal.
- Setelah login, pengguna diarahkan ke halaman sesuai perannya.

## 3. Konsep data SPP

- **Pengaturan:** nominal SPP per bulan (Rp1.000–Rp100.000.000) dan tahun ajaran aktif (format `2026/2027`, tahun kedua = tahun pertama + 1).
- **Tagihan:** satu per siswa per bulan per tahun ajaran (tidak boleh ganda). Bulan 7–12 memakai tahun pertama, bulan 1–6 memakai tahun kedua.
- **Status tagihan tersimpan:** belum bayar, menunggu verifikasi, lunas. **"Menunggak"** tidak disimpan: dihitung saat tampil (belum bayar dan lewat jatuh tempo).
- **Tagihan nonaktif:** bisa dinonaktifkan admin, hanya bila masih belum bayar.
- **Pembayaran:** status menunggu, berhasil, ditolak, dibatalkan. Satu tagihan hanya boleh punya **satu pembayaran aktif** (menunggu atau berhasil).
- **Nomor transaksi:** format `SPP-YYYYMMDD-NNNN`, nomor urut harian, tidak boleh duplikat.
- **Jejak audit:** setiap perubahan status pembayaran dicatat (siapa, status lama, status baru, catatan).

## 4. Alur utama

### 4.1 Admin menyiapkan tagihan
1. Admin mengisi nominal dan tahun ajaran (berlaku untuk tagihan yang dibuat sesudahnya).
2. **Generate massal:** pilih kelas (atau semua), pilih satu bulan atau seluruh tahun ajaran (Juli–Juni), nominal (kosong = pakai pengaturan), tanggal jatuh tempo 1–28 (bawaan 10). Hanya siswa aktif; tagihan yang sudah ada dilewati. Hasil: jumlah dibuat, dilewati, dan siswa terlibat.
3. **Tagihan satuan:** pilih siswa aktif, tahun ajaran, bulan, nominal, jatuh tempo. Jika sudah ada tagihan yang sama, tampilkan error pada kolom bulan.
4. **Ubah/nonaktifkan tagihan:** hanya tagihan yang masih belum bayar. Yang sedang menunggu verifikasi atau lunas tidak boleh diubah.

### 4.2 Siswa membayar (online, simulasi)
1. Siswa melihat tagihannya dan memilih "Bayar" pada tagihan belum bayar/menunggak.
2. Memilih metode: QRIS, Transfer Bank, Virtual Account, atau E-Wallet. (Belum ada payment gateway.)
3. Sistem membuat pembayaran berstatus **menunggu**; tagihan menjadi **menunggu verifikasi**. **Nominal diambil dari tagihan**, bukan dari input pengguna.
4. Siswa dapat **membatalkan** selama masih menunggu: pembayaran menjadi dibatalkan, tagihan kembali belum bayar.

### 4.3 Kasir memverifikasi
- **Setujui:** pembayaran berhasil, tanggal bayar = hari ini, tagihan lunas.
- **Tolak:** alasan wajib (maks. 255 karakter); pembayaran ditolak, tagihan kembali belum bayar. Alasan tampil ke siswa.
- Hanya pembayaran yang masih menunggu yang bisa diproses (yang sudah diproses ditolak dengan pesan konflik).

### 4.4 Kasir mencatat pembayaran langsung
1. Cari siswa (minimal 2 karakter nama atau NISN; hanya siswa aktif; maks. 20 hasil).
2. Pilih tagihan belum bayar milik siswa tersebut.
3. Nominal **harus sama persis** dengan tagihan (bayar penuh per bulan). Metode: online + **Tunai**. Catatan opsional (maks. 255).
4. Pembayaran langsung berstatus **berhasil** seketika; tagihan lunas; tampilkan bukti.

### 4.5 Bukti pembayaran
Berisi: nama sekolah, nama siswa, NIS/NISN, kelas, bulan, nominal, tanggal bayar, metode, nomor transaksi, status. Dicetak lewat tombol cetak browser.

### 4.6 Laporan admin
- Filter: periode (semua/hari ini/minggu ini/bulan ini/tahun ini), kelas, status pembayaran.
- Keluaran: daftar transaksi, total transaksi, total pemasukan (**hanya yang berhasil**), ekspor CSV.

### 4.7 Manajemen akun siswa (admin)
- Data: nama (maks. 100), NISN (tepat 10 digit), email (valid dan unik), kelas (maks. 30), jurusan (maks. 60). NISN dan email tidak boleh duplikat.
- Buat akun: sistem membuat **kata sandi sementara acak (12 karakter)** dan menampilkannya **sekali saja** kepada admin.
- Edit data, reset kata sandi (menghasilkan sandi sementara baru), aktifkan/nonaktifkan.
- Operasi ini hanya menyentuh akun berperan siswa; akun admin/kasir tidak bisa diubah dari sini.

## 5. Angka ringkasan di dashboard

- **Siswa:** total tagihan, total pembayaran berhasil, total tunggakan (belum bayar dan lewat jatuh tempo), dan status keseluruhan dengan prioritas: ada tunggakan → menunggu verifikasi → ada tagihan → lunas (atau "belum ada tagihan").
- **Kasir:** jumlah dan total transaksi berhasil hari ini dan bulan ini, jumlah menunggu verifikasi, jumlah ditolak.
- **Admin:** total siswa, siswa lunas, siswa belum lunas, pemasukan hari ini/bulan ini, total tunggakan, total transaksi, transaksi pending.

## 6. Prinsip keamanan dan integritas yang dipakai sistem lama

- Semua perubahan pembayaran dilakukan dalam **satu transaksi database** dengan penguncian baris, agar tidak terjadi pembayaran ganda saat dua permintaan bersamaan.
- Nominal, peran, dan kepemilikan data selalu divalidasi di server.
- Validasi dilakukan di server walaupun UI sudah memeriksa input.
- Respons error berbeda untuk kasus: tidak ditemukan (404), konflik status (409), input tidak valid (422), belum login (401), tidak berhak (403).

## 7. Data yang dibutuhkan tiap halaman (untuk perencanaan backend)

| Halaman | Data yang ditampilkan | Aksi yang dikirim |
|---|---|---|
| Siswa | profil siswa, pengaturan SPP, ringkasan, daftar tagihan, riwayat pembayaran | ajukan bayar, batalkan |
| Kasir | statistik, daftar menunggu verifikasi, hasil cari siswa, tagihan belum bayar siswa terpilih, data bukti | cari siswa, simpan pembayaran, setujui, tolak |
| Admin | statistik, pengaturan, kelas yang ada, daftar tagihan (filter), laporan (filter), daftar siswa | simpan pengaturan, generate, buat/ubah/nonaktifkan tagihan, ekspor CSV, tambah/edit/reset/nonaktifkan siswa |
| Login (modal) | – | email, kata sandi, ingat saya |
