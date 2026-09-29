/* ==========================================================
   SMKN 1 SUKOREJO — GRADUATION CHECK PAGE
   Front-end simulation only. Reads the shared session set by
   js/script.js (window.SMKN1Auth) to decide which view to show:
     - Logged out → locked card with a "Masuk" button.
     - Logged in  → full graduation dashboard, populated with
       sample data (no backend, no real student lookup).
   Reacts live to "smkn1:authchange" so logging in/out from the
   modal updates this page without a reload.
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {
  initGraduationPage();
});

function initGraduationPage() {
  var lockedSection = document.getElementById('graduation-locked');
  var dashboard = document.getElementById('graduation-dashboard');
  if (!lockedSection || !dashboard) return;

  // Sample academic record — stands in for data a real backend would
  // return once a student is authenticated.
  var SAMPLE_RECORD = {
    kelas: 'XII RPL 2',
    jurusan: 'Rekayasa Perangkat Lunak',
    tahunKelulusan: '2025/2026',
    rataRata: '89.75',
    predikat: 'Sangat Baik',
    subjects: [
      { name: 'Pemrograman Web', score: 92 },
      { name: 'Basis Data', score: 90 },
      { name: 'Internet of Things', score: 88 },
      { name: 'Matematika', score: 87 },
      { name: 'Bahasa Indonesia', score: 91 },
      { name: 'Bahasa Inggris', score: 89 }
    ],
    achievements: [
      '',
      '',
      ''
    ]
  };

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function populateDashboard(session) {
    setText('dash-name', session.name);
    setText('dash-nisn', session.nisn);
    setText('dash-kelas', SAMPLE_RECORD.kelas);
    setText('dash-jurusan', SAMPLE_RECORD.jurusan);
    setText('dash-tahun', SAMPLE_RECORD.tahunKelulusan);
    setText('dash-tahun-lulus', SAMPLE_RECORD.tahunKelulusan);
    setText('dash-rata-rata', SAMPLE_RECORD.rataRata);
    setText('dash-predikat', SAMPLE_RECORD.predikat);

    var tbody = document.getElementById('score-table-body');
    if (tbody) {
      tbody.innerHTML = '';
      SAMPLE_RECORD.subjects.forEach(function (subject) {
        var row = document.createElement('tr');

        var nameCell = document.createElement('td');
        nameCell.textContent = subject.name;

        var scoreCell = document.createElement('td');
        scoreCell.textContent = subject.score;

        row.appendChild(nameCell);
        row.appendChild(scoreCell);
        tbody.appendChild(row);
      });
    }

    var achievementList = document.getElementById('achievement-list');
    if (achievementList) {
      achievementList.innerHTML = '';
      if (SAMPLE_RECORD.achievements.length === 0) {
        var emptyItem = document.createElement('li');
        emptyItem.className = 'achievement-list__empty';
        emptyItem.textContent = 'Belum ada prestasi yang tercatat.';
        achievementList.appendChild(emptyItem);
      } else {
        SAMPLE_RECORD.achievements.forEach(function (achievement) {
          var item = document.createElement('li');
          item.textContent = achievement;
          achievementList.appendChild(item);
        });
      }
    }
  }

  function render() {
    var session = window.SMKN1Auth ? window.SMKN1Auth.getSession() : null;
    var loggedIn = !!session;

    lockedSection.classList.toggle('is-active', !loggedIn);
    dashboard.classList.toggle('is-active', loggedIn);

    if (loggedIn) populateDashboard(session);
  }

  window.addEventListener('smkn1:authchange', render);
  render();
}
