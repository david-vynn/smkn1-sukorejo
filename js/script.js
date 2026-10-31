/* ==========================================================
   SMKN 1 SUKOREJO — MODAL LOGIN + MENU AKUN (UI SAJA)
   Hanya perilaku tampilan. TIDAK ada pemanggilan server, sesi,
   cookie, atau penyimpanan browser di file ini.

   Yang dikerjakan:
     - Buka/tutup modal login (tombol tutup, klik di luar, Escape).
     - Validasi sisi-klien sederhana (email & kata sandi wajib diisi).
     - Tombol akun di navigasi: membuka modal login, atau membuka
       dropdown akun bila <body data-auth="in"> (atribut ini nanti
       dipasang oleh PHP ketika pengguna sudah login).

   Titik sambung ke backend (nanti):
     - Beri <form id="login-form"> atribut method="post" dan action="..."
       milik Anda. Selama atribut action BELUM ada, form tidak dikirim
       ke mana pun dan modal menampilkan pemberitahuan.
   ========================================================== */

(function () {
  var NOT_CONNECTED = 'Login belum terhubung ke backend. Tampilan ini hanya contoh.';

  function isLoggedIn() {
    return document.body.getAttribute('data-auth') === 'in';
  }

  // Dibagikan ke initAuthUI agar tombol akun bisa membuka modal.
  var openAuthModal = function () {};

  document.addEventListener('DOMContentLoaded', function () {
    initAuthModal();
    initAuthUI();
  });

  /* ---------- Modal: buka/tutup dan form login ---------- */
  function initAuthModal() {
    var overlay = document.getElementById('auth-overlay');
    if (!overlay) return;

    var closeBtn = document.getElementById('auth-modal-close');
    var openTriggers = document.querySelectorAll('[data-open-auth]');
    var loginForm = document.getElementById('login-form');
    var authSuccess = document.getElementById('auth-success');
    var lastFocusedElement = null;

    function focusFirstField() {
      var field = overlay.querySelector('.auth-panel.is-active input');
      if (field) field.focus();
    }

    function resetLoginView() {
      if (loginForm) loginForm.classList.remove('is-hidden');
      if (authSuccess) {
        authSuccess.classList.remove('is-visible');
        authSuccess.hidden = true;
      }
      showStatus('');
    }

    function openModal() {
      lastFocusedElement = document.activeElement;
      clearAllErrors();
      resetLoginView();
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      focusFirstField();
    }

    function closeModal() {
      if (!overlay.classList.contains('is-open')) return;
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
        lastFocusedElement.focus();
      }
    }

    function isOpen() {
      return overlay.classList.contains('is-open');
    }

    openAuthModal = openModal;

    openTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', openModal);
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen()) closeModal();
    });

    /* Helper validasi */
    function setFieldError(input, message) {
      var errorEl = overlay.querySelector('[data-error-for="' + input.id + '"]');
      if (errorEl) errorEl.textContent = message;
      input.classList.add('has-error');
    }

    function clearFieldError(input) {
      var errorEl = overlay.querySelector('[data-error-for="' + input.id + '"]');
      if (errorEl) errorEl.textContent = '';
      input.classList.remove('has-error');
    }

    function clearAllErrors() {
      overlay.querySelectorAll('.form-error').forEach(function (el) { el.textContent = ''; });
      overlay.querySelectorAll('.has-error').forEach(function (el) { el.classList.remove('has-error'); });
    }

    function requireValue(input, message) {
      if (!input.value.trim()) {
        setFieldError(input, message);
        return false;
      }
      return true;
    }

    /* Pemberitahuan status di dalam modal (dibuat sekali bila belum ada) */
    function showStatus(message) {
      if (!loginForm) return;
      var el = loginForm.querySelector('.auth-status');
      if (!el) {
        if (!message) return;
        el = document.createElement('p');
        el.className = 'form-help auth-status';
        el.setAttribute('role', 'status');
        loginForm.appendChild(el);
      }
      el.textContent = message;
      el.hidden = !message;
    }

    if (!loginForm) return;

    loginForm.addEventListener('submit', function (event) {
      clearAllErrors();
      showStatus('');
      var email = document.getElementById('login-email');
      var pass = document.getElementById('login-password');
      var valid = requireValue(email, 'Email wajib diisi.');
      if (!requireValue(pass, 'Kata sandi wajib diisi.')) valid = false;

      if (!valid) { event.preventDefault(); return; }

      // Belum ada action → backend belum dibuat; jangan kirim ke mana pun.
      if (!loginForm.hasAttribute('action')) {
        event.preventDefault();
        showStatus(NOT_CONNECTED);
      }
      // Bila action sudah Anda isi, form dikirim secara normal.
    });

    // index.html?login=1 membuka modal login otomatis.
    if (/[?&]login=1/.test(window.location.search)) openModal();

    // Hapus pesan error begitu kolom diedit.
    loginForm.querySelectorAll('input').forEach(function (input) {
      input.addEventListener('input', function () { clearFieldError(input); });
    });
  }

  /* ---------- Navigasi: tombol akun + dropdown ---------- */
  function initAuthUI() {
    var accounts = document.querySelectorAll('.header-account');
    if (!accounts.length) return;

    document.querySelectorAll('.user-chip__caret').forEach(function (caret) {
      caret.hidden = !isLoggedIn();
    });

    document.querySelectorAll('.user-chip__trigger').forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        event.stopPropagation();

        if (!isLoggedIn()) {
          openAuthModal();
          return;
        }

        var menu = trigger.parentElement.querySelector('.user-chip__menu');
        var wasOpen = trigger.getAttribute('aria-expanded') === 'true';
        closeAllUserMenus();
        if (!wasOpen && menu) {
          trigger.setAttribute('aria-expanded', 'true');
          menu.classList.add('is-open');
        }
      });
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.header-account')) closeAllUserMenus();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAllUserMenus();
    });
  }

  function closeAllUserMenus() {
    document.querySelectorAll('.user-chip__trigger').forEach(function (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    });
    document.querySelectorAll('.user-chip__menu').forEach(function (menu) {
      menu.classList.remove('is-open');
    });
  }
})();
