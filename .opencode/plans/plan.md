# Plan: Refactor Quota Scheduling dan Fitur Registration Baru

## 1. Pahami Maksud dan Flow Project
Project "Catera" mengelola sistem manajemen makan siang (lunch management). Terdapat fungsionalitas manajemen akses dengan kartu RFID (UUID), yang direpresentasikan dalam tabel `authorizeds`. User yang memiliki hak akses ini di-link dengan tabel `md_users` (portal application). Sebelumnya, sistem menggunakan konsep `unauthorizeds` untuk menangkap kartu asing (RFID taps yang belum dikenali) untuk kemudian didaftarkan. Fitur penjadwalan penambahan kuota makan siang disebut `registereds`.

## 2. Problem & Solusi

### A. Penamaan Fitur Quota Scheduling Membingungkan
**Masalah:** Penjadwalan kuota harian/bulanan ("Quota Scheduling") saat ini menggunakan nama "Registered". Ini sangat membingungkan dengan konsep pendaftaran/register user baru.
**Solusi:**
1. Rename model `Registered` menjadi `QuotaSchedule` (atau `QuotaScheduling`).
2. Rename tabel dari `registereds` ke `quota_schedules`.
3. Update relasi `authorizeds` (dari `registered()` ke `quotaSchedule()`).
4. Update class Policy (`RegisteredPolicy` -> `QuotaSchedulePolicy`), console commands, dan views Livewire (`resources/views/pages/registered/index.blade.php` dipindah ke `resources/views/pages/quota_schedule/index.blade.php`).
5. Update file terjemahan, routes (`/registereds` menjadi `/quota-schedules`), dan menu di sidebar.

### B. Fitur Register User Baru: Tap RFID & NIK Langsung ke Form
**Masalah:** Ingin menggabungkan flow saat kartu di-tap ke tablet, RFID value langsung ter-fill di form register, didampingi dengan input NIK untuk mencari eksisting user di portal. Konsep tabel `unauthorizeds` menjadi tidak relevan.
**Solusi:**
1. Karena RFID device biasanya bertindak seperti keyboard scanner, saat admin berada di modal/halaman "Register New Authorized", men-tap kartu akan langsung mengisi field `rfid-value` (`uuid`).
2. Pada form tersebut, admin mengisi NIK. Ketika NIK dimasukkan, akan ada pencarian ke `portal_application.md_users` (Livewire auto-complete/searchable select seperti yang sudah ada) untuk me-link user tersebut.
3. Hapus fungsionalitas `unauthorizeds` dari codebase:
   - Drop tabel `catera.unauthorizeds`.
   - Hapus model `Unauthorized`.
   - Hapus `UnauthorizedPolicy`, controller, view `unauthorized/index.blade.php`, dan scheduler job `DeletUnauthorizeds`.
   - Hapus menu "Unauthorized" dari sidebar.
4. Update `resources/views/pages/authorized/index.blade.php` pada bagian "Add Modal":
   - Ubah `addUuid` dari dropdown pilihan tabel `unauthorizeds` menjadi input text biasa yang bisa langsung auto-fill dari tap RFID (keyboard input).
   - Pastikan focus state berjalan baik untuk mempermudah tap scanner (scanner input di field `addUuid` lalu lanjut ke field `addUserId`).

### C. Best Practice Flow: Pending (Registering) ke Authorized
**Masalah:** Bagaimana best practice merubah status data dari sedang didaftarkan menjadi aktif di fitur `authorized`. Berdasarkan jawaban "Tap langsung ke form", user baru akan masuk langsung melalui proses registrasi form tersebut.

**Solusi Best Practice Flow:**
1. Tambahkan kolom status `approval_status` (enum/string: `pending`, `approved`, `rejected`) di tabel `authorizeds`. Status default adalah `approved` untuk admin yang bisa direct approve, atau `pending` jika ada flow review terpisah (opsional).
2. Jika butuh alur "Didaftarkan (Pending)" -> "Aktif":
   - Form Add Authorized di halaman "Registration" / "Authorized" menyimpan data dengan status awal `pending` dan `is_active = false`.
   - Admin berwenang membuka halaman Authorized, melihat data berstatus `pending`.
   - Klik aksi "Approve", status berubah menjadi `approved` dan `is_active = true`.
3. Namun, sesuai jawaban klarifikasi "Tap langsung ke form", flow bisa jauh lebih sederhana:
   - Admin menekan tombol "Add Authorized" -> Muncul modal.
   - Admin klik/focus pada field "UUID/RFID". Scanner kartu di-tap -> Field "UUID" terisi otomatis oleh hardware scanner.
   - Admin ketik NIK -> Pilih User -> Pilih Group -> Isi Quota -> Submit.
   - Data tersimpan dan record langsung aktif di tabel `authorizeds`. Tidak perlu staging table.

## Ringkasan Eksekusi (Action Items)
1. **Migration (Rename & Cleanup):**
   - Buat migration merename tabel `registereds` ke `quota_schedules`.
   - Buat migration untuk DROP tabel `unauthorizeds`.
2. **Refactor Code (Quota Schedule):**
   - Rename Model, Policy, Controller/Livewire, Views, Routes, Sidebar dari `Registered` menjadi `QuotaSchedule`.
3. **Refactor Code (Unauthorized & Registration):**
   - Hapus model, policy, controller, seeder, scheduler untuk `Unauthorized`.
   - Update `pages/authorized/index.blade.php` (Add Modal) -> Field `addUuid` jadi text input (fokus otomatis ke input ini bila modal dibuka bisa membantu scanner).
   - Hapus referensi `Unauthorized` di sidebar, route, dashboard stats.
4. **Testing:**
   - Run linter/pint.
   - Pastikan halaman Authorized bisa dibuka tanpa error missing `unauthorizeds`.
   - Pastikan halaman Quota Schedule berjalan normal dengan nama baru.