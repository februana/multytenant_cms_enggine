const tabs = document.querySelectorAll('.sidebar nav a');
const sections = document.querySelectorAll('.panel-section');
const mobileToggle = document.querySelector('#mobile-menu-toggle');
const navElement = document.querySelector('.sidebar nav');
const copyLinkButton = document.querySelector('#copyInvitationLink');
const invitationLinkDisplay = document.querySelector('#invitationLink');

if (mobileToggle) {
  mobileToggle.addEventListener('click', () => {
    navElement.classList.toggle('open');
  });
}

tabs.forEach(tab => {
  tab.addEventListener('click', event => {
    event.preventDefault();
    const target = document.querySelector(tab.getAttribute('href'));
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    if (navElement.classList.contains('open')) {
      navElement.classList.remove('open');
    }
  });
});

if (copyLinkButton && invitationLinkDisplay) {
  copyLinkButton.addEventListener('click', async () => {
    const url = invitationLinkDisplay.value || invitationLinkDisplay.textContent;
    if (!url) return;
    try {
      await navigator.clipboard.writeText(url);
      copyLinkButton.textContent = 'Tautan Disalin';
      setTimeout(() => { copyLinkButton.textContent = 'Salin Tautan'; }, 2000);
    } catch (err) {
      console.warn(err);
      copyLinkButton.textContent = 'Salin Manual';
    }
  });
}

const previewInputs = document.querySelectorAll('[data-preview-target]');
previewInputs.forEach(input => {
  input.addEventListener('change', event => {
    const targetId = input.dataset.previewTarget;
    const target = document.querySelector(targetId);
    const files = input.files;
    if (!target || !files || files.length === 0) return;
    const reader = new FileReader();
    reader.onload = () => {
      if (target.tagName === 'IMG') {
        target.src = reader.result;
        target.style.display = '';
      }
      if (target.tagName === 'TEXTAREA') {
        target.textContent = reader.result;
      }
    };
    reader.readAsDataURL(files[0]);
  });
});

const reorderForms = document.querySelectorAll('.gallery-order-form');
reorderForms.forEach(form => {
  form.addEventListener('submit', event => {
    const inputs = form.querySelectorAll('input[type=number]');
    inputs.forEach(input => {
      if (input.value === '') {
        input.value = 99;
      }
    });
  });
});

const deleteButtons = document.querySelectorAll('.gallery-delete-button');
deleteButtons.forEach(button => {
  button.addEventListener('click', () => {
    const filename = button.dataset.filename;
    const csrfInput = document.querySelector('input[name=csrf_token]');
    if (!filename || !csrfInput || !confirm('Hapus foto ini dari galeri?')) return;
    const form = document.createElement('form');
    form.method = 'post';
    form.style.display = 'none';
    const csrfField = document.createElement('input');
    csrfField.type = 'hidden';
    csrfField.name = 'csrf_token';
    csrfField.value = csrfInput.value;
    const actionField = document.createElement('input');
    actionField.type = 'hidden';
    actionField.name = 'action';
    actionField.value = 'delete_gallery_item';
    const filenameField = document.createElement('input');
    filenameField.type = 'hidden';
    filenameField.name = 'gallery_filename';
    filenameField.value = filename;
    form.appendChild(csrfField);
    form.appendChild(actionField);
    form.appendChild(filenameField);
    document.body.appendChild(form);
    form.submit();
  });
});

const guestNameInput = document.getElementById('guestNameInput');
const guestLinkOutput = document.getElementById('guestLinkOutput');
const generateGuestLinkBtn = document.getElementById('generateGuestLinkBtn');
const copyGuestLinkBtn = document.getElementById('copyGuestLinkBtn');
const saveGuestLinkBtn = document.getElementById('saveGuestLinkBtn');
const sendGuestLinkWhatsappBtn = document.getElementById('sendGuestLinkWhatsappBtn');
const guestLinkQrPreview = document.getElementById('guestLinkQrPreview');
const downloadGuestLinkQrBtn = document.getElementById('downloadGuestLinkQrBtn');
const guestLinkSearch = document.getElementById('guestLinkSearch');
const guestLinksTable = document.getElementById('guestLinksTable');
const guestLinkBaseUrlInput = document.getElementById('guestLinkBaseUrl');
const guestLinkWhatsappPhone = document.getElementById('guestLinkWhatsappPhone');
const guestLinkWhatsappMessage = document.getElementById('guestLinkWhatsappMessage');
const guestLinkStatus = document.getElementById('guestLinkStatus');

