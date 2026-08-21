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
const globalThemePreset = document.getElementById('globalThemePreset');
const themeOptionsPresetKey = document.getElementById('themeOptionsPresetKey');
const previewViewportButtons = Array.from(document.querySelectorAll('[data-preview-viewport]'));

function setPreviewViewport(viewport) {
  if (!themePreviewFrame) return;
  const sizes = {
    desktop: {width: '100%', height: '720px'},
    tablet: {width: '768px', height: '1024px'},
    mobile: {width: '390px', height: '844px'}
  };
  const size = sizes[viewport] || sizes.desktop;
  themePreviewFrame.style.width = size.width;
  themePreviewFrame.style.height = size.height;
  themePreviewFrame.style.maxWidth = '100%';
  themePreviewFrame.style.display = 'block';
  themePreviewFrame.style.margin = '0 auto';
  previewViewportButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.previewViewport === viewport));
}
previewViewportButtons.forEach((button) => button.addEventListener('click', () => setPreviewViewport(button.dataset.previewViewport)));
setPreviewViewport('desktop');

if (themeSettingsForm && themePreviewFrame) {
  const previewFieldNames = [
    'theme_preset', 'primary_color', 'secondary_color', 'accent_color', 'background_color',
    'paper_color', 'muted_color', 'text_color', 'link_color', 'heading_font', 'body_font',
    'font_size_base', 'container_width', 'section_spacing', 'border_radius', 'shadow',
    'button_style', 'navbar_style', 'card_style', 'footer_style', 'animation_enabled',
    'hero_height', 'hero_vertical_alignment', 'hero_content_width',
    'hero_image_fit', 'hero_image_position',
    'mobile_hero_height', 'mobile_hero_vertical_alignment', 'mobile_hero_content_width',
    'mobile_hero_image_fit', 'mobile_hero_image_position'
  ];
  const savedTheme = JSON.parse(themeSettingsForm.dataset.savedTheme || '{}');
  const customTheme = JSON.parse(themeSettingsForm.dataset.customTheme || '{}');
  const themePresets = JSON.parse(themeSettingsForm.dataset.themePresets || '{}');
  const themeLabels = JSON.parse(themeSettingsForm.dataset.themeLabels || '{}');
  const visualSchemas = JSON.parse(themeSettingsForm.dataset.visualSchemas || '{}');
  const savedVisualValues = JSON.parse(themeSettingsForm.dataset.visualValues || '{}');
  const mediaAssets = JSON.parse(themeSettingsForm.dataset.mediaAssets || '[]');
  const visualPanel = document.getElementById('visualCapabilityPanel');
  const visualTitle = document.getElementById('visualCapabilityTitle');
  const visualFields = document.getElementById('visualCapabilityFields');
  const customEditor = themeSettingsForm.querySelector('[data-custom-theme-editor]');
  const hiddenPresetField = themeSettingsForm.elements.theme_preset;
  let debounceTimer = null;
  const unsavedVisualValues = {};
  const unsavedThemeValues = {};

  function getCurrentPreset() {
    const selected = globalThemePreset?.value || hiddenPresetField?.value || visualPanel?.dataset.visualPanel || 'custom';
    return selected === 'custom' ? 'custom' : (visualSchemas[selected] ? selected : 'custom');
  }

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

  function visualMediaUrl(value) {
    const path = String(value || '').trim();
    if (!path) return '';
    return /^https?:\/\//i.test(path) ? path : `/${path.replace(/^\/+/, '')}`;
  }

  function updatePalettePreview() {
    const preview = document.querySelector('[data-palette-preview]');
    if (!preview) return;
    const valueFor = (key, fallback) => themeSettingsForm.querySelector(`[name="visuals[${key}]"]`)?.value || fallback;
    const primary = valueFor('accent_color', '#c84c47');
    const secondary = valueFor('palette_secondary_color', '#f0c2a1');
    const mix = valueFor('palette_mix', '50');
    const saturation = valueFor('palette_saturation', '1.10');
    preview.style.background = `linear-gradient(135deg, ${primary}, color-mix(in srgb, ${primary} ${mix}%, ${secondary}), ${secondary})`;
    preview.style.filter = `saturate(${saturation})`;
  }

  function updateVisualDecorations(input) {
    if (!input) return;
    if (input.dataset.fontPreview) input.style.fontFamily = input.value;
    const output = document.querySelector(`[data-range-output="${input.id}"]`);
    if (output) output.value = input.value;
    const sample = document.querySelector(`[data-for="${input.id}"]`);
    if (sample) {
      sample.style.fontFamily = input.value;
      if (input.id.includes('heading_font_weight') || input.id.includes('body_font_weight')) sample.style.fontWeight = input.value;
      if (input.id.includes('font_size_base')) sample.style.fontSize = input.value;
    }
    updatePalettePreview();
    if (input.dataset.visualMediaSelect) {
      const preview = input.closest('.visual-field')?.querySelector('[data-visual-preview]');
      const wrap = preview?.closest('.visual-media-preview');
      if (preview && wrap) {
        const url = visualMediaUrl(input.value);
        preview.src = url;
        wrap.style.display = url ? 'block' : 'none';
      }
    }
  }

  function getStoredVisuals(preset) {
    return {...(savedVisualValues[preset] || {}), ...(unsavedVisualValues[preset] || {})};
  }

  function collectVisuals() {
    const values = {};
    if (!visualFields) return values;
    visualFields.querySelectorAll('[name^="visuals["]').forEach((input) => {
      const match = input.name.match(/^visuals\[([^\]]+)\]$/);
      if (match) values[match[1]] = input.value;
    });
    return values;
  }

  function captureCurrentVisuals(preset = getCurrentPreset()) {
    if (preset) unsavedVisualValues[preset] = collectVisuals();
  }

  function readFieldValue(field) {
    if (!field) return '';
    return field.type === 'checkbox' ? field.checked : field.value;
  }

  function collectEditorThemeState() {
    const values = {};
    previewFieldNames.forEach(name => {
      if (name === 'theme_preset') return;
      const field = themeSettingsForm.elements[name];
      if (field) values[name] = readFieldValue(field);
    });
    customEditor?.querySelectorAll('[name]').forEach(field => {
      if (field.name && field.name !== 'theme_preset') values[field.name] = readFieldValue(field);
    });
    return values;
  }

  function captureCurrentTheme(preset = getCurrentPreset()) {
    if (preset) unsavedThemeValues[preset] = collectEditorThemeState();
  }

  function createVisualField(key, definition, value) {
    const type = definition.type || 'text';
    const row = document.createElement('div');
    row.className = 'form-row visual-field';
    row.dataset.visualKey = key;
    const id = `visual-${key.replace(/[^a-zA-Z0-9_-]/g, '-')}`;
    const label = document.createElement('label');
    label.htmlFor = id;
    label.textContent = definition.label || key.replace(/_/g, ' ');
    row.appendChild(label);

    let input;
    if (type === 'font') {
      input = document.createElement('select');
      input.dataset.fontPreview = '1';
      Object.entries(definition.options || {}).forEach(([fontValue, fontLabel]) => {
        const option = document.createElement('option');
        option.value = fontValue;
        option.textContent = fontLabel;
        option.style.fontFamily = fontValue;
        input.appendChild(option);
      });
    } else if (type === 'range') {
      const wrapper = document.createElement('div');
      wrapper.style.cssText = 'display:flex;align-items:center;gap:0.65rem;';
      input = document.createElement('input');
      input.type = 'range';
      input.min = definition.min ?? '0';
      input.max = definition.max ?? '1';
      input.step = definition.step ?? '0.05';
      input.style.flex = '1';
      const output = document.createElement('output');
      output.dataset.rangeOutput = id;
      output.value = value;
      wrapper.append(input, output);
      row.appendChild(wrapper);
    } else if (type === 'color') {
      input = document.createElement('input');
      input.type = 'color';
      input.setAttribute('list', `visual-palette-${key}`);
      input.style.cssText = 'width:100%;height:42px;';
      const palette = definition.palette || {};
      if (Object.keys(palette).length) {
        const paletteWrap = document.createElement('div');
        paletteWrap.className = 'visual-color-palette';
        paletteWrap.setAttribute('aria-label', 'Pilihan warna cepat');
        paletteWrap.style.cssText = 'display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.55rem;';
        Object.entries(palette).forEach(([color, labelText]) => {
          const swatch = document.createElement('button');
          swatch.type = 'button';
          swatch.dataset.visualColorPalette = color;
          swatch.title = labelText;
          swatch.setAttribute('aria-label', labelText);
          swatch.style.cssText = `width:28px;height:28px;padding:0;border-radius:50%;border:2px solid #fff;outline:1px solid #d8c9bc;background:${color};cursor:pointer;`;
          swatch.addEventListener('click', () => {
            input.value = color;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.dispatchEvent(new Event('change', {bubbles: true}));
          });
          paletteWrap.appendChild(swatch);
        });
        row.appendChild(paletteWrap);
        const datalist = document.createElement('datalist');
        datalist.id = `visual-palette-${key}`;
        Object.entries(palette).forEach(([color, labelText]) => {
          const option = document.createElement('option');
          option.value = color;
          option.label = labelText;
          datalist.appendChild(option);
        });
        row.appendChild(datalist);
      }
    } else if (type === 'image') {
      input = document.createElement('select');
      input.dataset.visualMediaSelect = '1';
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Gunakan gambar bawaan tema';
      input.appendChild(defaultOption);
      const assetPaths = new Set();
      mediaAssets.filter(asset => asset && asset.type === 'image' && asset.path).forEach(asset => {
        assetPaths.add(asset.path);
        const option = document.createElement('option');
        option.value = asset.path;
        option.textContent = `${asset.label || 'Media'} — ${asset.name || asset.path}`;
        input.appendChild(option);
      });
      if (value && !assetPaths.has(value)) {
        const storedOption = document.createElement('option');
        storedOption.value = value;
        storedOption.textContent = `Gambar tersimpan — ${value}`;
        input.appendChild(storedOption);
      }
      const previewWrap = document.createElement('div');
      previewWrap.className = 'visual-media-preview';
      previewWrap.style.cssText = `margin-top:0.65rem;${value ? '' : 'display:none;'}`;
      const preview = document.createElement('img');
      preview.dataset.visualPreview = '1';
      preview.src = visualMediaUrl(value);
      preview.alt = `Pratinjau ${definition.label || key}`;
      preview.style.cssText = 'display:block;max-width:100%;width:min(100%,360px);max-height:180px;object-fit:cover;border-radius:10px;border:1px solid #eadccf;';
      previewWrap.appendChild(preview);
      row.appendChild(previewWrap);
      const resetButton = document.createElement('button');
      resetButton.type = 'submit';
      resetButton.name = 'reset_visual_key';
      resetButton.value = key;
      resetButton.className = 'button small-button';
      resetButton.textContent = 'Kembalikan ke Bawaan';
      const resetRow = document.createElement('div');
      resetRow.style.cssText = 'display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;margin-top:0.55rem;';
      resetRow.appendChild(resetButton);
      const resetNote = document.createElement('small');
      resetNote.textContent = 'Reset hanya menghapus referensi CMS; file media tetap ada.';
      resetRow.appendChild(resetNote);
      row.appendChild(resetRow);
      const mediaNote = document.createElement('small');
      mediaNote.append('Pilih gambar dari Foto, Musik, dan File. Untuk upload baru, gunakan ');
      const mediaLink = document.createElement('a');
      mediaLink.href = '#file-manager';
      mediaLink.textContent = 'Foto, Musik, dan File';
      mediaNote.append(mediaLink, ', lalu muat ulang halaman.');
      row.appendChild(mediaNote);
    } else {
      input = document.createElement('input');
      input.type = 'text';
    }
    input.id = id;
    input.name = `visuals[${key}]`;
    input.value = value ?? definition.default ?? '';
    if (type === 'font') input.style.fontFamily = input.value;
    if (type !== 'range') row.appendChild(input);

    if (type === 'font') {
      const sample = document.createElement('span');
      sample.className = 'font-preview-sample';
      sample.dataset.for = id;
      sample.style.cssText = `display:block;margin-top:6px;font-family:${input.value};font-size:1.35rem;`;
      sample.textContent = 'Aa Bb Cc — Februana & Andi';
      row.appendChild(sample);
    }
    if (definition.description) {
      const description = document.createElement('small');
      description.textContent = definition.description;
      row.appendChild(description);
    }
    input.addEventListener('input', () => {
      updateVisualDecorations(input);
      const preset = getCurrentPreset();
      unsavedVisualValues[preset] = collectVisuals();
      unsavedThemeValues[preset] = collectEditorThemeState();
      schedulePreview();
    });
    input.addEventListener('change', () => {
      updateVisualDecorations(input);
      const preset = getCurrentPreset();
      unsavedVisualValues[preset] = collectVisuals();
      unsavedThemeValues[preset] = collectEditorThemeState();
      schedulePreview(0);
    });
    return row;
  }

  function renderVisualPanel(preset) {
    if (!visualPanel || !visualFields) return;
    const schema = visualSchemas[preset] || {};
    const values = getStoredVisuals(preset);
    visualPanel.dataset.visualPanel = preset;
    if (visualTitle) visualTitle.textContent = `Tampilan ${themeLabels[preset] || preset}`;
    visualFields.replaceChildren();
    Object.entries(schema).forEach(([key, definition]) => {
      visualFields.appendChild(createVisualField(key, definition, values[key]));
    });
    visualPanel.hidden = false;
  }

  function getBaseTheme(preset) {
    const base = preset === 'custom'
      ? {...customTheme}
      : {...savedTheme, ...(themePresets[preset] || {})};
    return {...base, ...(unsavedThemeValues[preset] || {}), theme_preset: preset, mode: preset === 'custom' ? 'custom' : 'preset'};
  }

  function restoreForm(theme) {
    previewFieldNames.forEach(name => {
      if (name in theme) setFieldValue(name, theme[name]);
    });
  }

  function applyPresetToForm(preset) {
    restoreForm(getBaseTheme(preset));
    if (hiddenPresetField) hiddenPresetField.value = preset;
    if (themeOptionsPresetKey) themeOptionsPresetKey.value = preset;
    if (customEditor) customEditor.hidden = preset !== 'custom';
  }

  function selectPreset(preset, shouldPreview = true) {
    updatePalettePreview();
    const nextPreset = visualSchemas[preset] ? preset : 'custom';
    const previousPreset = getCurrentPreset();
    if (previousPreset !== nextPreset) {
      captureCurrentVisuals(previousPreset);
      captureCurrentTheme(previousPreset);
    }
    if (globalThemePreset && globalThemePreset.value !== nextPreset) globalThemePreset.value = nextPreset;
    applyPresetToForm(nextPreset);
    renderVisualPanel(nextPreset);
    updatePalettePreview();
    if (shouldPreview) schedulePreview(0);
  }

  function collectTheme() {
    const selectedPreset = getCurrentPreset();
    const theme = getBaseTheme(selectedPreset);
    previewFieldNames.forEach(name => {
      if (name === 'theme_preset') {
        theme[name] = selectedPreset;
        return;
      }
      const field = themeSettingsForm.elements[name];
      if (!field) return;
      if (customEditor?.contains(field) && selectedPreset !== 'custom') return;
      theme[name] = fieldValue(name);
    });
    const mobileLayoutField = themeSettingsForm.elements.buttons_mobile_layout;
    if (mobileLayoutField) {
      theme.buttons = theme.buttons || {};
      theme.buttons.mobile_layout = mobileLayoutField.value;
    }
    if (theme.buttons?.mobile_layout) {
      theme.buttons.mobile_layout = ['horizontal', '2-columns'].includes(theme.buttons.mobile_layout) ? 'row' : 'column';
    }
    theme.visuals = collectVisuals();
    return theme;
  }

  function postThemePreview(theme) {
    const frameWindow = themePreviewFrame.contentWindow;
    if (frameWindow) frameWindow.postMessage({type: 'theme-preview:update', theme}, window.location.origin);
  }

  function postPreview() {
    postThemePreview(collectTheme());
  }

  function schedulePreview(delay = 150) {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(postPreview, delay);
  }

  globalThemePreset?.addEventListener('change', () => selectPreset(globalThemePreset.value));
  themeSettingsForm.elements.theme_preset?.addEventListener('change', () => selectPreset(themeSettingsForm.elements.theme_preset.value));
  document.addEventListener('click', (event) => {
    const swatch = event.target.closest('[data-visual-color-palette-static]');
    if (!swatch || !visualFields?.contains(swatch)) return;
    event.preventDefault();
    const field = swatch.closest('.visual-field')?.querySelector('input[type="color"]');
    if (!field) return;
    field.value = swatch.dataset.visualColorPaletteStatic || swatch.dataset.visualColorPalette || field.value;
    field.dispatchEvent(new Event('input', {bubbles: true}));
    field.dispatchEvent(new Event('change', {bubbles: true}));
  });
  themePreviewFrame.addEventListener('load', postPreview);

  themePreviewReset?.addEventListener('click', () => {
    const preset = getCurrentPreset();
    delete unsavedVisualValues[preset];
    delete unsavedThemeValues[preset];
    applyPresetToForm(preset);
    renderVisualPanel(preset);
    postPreview();
  });
  themePreviewCancel?.addEventListener('click', () => {
    const preset = getCurrentPreset();
    delete unsavedVisualValues[preset];
    delete unsavedThemeValues[preset];
    applyPresetToForm(preset);
    renderVisualPanel(preset);
    postPreview();
  });

  previewFieldNames.forEach(name => {
    const input = themeSettingsForm.elements[name];
    if (!input || input.name === 'theme_preset') return;
    input.addEventListener('input', schedulePreview);
    input.addEventListener('change', () => schedulePreview(input.tagName === 'SELECT' || input.type === 'checkbox' ? 0 : 150));
  });

  selectPreset(getCurrentPreset(), false);
}
