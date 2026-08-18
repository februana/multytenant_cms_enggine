(function () {
  'use strict';

  var root = document.querySelector('[data-pawiwahan-root]');
  if (!root) return;

  var welcomeModal = document.getElementById('welcomeModal');
  var myAudio = document.getElementById('my_audio');

  function pad(value) { return String(Math.max(0, value)).padStart(2, '0'); }

  function initCountdown() {
    var target = document.body.getAttribute('data-pawiwahan-countdown-target') || '';
    var list = document.getElementById('hitungmundur');
    if (!target || !list) return;

    // Keep the original jQuery countdown lifecycle when its local source plugin is available.
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.countdown === 'function') {
      window.jQuery('#hitungmundur').countdown({ date: target, offset: 0 }, function () {
        var notice = document.getElementById('pawiwahan-countdown-notice');
        if (notice) notice.textContent = 'Acara pernikahan telah selesai. Terima kasih atas doa dan kehadiran Anda.';
      });
      return;
    }

    function update() {
      var distance = new Date(target).getTime() - Date.now();
      if (!Number.isFinite(distance) || distance < 0) distance = 0;
      var total = Math.floor(distance / 1000);
      var days = list.querySelector('.days');
      var hours = list.querySelector('.hours');
      var minutes = list.querySelector('.minutes');
      var seconds = list.querySelector('.seconds');
      if (days) days.textContent = pad(Math.floor(total / 86400));
      if (hours) hours.textContent = pad(Math.floor((total % 86400) / 3600));
      if (minutes) minutes.textContent = pad(Math.floor((total % 3600) / 60));
      if (seconds) seconds.textContent = pad(total % 60);
    }
    update();
    window.setInterval(update, 1000);
  }

  function closeWelcomeModal() {
    if (welcomeModal) welcomeModal.style.display = 'none';
    if (myAudio && myAudio.paused) {
      var attempt = myAudio.play();
      if (attempt && typeof attempt.catch === 'function') attempt.catch(function () {});
    }
  }
  window.closeWelcomeModal = closeWelcomeModal;

  function toggleAudio(input) {
    if (!myAudio || !input) return;
    var action = input.checked ? myAudio.pause() : myAudio.play();
    if (action && typeof action.catch === 'function') action.catch(function () {});
  }
  window.toggleAudio = toggleAudio;

  function copyToClipboard(element) {
    if (!element) return;
    var value = element.getAttribute('data-copy') || element.textContent || '';
    var done = function () {
      var notice = document.getElementById('popupCopy2');
      if (notice) { notice.textContent = 'Berhasil disalin'; notice.classList.add('show'); }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(value).then(done).catch(function () {});
    else {
      var textarea = document.createElement('textarea');
      textarea.value = value;
      document.body.appendChild(textarea);
      textarea.select();
      try { document.execCommand('copy'); done(); } catch (error) {}
      textarea.remove();
    }
  }
  window.copyToClipboard = copyToClipboard;

  function initCopyButtons() {
    root.querySelectorAll('[data-copy]').forEach(function (button) {
      button.addEventListener('click', function () { copyToClipboard(button); });
    });
  }

  function initScrollTop() {
    var button = document.getElementById('button');
    if (!button) return;
    var update = function () { button.classList.toggle('show', window.scrollY > 500); };
    window.addEventListener('scroll', update, { passive: true });
    button.addEventListener('click', function (event) {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initRsvp() {
    var form = document.getElementById('pawiwahan-rsvp-form');
    var message = document.getElementById('pawiwahan-form-message');
    if (!form || !message) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      message.textContent = 'Mengirim...';
      fetch('save.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          message.textContent = data.message || (data.success ? 'Terima kasih.' : 'Gagal mengirim.');
          if (data.success) form.reset();
        })
        .catch(function () { message.textContent = 'Gagal mengirim. Silakan coba lagi.'; });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (welcomeModal) welcomeModal.style.display = 'block';
    initCountdown();
    initCopyButtons();
    initScrollTop();
    initRsvp();
  }, { once: true });
}());
