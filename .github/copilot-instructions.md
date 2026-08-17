# Copilot instructions for webserver_undangan

"CMS tidak boleh kaku. CMS harus dinamis, extensible, dan mampu beradaptasi dengan seluruh kebutuhan preset/tema (seperti DewanaKL, Rainier, dll)."
"Konfigurasi (config.json / config.php) harus memiliki skema yang mendukung variabel universal dan ruang khusus (seperti theme_options) untuk pengaturan spesifik tiap preset."
"Semua frontend harus tunduk pada CMS tanpa ada data hardcode."

## Dynamic & Extensible CMS Rules
1. **Dynamic Configuration**: `config.json` and `config.php` are extensible sources of truth supporting universal variables and preset-specific options in `theme_options`.
2. **Preset Adaptation**: Themes (e.g., DewanaKL, Elix, Rainier, Archak) dynamically read their options from `theme_options.<preset_key>` and universal configuration without hardcoded data.
3. **Data Isolation**: Bride (Mempelai Wanita) and Groom (Mempelai Pria) input fields, file upload handlers (`upload_bride_photo`, `upload_groom_photo`), and target media paths (`media.bride_photo`, `media.groom_photo`) must remain strictly isolated and never swapped or crossed.
4. **No Hardcoded Data**: All frontend rendering must strictly respect CMS configurations.
