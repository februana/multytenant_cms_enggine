const targetDate = new Date('2026-12-29T09:00:00+07:00');
const countdown = document.getElementById('countdown');
const music = document.getElementById('backgroundMusic');
const musicBtn = document.getElementById('musicBtn');
const openInvitationBtn = document.getElementById('openInvitationBtn');
const dataSaverBtn = document.getElementById('dataSaverBtn');
const loadGalleryBtn = document.getElementById('loadGalleryBtn');
const lightbox = document.getElementById('lightbox');
const lightboxImage = document.getElementById('lightboxImage');
const lightboxClose = document.querySelector('.lightbox-close');
const csrfToken = document.getElementById('csrfToken');
const rsvpForm = document.getElementById('rsvpForm');
const formMessage = document.getElementById('formMessage');
const messagesBox = document.getElementById('messages');
const galleryGrid = document.getElementById('galleryGrid');
const guestNameDisplay = document.getElementById('guestNameDisplay');

let isDataSaver = false;

function pad(n){ return String(n).padStart(2,'0'); }
function escapeHTML(text){
  const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
  return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

function updateGuestName(){
  if(!guestNameDisplay) return;
  const params = new URLSearchParams(window.location.search);
  const guestName = params.get('to');
  if(guestName && guestName.trim() !== ''){
    guestNameDisplay.textContent = escapeHTML(guestName.trim());
  }
}

function loadLocalQr(){
  const qrImg = document.getElementById('qrLokasiImg');
  if(!qrImg) return;
  const link = document.createElement('link');
  link.rel = 'prefetch';
  link.href = 'assets/qr-lokasi.webp';
  document.head.appendChild(link);
  fetch('assets/qr-lokasi.webp', {method:'HEAD'}).then(() => {
    qrImg.src = 'assets/qr-lokasi.webp';
  }).catch(() => {});
}

function updateCountdown(){
  if(!countdown) return;
  const diff = targetDate - new Date();
  if(diff <= 0){
    countdown.innerHTML = '<div><strong>00</strong><span>Hari</span></div><div><strong>00</strong><span>Jam</span></div><div><strong>00</strong><span>Menit</span></div><div><strong>00</strong><span>Detik</span></div>';
    return;
  }
  const days = Math.floor(diff / 86400000);
  const hours = Math.floor((diff % 86400000) / 3600000);
  const minutes = Math.floor((diff % 3600000) / 60000);
  const seconds = Math.floor((diff % 60000) / 1000);
  countdown.innerHTML = `<div><strong>${days}</strong><span>Hari</span></div><div><strong>${pad(hours)}</strong><span>Jam</span></div><div><strong>${pad(minutes)}</strong><span>Menit</span></div><div><strong>${pad(seconds)}</strong><span>Detik</span></div>`;
}
updateCountdown();
setInterval(updateCountdown, 1000);

openInvitationBtn?.addEventListener('click', () => document.getElementById('undangan')?.scrollIntoView({behavior:'smooth'}));
function updateMusicButton(state){
  if(!musicBtn) return;
  switch(state){
    case 'playing': musicBtn.textContent = 'Jeda Musik'; musicBtn.dataset.state='playing'; break;
    case 'waiting': musicBtn.textContent = 'Menunggu interaksi...'; musicBtn.dataset.state='waiting'; break;
    case 'paused': musicBtn.textContent = 'Putar Musik'; musicBtn.dataset.state='paused'; break;
    default: musicBtn.textContent = 'Putar Musik'; musicBtn.dataset.state='paused';
  }
}

musicBtn?.addEventListener('click', async () => {
  if(!music) return;
  try{
    musicBtn.disabled = true;
    if(music.paused){ await music.play(); updateMusicButton('playing'); }
    else{ music.pause(); updateMusicButton('paused'); }
  }catch(e){ updateMusicButton('paused'); }
  musicBtn.disabled = false;
});

async function loadGallery(){
  if(!galleryGrid) return;
  try{
    const res = await fetch('gallery.php', {cache:'no-store'});
    const data = await res.json();
    galleryGrid.innerHTML = '';
    if(!Array.isArray(data) || data.length === 0){
      galleryGrid.innerHTML = '<p class="empty">Galeri belum tersedia.</p>';
      return;
    }
    data.forEach(item => {
      const src = typeof item === 'string' ? item : (item.thumb || item.src || item.url);
      const img = document.createElement('img');
      img.src = src;
      img.alt = 'Foto galeri undangan';
      img.loading = 'lazy';
      try{ img.decoding = 'async'; }catch(e){}
      img.addEventListener('click', () => openLightbox(src));
      galleryGrid.appendChild(img);
    });
    galleryGrid.classList.add('masonry');
  }catch(e){
    galleryGrid.innerHTML = '<p class="empty">Tidak dapat memuat galeri.</p>';
  }
}

function openLightbox(src){
  if(!lightbox || !lightboxImage) return;
  lightboxImage.src = src;
  lightbox.classList.add('show');
  lightbox.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeLightbox(){
  if(!lightbox || !lightboxImage) return;
  lightbox.classList.remove('show');
  lightbox.style.display = 'none';
  lightboxImage.src = '';
  document.body.style.overflow = '';
}

async function getCsrf(){
  if(!csrfToken) return;
  try{
    const res = await fetch('save.php?get_csrf=1', {cache:'no-store'});
    const data = await res.json();
    csrfToken.value = data.csrf_token || '';
  }catch(e){ console.warn('CSRF gagal:', e); }
}

function findAmplopButtons(){
  const buttons = document.querySelectorAll('.amplop-copy-btn');
  buttons.forEach(button => {
    button.addEventListener('click', async () => {
      const account = button.dataset.account || '';
      if (!account) return;
      try {
        await navigator.clipboard.writeText(account);
        const feedback = button.parentElement?.querySelector('.amplop-feedback');
        if (feedback) {
          feedback.textContent = '✓ Nomor berhasil disalin';
          feedback.style.display = 'block';
          setTimeout(() => { feedback.style.display = 'none'; }, 2500);
        }
      } catch (err) {
        const temp = document.createElement('textarea');
        temp.value = account;
        temp.setAttribute('readonly', '');
        temp.style.position = 'absolute';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        const feedback = button.parentElement?.querySelector('.amplop-feedback');
        if (feedback) {
          feedback.textContent = '✓ Nomor berhasil disalin';
          feedback.style.display = 'block';
          setTimeout(() => { feedback.style.display = 'none'; }, 2500);
        }
      }
    });
  });
}

function updateDataSaverState(){
  const storageValue = localStorage.getItem('dataSaverEnabled');
  isDataSaver = storageValue === '1';
  if (!dataSaverBtn) return;
  if (isDataSaver) {
    dataSaverBtn.classList.add('active');
    dataSaverBtn.textContent = 'Mode Hemat Data: Aktif';
    if (music && !music.paused) {
      music.pause();
      updateMusicButton('paused');
    }
    if (loadGalleryBtn) {
      loadGalleryBtn.style.display = 'block';
      galleryGrid.innerHTML = '<p class="loading">Mode Hemat Data aktif. Tekan "Muat Galeri" untuk melihat foto.</p>';
    }
  } else {
    dataSaverBtn.classList.remove('active');
    dataSaverBtn.textContent = 'Mode Hemat Data';
    if (loadGalleryBtn) loadGalleryBtn.style.display = 'none';
  }
}

function toggleDataSaver(){
  isDataSaver = !isDataSaver;
  localStorage.setItem('dataSaverEnabled', isDataSaver ? '1' : '0');
  updateDataSaverState();
  if (!isDataSaver) {
    if (loadGalleryBtn) {
      loadGalleryBtn.style.display = 'none';
    }
    loadGallery();
  }
}

async function loadMessages(){
  if(!messagesBox) return;
  try{
    const res = await fetch('messages.php', {cache:'no-store'});
    const data = await res.json();
    messagesBox.innerHTML = '';
    if(!Array.isArray(data) || data.length === 0) return;
    data.forEach(msg => {
      const div = document.createElement('div');
      div.className = 'message';
      div.innerHTML = `<strong>${escapeHTML(msg.nama)}</strong><span>${escapeHTML(msg.status)}</span><p>${escapeHTML(msg.ucapan)}</p>`;
      messagesBox.appendChild(div);
    });
  }catch(e){ console.warn('Pesan gagal dimuat:', e); }
}

rsvpForm?.addEventListener('submit', async e => {
  e.preventDefault();
  const submitBtn = rsvpForm.querySelector('button[type="submit"]');
  if(submitBtn) { submitBtn.disabled = true; }
  formMessage.className = 'form-message';
  formMessage.textContent = 'Mengirim...';
  formMessage.setAttribute('aria-live','polite');
  try{
    const res = await fetch('save.php', {method:'POST', body:new FormData(rsvpForm)});
    const data = await res.json();
    const msg = data.message || (data.success ? 'Berhasil dikirim.' : 'Gagal dikirim.');
    formMessage.textContent = msg;
    if (data.success) {
      formMessage.classList.add('success');
      rsvpForm.reset();
      await getCsrf();
      await loadMessages();
      const firstInput = rsvpForm.querySelector('input,select,textarea');
      if (firstInput) firstInput.focus();
      setTimeout(() => { formMessage.className = 'form-message'; formMessage.textContent = ''; }, 5000);
    } else {
      formMessage.classList.add('error');
    }
  }catch(err){ formMessage.textContent = 'Gagal mengirim RSVP. Pastikan server PHP berjalan.'; }
  if(submitBtn) { submitBtn.disabled = false; }
});

if (dataSaverBtn) {
  dataSaverBtn.addEventListener('click', toggleDataSaver);
}
if (loadGalleryBtn) {
  loadGalleryBtn.addEventListener('click', () => {
    loadGallery();
    loadGalleryBtn.style.display = 'none';
  });
}
if (lightboxClose) {
  lightboxClose.addEventListener('click', closeLightbox);
}
if (lightbox) {
  lightbox.addEventListener('click', event => {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });
}
window.addEventListener('keyup', event => {
  if (event.key === 'Escape') {
    closeLightbox();
  }
});

const savedDataSaver = localStorage.getItem('dataSaverEnabled');
if (savedDataSaver === '1') {
  isDataSaver = true;
}
updateDataSaverState();
getCsrf();
loadMessages();
updateGuestName();
loadLocalQr();
findAmplopButtons();
if (!isDataSaver) {
  loadGallery();
}

async function tryAutoplay() {
  if (!music || isDataSaver) return;
  try{
    await music.play();
    music.muted = false;
    updateMusicButton('playing');
    removeInteractionListeners();
    return;
  }catch(e){
    try{
      music.muted = true;
      await music.play();
      updateMusicButton('waiting');
      attachInteractionListeners();
      return;
    }catch(e2){
      updateMusicButton('paused');
      attachInteractionListeners();
    }
  }
}

function resumeAfterInteraction(){
  if(!music) return;
  music.muted = false;
  music.play().then(()=>{ updateMusicButton('playing'); }).catch(()=>{ updateMusicButton('paused'); });
  removeInteractionListeners();
}

function attachInteractionListeners(){
  ['click','touchstart','scroll','keydown'].forEach(ev => window.addEventListener(ev, resumeAfterInteraction, {once:true, passive:true}));
}

function removeInteractionListeners(){
  ['click','touchstart','scroll','keydown'].forEach(ev => window.removeEventListener(ev, resumeAfterInteraction));
}

setTimeout(tryAutoplay, 300);
