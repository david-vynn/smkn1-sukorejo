/* ==========================================================
   SMKN 1 SUKOREJO — SPP: PERILAKU TAMPILAN SAJA
   Tidak memanggil server dan tidak menyimpan data.

   - [data-action="print"]   : mencetak bukti pembayaran (window.print).
   - [data-scroll-to="id"]   : menggulir ke bagian halaman (mis. form bayar).
   - form[data-backend="pending"] dan tombol [data-action] lainnya:
       belum ada backend, jadi hanya menampilkan pemberitahuan di #spp-msg.

   Saat backend sudah Anda buat:
     - Beri form atribut method + action milik Anda dan hapus
       data-backend="pending" agar form terkirim secara normal.
     - Tombol [data-action] bisa Anda ganti menjadi <form> kecil
       (mis. tombol Setujui/Tolak) atau Anda tangani sendiri di JS.
   ========================================================== */
(function () {
  var NOTE = 'Aksi ini belum terhubung ke backend. Halaman ini hanya contoh tampilan.';

  function say(message) {
    var el = document.getElementById('spp-msg');
    if (!el) return;
    el.textContent = message;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.matches && form.matches('form[data-backend="pending"]')) {
      event.preventDefault();
      say(NOTE);
    }
  });

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-action]');
    if (!trigger) return;

    var action = trigger.getAttribute('data-action');
    if (action === 'print') {
      window.print();
      return;
    }

    var targetId = trigger.getAttribute('data-scroll-to');
    if (targetId) {
      var target = document.getElementById(targetId);
      if (target) target.scrollIntoView({ behavior: 'smooth' });
      return;
    }

    say(NOTE);
  });

  // Dropdown jurusan dan kelas
var jurusanSelect = document.getElementById('sw-jurusan');
var kelasSelect = document.getElementById('sw-kelas');

var daftarKelas = {
    RPL: 2,
    KI: 3,
    TEI: 3,
    TKR: 4,
    TAV: 1
};

if (jurusanSelect && kelasSelect) {
    jurusanSelect.addEventListener('change', function () {
        var jurusan = this.value;

        // Reset pilihan kelas
        kelasSelect.innerHTML = '';

        if (!jurusan || !daftarKelas[jurusan]) {
            kelasSelect.disabled = true;
            kelasSelect.add(new Option(
                'Pilih jurusan terlebih dahulu',
                ''
            ));
            return;
        }

        // Aktifkan dropdown kelas
        kelasSelect.disabled = false;
        kelasSelect.add(new Option('Pilih kelas', ''));

        // Buat pilihan kelas X sampai XII
        var tingkat = ['X', 'XI', 'XII'];

        tingkat.forEach(function (kelas) {
            for (var i = 1; i <= daftarKelas[jurusan]; i++) {
                var namaKelas = kelas + ' ' + jurusan;

                if (daftarKelas[jurusan] > 1) {
                    namaKelas += ' ' + i;
                }

                kelasSelect.add(new Option(namaKelas, namaKelas));
            }
        });
    });
}

// Menghitung total tagihan yang dipilih
var billCheckboxes = document.querySelectorAll(
    'input[name="bill_ids[]"]'
);

var totalInput = document.getElementById('k-amt');

function hitungTotal() {
    var total = 0;

    billCheckboxes.forEach(function (checkbox) {
        if (checkbox.checked) {
            total += Number(checkbox.dataset.amount);
        }
    });

    if (totalInput) {
        totalInput.value = 'Rp' + total.toLocaleString('id-ID');
    }
}

billCheckboxes.forEach(function (checkbox) {
    checkbox.addEventListener('change', hitungTotal);
});

hitungTotal();

})();
