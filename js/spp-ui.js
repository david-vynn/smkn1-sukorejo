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

if (jurusanSelect && kelasSelect) {
    jurusanSelect.addEventListener('change', function () {
        var majorId = this.value;

        kelasSelect.innerHTML = '';
        kelasSelect.disabled = true;

        if (!majorId) {
            kelasSelect.add(new Option(
                'Pilih jurusan terlebih dahulu',
                ''
            ));
            return;
        }

        kelasSelect.add(new Option('Memuat kelas...', ''));

        fetch('/smkn1-sukorejo/spp/get-classes.php?major_id=' + encodeURIComponent(majorId))            
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Gagal mengambil data kelas');
                }

                return response.json();
            })
            .then(function (classes) {
                kelasSelect.innerHTML = '';
                kelasSelect.add(new Option('Pilih kelas', ''));

                console.log('Dropdown kelas menerima:', classes);
                console.log('Elemen dropdown:', kelasSelect);

                classes.forEach(function (kelas) {
                    kelasSelect.add(
                        new Option(kelas.class_name, kelas.id)
                    );
                });

                kelasSelect.disabled = classes.length === 0;

                console.log('Jumlah opsi dropdown:', kelasSelect.options.length);

                if (classes.length === 0) {
                    kelasSelect.innerHTML = '';
                    kelasSelect.add(new Option('Kelas tidak ditemukan', ''));
                }
            })
            .catch(function (error) {
                console.error(error);

                kelasSelect.innerHTML = '';
                kelasSelect.add(new Option('Gagal memuat kelas', ''));
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
