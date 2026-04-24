<div align="center">
    <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel Logo" width="100">
    <h1 align="center">Lunch Management System (Catera)</h1>
    <p align="center">
        A modern enterprise application for managing employee lunch quotas, built with Laravel 12, Livewire 4, and Flux UI.
    </p>
</div>

---

## 📖 Deskripsi Proyek

**Catera (Lunch Management System)** adalah platform manajemen akses kantin berbasis UUID (RFID/NFC). Sistem ini memudahkan pengelolaan data kartu karyawan yang terdeteksi, pemisahan antara data resmi (`Authorized`) dan data anonim (`Unauthorized`), serta fitur penjadwalan penambahan kuota secara otomatis.

Fitur Utama:
- **Authorized & Unauthorized Tracking**: Memantau kartu yang sudah terdaftar maupun yang belum dikenal.
- **Scheduled Quota (Registered)**: Penjadwalan penambahan kuota makan otomatis pada tanggal tertentu.
- **Modern UI**: Antarmuka bersih dengan nuansa medis profesional menggunakan Flux UI dan Tailwind CSS v4.
- **Docker Ready**: Pengembangan yang konsisten dan mudah menggunakan Laravel Sail.

---

## 🏗️ Struktur Database Utama

Berikut adalah gambaran singkat entitas utama dalam sistem:

| Tabel | Deskripsi |
|-------|-----------|
| **Users** | Akun administrator untuk mengelola sistem. |
| **Authorizeds** | Data karyawan yang sudah resmi terdaftar (UUID, Nama, Grup, Kuota saat ini). |
| **Unauthorizeds** | Log UUID kartu yang pernah melakukan tapping namun belum didaftarkan ke sistem. |
| **Registereds** | Daftar antrean (penjadwalan) penambahan kuota yang akan dieksekusi oleh sistem pada tanggal yang ditentukan. |

---

## ⚙️ Persyaratan Sistem

- **Docker Desktop** (Sangat disarankan)
- **Node.js & NPM** (Untuk kompilasi aset frontend)
- **Composer** (Opsional, jika ingin menjalankan diluar Docker)

---

## 🚀 Panduan Instalasi (Development dengan Docker Sail)

Project ini dikembangkan menggunakan **Laravel Sail**, sehingga Anda tidak perlu menginstal PHP atau PostgreSQL secara lokal di komputer Anda.

### 1. Persiapan Awal
Kloning repositori dan masuk ke folder project:
```bash
git clone <url-repo-anda> catera
cd catera
```

### 2. Konfigurasi Environment
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

### 3. Instalasi Dependensi
Jalankan composer melalui Docker (atau lokal jika tersedia) untuk mengunduh Laravel Sail:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Menjalankan Docker (Sail)
Nyalakan kontainer Docker:
```bash
./vendor/bin/sail up -d
```

### 5. Inisialisasi Aplikasi
Setelah kontainer berjalan, lakukan setup database dan key:
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

### 6. Setup Frontend
Install dan jalankan aset frontend (bisa dilakukan di host atau via Sail):
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build # atau npm run dev
```

Aplikasi sekarang dapat diakses di: **http://localhost:81** (Sesuai `APP_PORT` di `.env`).

---

## 🐳 Penggunaan Laravel Sail

Sangat penting untuk diingat bahwa setiap perintah Laravel/Artisan **WAJIB** dijalankan melalui Sail:

- **Migrasi Database**: `./vendor/bin/sail artisan migrate`
- **Menjalankan Test**: `./vendor/bin/sail artisan test`
- **Tinker**: `./vendor/bin/sail artisan tinker`
- **Node Commands**: `./vendor/bin/sail npm run build`

> **Tips**: Anda bisa membuat alias `alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'` di `.bashrc` atau `.zshrc` Anda agar cukup mengetik `sail artisan ...`.

---

## ⏳ Menjalankan Scheduler

Sistem ini bergantung pada Scheduler untuk memproses penambahan kuota otomatis. Di lingkungan lokal, Anda bisa menjalankan:
```bash
./vendor/bin/sail artisan schedule:work
```

---

_Dibuat untuk Manajemen Praktis di Lingkungan F&B / Catera - Enterprise._
