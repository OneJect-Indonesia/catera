# Modernisasi UI/UX Tema Perusahaan Kesehatan (Medical/Healthcare)

## Tujuan Utama
Memperbaiki dan memodernisasi UI/UX project ini agar menjadi lebih bersih, ramah pengguna (user-friendly), dan relevan dengan identitas perusahaan kesehatan/medis.

## Panduan Desain & Tema
Kita akan menerapkan **satu tema utama** (tidak perlu ada fungsionalitas dark/light mode toggle). Harap gunakan skema warna yang menyejukkan mata dan memberikan kesan profesional, bersih, serta dapat dipercaya.

Gunakan referensi dari rekomendasi `.agent/skills/ui-ux-pro-max` berikut ini sebagai acuan:

### 1. Palet Warna (Soothing & Calm)
- **Background Utama:** `#F0FDFA` (Memberikan kesan bersih dan tenang)
- **Warna Primer:** `#0891B2` (Medical teal)
- **Warna Sekunder:** `#22D3EE` 
- **Call to Action (CTA):** `#22C55E` (Health green, gunakan untuk tombol utama)
- **Teks Utama:** `#134E4A` (Kontras yang baik untuk keterbacaan)

### 2. Tipografi (Typography)
- **Heading:** `Figtree`
- **Body Text:** `Noto Sans`
- *Catatan: Kombinasi font ini sangat cocok untuk aplikasi medis karena mudah dibaca, inklusif, dan terlihat profesional.*

### 3. Elemen Visual & Efek (Key Effects)
- Hindari penggunaan warna neon yang terlalu mencolok.
- Hindari animasi berlebihan yang mengganggu (motion-heavy).
- Pastikan ukuran area klik (touch target) memadai (minimal 44x44px).
- Gunakan ring fokus (focus rings) yang jelas untuk navigasi keyboard.
- Gunakan ikon SVG yang profesional (misal: Heroicons atau Lucide), hindari penggunaan emoji sebagai ikon.

## Instruksi Teknis (Penting!)
Project ini berjalan menggunakan **Docker**. Oleh karena itu, untuk menjalankan perintah artisan, pastikan Anda menggunakan Sail:
```bash
# Gunakan ini
sail artisan [nama-command]

# BUKAN ini
php artisan [nama-command]
```

## Ekspektasi Pengerjaan
Dokumen ini bersifat arahan umum. Anda diberikan kebebasan untuk **mengeksplorasi** implementasi komponen visual, layout spacing, dan letak elemen secara kreatif selama masih berpedoman pada tema kesehatan di atas. Fokus pada kenyamanan pengguna saat melihat dan menggunakan aplikasi.