function getGuestLinkBaseUrl() {
  const value = guestLinkBaseUrlInput?.value?.trim();
  return value ? value.replace(/\/+$/, '') : '';
}

function getGuestLinkWhatsappUrl(invitationUrl) {
  const phoneRaw = guestLinkWhatsappPhone?.value || '';
  const message = guestLinkWhatsappMessage?.value || '';
  const phone = phoneRaw.replace(/[^0-9+]/g, '').replace(/^\+/, '');
  const text = [message.trim(), invitationUrl].filter(Boolean).join(' ');
  return phone ? 'https://wa.me/' + encodeURIComponent(phone) + '?text=' + encodeURIComponent(text) : '#';
}

function updateGuestLinkOutput() {
  const guestName = guestNameInput?.value.trim() || '';
  if (!guestName || !guestLinkOutput) {
    guestLinkOutput.value = '';
    if (guestLinkQrPreview) {
      guestLinkQrPreview.style.display = 'none';
      guestLinkQrPreview.src = '';
    }
    if (downloadGuestLinkQrBtn) {
      downloadGuestLinkQrBtn.style.display = 'none';
      downloadGuestLinkQrBtn.href = '#';
    }
    return false;
  }
  const baseUrl = getGuestLinkBaseUrl();
  if (!baseUrl) {
    guestLinkOutput.value = '';
    showGuestLinkStatus('Konfigurasikan Site URL di Pengaturan terlebih dahulu.', false);
    return false;
  }
  const url = baseUrl + '/?to=' + encodeURIComponent(guestName);
  guestLinkOutput.value = url;
  const qrUrl = '/admin/qr.php?data=' + encodeURIComponent(url);
  if (guestLinkQrPreview) {
    guestLinkQrPreview.src = qrUrl;
    guestLinkQrPreview.style.display = 'block';
  }
  if (downloadGuestLinkQrBtn) {
    downloadGuestLinkQrBtn.href = qrUrl;
    downloadGuestLinkQrBtn.style.display = 'inline-flex';
  }
  return true;
}

function showGuestLinkStatus(message, success = true) {
  if (!guestLinkStatus) return;
  guestLinkStatus.textContent = message;
  guestLinkStatus.style.color = success ? '#2f5d32' : '#b02a2a';
  if (message) {
    setTimeout(() => {
      if (guestLinkStatus.textContent === message) {
        guestLinkStatus.textContent = '';
      }
    }, 4000);
  }
}

generateGuestLinkBtn?.addEventListener('click', () => {
  if (!guestNameInput?.value.trim()) {
    showGuestLinkStatus('Masukkan nama tamu terlebih dahulu.', false);
    return;
  }
  if (updateGuestLinkOutput()) {
    showGuestLinkStatus('Link tamu dibuat. Tekan Simpan untuk menyimpan.');
  }
});

copyGuestLinkBtn?.addEventListener('click', async () => {
  if (!guestLinkOutput?.value) {
    showGuestLinkStatus('Tidak ada link untuk disalin.', false);
    return;
  }
  try {
    await navigator.clipboard.writeText(guestLinkOutput.value);
    showGuestLinkStatus('Link berhasil disalin.');
  } catch (err) {
    showGuestLinkStatus('Gagal menyalin link.', false);
  }
});

sendGuestLinkWhatsappBtn?.addEventListener('click', () => {
  if (!guestLinkOutput?.value) {
    showGuestLinkStatus('Silakan buat link terlebih dahulu.', false);
    return;
  }
  const whatsappUrl = getGuestLinkWhatsappUrl(guestLinkOutput.value);
  if (whatsappUrl === '#') {
    showGuestLinkStatus('Nomor WhatsApp belum dikonfigurasi.', false);
    return;
  }
  window.open(whatsappUrl, '_blank', 'noopener');
});

