/* ==========================================================
   SMKN 1 SUKOREJO — CONTACT PAGE
   Front-end simulation only: there is no backend to send the
   message to, so submitting the form just shows a success
   toast and resets the fields.
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {
  initContactForm();
});

function initContactForm() {
  var form = document.getElementById('contact-form');
  var toast = document.getElementById('contact-toast');
  if (!form || !toast) return;

  var toastTimer = null;

  function setFieldError(input, message) {
    var errorEl = form.querySelector('[data-error-for="' + input.id + '"]');
    if (errorEl) errorEl.textContent = message;
    input.classList.add('has-error');
  }

  function clearFieldError(input) {
    var errorEl = form.querySelector('[data-error-for="' + input.id + '"]');
    if (errorEl) errorEl.textContent = '';
    input.classList.remove('has-error');
  }

  function clearAllErrors() {
    form.querySelectorAll('.form-error').forEach(function (el) {
      el.textContent = '';
    });
    form.querySelectorAll('.has-error').forEach(function (el) {
      el.classList.remove('has-error');
    });
  }

  function showToast() {
    clearTimeout(toastTimer);
    toast.classList.add('is-visible');
    toastTimer = setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3200);
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    clearAllErrors();

    var name = document.getElementById('contact-name');
    var email = document.getElementById('contact-email');
    var subject = document.getElementById('contact-subject');
    var message = document.getElementById('contact-message');
    var valid = true;

    [name, email, subject, message].forEach(function (field) {
      if (!field.value.trim()) {
        setFieldError(field, 'Kolom ini wajib diisi.');
        valid = false;
      }
    });

    if (valid) {
      // No backend — this only simulates a successful submission.
      form.reset();
      showToast();
    }
  });

  form.querySelectorAll('input, textarea').forEach(function (field) {
    field.addEventListener('input', function () {
      clearFieldError(field);
    });
  });
}