saveGuestLinkBtn?.addEventListener('click', () => {
  if (!guestLinkOutput?.value || !guestNameInput?.value.trim()) {
    showGuestLinkStatus('Silakan buat nama dan link terlebih dahulu.', false);
    return;
  }
  const form = document.createElement('form');
  form.method = 'post';
  form.style.display = 'none';
  const csrfField = document.createElement('input');
  csrfField.type = 'hidden';
  csrfField.name = 'csrf_token';
  csrfField.value = document.querySelector('input[name=csrf_token]')?.value || '';
  const actionField = document.createElement('input');
  actionField.type = 'hidden';
  actionField.name = 'action';
  actionField.value = 'save_guest_link';
  const guestNameField = document.createElement('input');
  guestNameField.type = 'hidden';
  guestNameField.name = 'guest_name';
  guestNameField.value = guestNameInput.value.trim();
  const baseUrlField = document.createElement('input');
  baseUrlField.type = 'hidden';
  baseUrlField.name = 'base_url';
  baseUrlField.value = getGuestLinkBaseUrl();
  form.append(csrfField, actionField, guestNameField, baseUrlField);
  document.body.appendChild(form);
  form.submit();
});

function updateGuestLinkFilter() {
  if (!guestLinkSearch || !guestLinksTable) return;
  const query = guestLinkSearch.value.trim().toLowerCase();
  const rows = guestLinksTable.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const guestName = row.dataset.guestName?.toLowerCase() || '';
    const invitationUrl = row.dataset.invitationUrl?.toLowerCase() || '';
    row.style.display = !query || guestName.includes(query) || invitationUrl.includes(query) ? '' : 'none';
  });
}

guestLinkSearch?.addEventListener('input', updateGuestLinkFilter);

document.querySelectorAll('.guest-link-copy').forEach(button => {
  button.addEventListener('click', async () => {
    const url = button.dataset.url;
    if (!url) return;
    try {
      await navigator.clipboard.writeText(url);
      showGuestLinkStatus('Link tamu disalin.');
    } catch (err) {
      showGuestLinkStatus('Gagal menyalin link tamu.', false);
    }
  });
});

document.querySelectorAll('.guest-link-whatsapp').forEach(button => {
  button.addEventListener('click', () => {
    const url = button.dataset.url;
    if (!url) return;
    const whatsappUrl = getGuestLinkWhatsappUrl(url);
    if (whatsappUrl === '#') {
      showGuestLinkStatus('Nomor WhatsApp belum dikonfigurasi.', false);
      return;
    }
    window.open(whatsappUrl, '_blank', 'noopener');
  });
});

document.querySelectorAll('.guest-link-qr').forEach(button => {
  button.addEventListener('click', () => {
    const url = button.dataset.url;
    if (!url || !guestLinkQrPreview || !downloadGuestLinkQrBtn) return;
    const qrUrl = '/admin/qr.php?data=' + encodeURIComponent(url);
    guestLinkQrPreview.src = qrUrl;
    guestLinkQrPreview.style.display = 'block';
    downloadGuestLinkQrBtn.href = qrUrl;
    downloadGuestLinkQrBtn.style.display = 'inline-flex';
    showGuestLinkStatus('QR Code disiapkan untuk link tamu.');
  });
});

const themeSettingsForm = document.getElementById('themeSettingsForm');
const themePreviewFrame = document.getElementById('themePreviewFrame');
const themePreviewReset = document.getElementById('themePreviewReset');
const themePreviewCancel = document.getElementById('themePreviewCancel');

if (themeSettingsForm && themePreviewFrame) {
  const previewFieldNames = [
    'theme_preset', 'primary_color', 'secondary_color', 'accent_color', 'background_color',
    'paper_color', 'muted_color', 'text_color', 'link_color', 'heading_font', 'body_font',
    'font_size_base', 'container_width', 'section_spacing', 'border_radius', 'shadow',
    'button_style', 'navbar_style', 'card_style', 'footer_style', 'animation_enabled',
    // Hero settings - Desktop
    'hero_height', 'hero_vertical_alignment', 'hero_content_width',
    'hero_image_fit', 'hero_image_position',
    // Hero settings - Mobile
    'mobile_hero_height', 'mobile_hero_vertical_alignment', 'mobile_hero_content_width',
    'mobile_hero_image_fit', 'mobile_hero_image_position'
  ];
  const savedTheme = JSON.parse(themeSettingsForm.dataset.savedTheme || '{}');
  const themePresets = JSON.parse(themeSettingsForm.dataset.themePresets || '{}');
  const previewInputs = previewFieldNames
    .map(name => themeSettingsForm.elements[name])
    .filter(Boolean);
  let debounceTimer = null;

  function fieldValue(name) {
    const field = themeSettingsForm.elements[name];
    if (!field) return '';
    if (field.type === 'checkbox') return field.checked;
    return field.value;
  }

  function setFieldValue(name, value) {
    const field = themeSettingsForm.elements[name];
    if (!field) return;
    if (field.type === 'checkbox') {
      field.checked = Boolean(value);
      return;
    }
    field.value = value ?? '';
  }

  function collectTheme() {
    const selectedPreset = fieldValue('theme_preset');
    const presetValues = selectedPreset && selectedPreset !== 'custom' ? (themePresets[selectedPreset] || {}) : {};
    const theme = { ...savedTheme, ...presetValues };
    previewFieldNames.forEach(name => {
      if (name === 'theme_preset') {
        theme[name] = selectedPreset;
        return;
      }
      const field = themeSettingsForm.elements[name];
      if (!field) return;
      // Always use form field value if it exists (manual override)
      theme[name] = fieldValue(name);
    });
    // Also include buttons.mobile_layout in the collected theme
    const mobileLayoutField = themeSettingsForm.elements['buttons_mobile_layout'];
    if (mobileLayoutField) {
      theme.buttons = theme.buttons || {};
      theme.buttons.mobile_layout = mobileLayoutField.value;
    }
    // Keep preview in sync with the production renderer contract; the CSS uses a valid flex-direction value.
    if (theme.buttons && theme.buttons.mobile_layout) {
      theme.buttons.mobile_layout = theme.buttons.mobile_layout === 'horizontal' || theme.buttons.mobile_layout === '2-columns' ? 'row' : 'column';
    }
    return theme;
  }

  function postThemePreview(theme) {
    const frameWindow = themePreviewFrame.contentWindow;
    if (!frameWindow) return;
    frameWindow.postMessage({ type: 'theme-preview:update', theme }, window.location.origin);
  }

  function postPreview() {
    postThemePreview(collectTheme());
  }

  function schedulePreview(delay = 150) {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(postPreview, delay);
  }

  function restoreForm(theme) {
    previewFieldNames.forEach(name => {
      if (name in theme) setFieldValue(name, theme[name]);
    });
  }

  function applyPresetToForm() {
    const selectedPreset = fieldValue('theme_preset');
    if (!selectedPreset || selectedPreset === 'custom' || !themePresets[selectedPreset]) return;
    Object.entries(themePresets[selectedPreset]).forEach(([name, value]) => setFieldValue(name, value));
  }

  previewInputs.forEach(input => {
    input.addEventListener('input', () => {
      if (input.name === 'theme_preset') applyPresetToForm();
      schedulePreview();
    });
    input.addEventListener('change', () => {
      if (input.name === 'theme_preset') applyPresetToForm();
      schedulePreview(input.tagName === 'SELECT' || input.type === 'checkbox' ? 0 : 150);
    });
  });

  themePreviewFrame.addEventListener('load', () => postPreview());
  themePreviewReset?.addEventListener('click', () => {
    restoreForm(savedTheme);
    postPreview();
  });
  themePreviewCancel?.addEventListener('click', () => {
    postThemePreview(savedTheme);
  });
}
