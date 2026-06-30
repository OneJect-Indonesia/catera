# Issue: Project Scan - Bug Fixes & Improvements

## Backstory

Project Catera (lunch management) sudah hampir selesai. Semua policy dan authorization sudah diimplementasikan dengan benar. Sekarang perlu dilakukan scan menyeluruh untuk menemukan bug, potensi error, dan improvement yang perlu dilakukan sebelum production.

## Hasil Scan

### 🔴 CRITICAL BUGS (Harus Diperbaiki)

#### 1. Missing Gate Import di `authorized/index.blade.php`

**File:** `resources/views/pages/authorized/index.blade.php`

**Problem:** File menggunakan `Gate::authorize()` di banyak tempat (line 57, 103, 132, 151, 170, 182, 203) tetapi **tidak ada import** `use Illuminate\Support\Facades\Gate;` di bagian atas file.

**Impact:** Fatal error `Class "Gate" not found` saat mengakses halaman authorized.

**Fix:**
```diff
 <?php
 
 use App\Models\Authorized;
+use Illuminate\Support\Facades\Gate;
 use Illuminate\Support\Facades\DB;
 use Livewire\Component;
 use Livewire\WithPagination;
```

---

### 🟡 BUGS (Perlu Diperbaiki)

#### 2. env() Usage di AppServiceProvider

**File:** `app/Providers/AppServiceProvider.php` (line 43)

**Problem:** Menggunakan `env('PULSE_ADMIN_EMAIL')` sebagai fallback di dalam method, bukan di config file.

```php
return $user->email === config('app.pulse_admin_email', env('PULSE_ADMIN_EMAIL'));
```

**Laravel Best Practice:** `env()` hanya boleh dipanggil di config files. Di tempat lain harus menggunakan `config()`.

**Fix:**
```diff
-return $user->email === config('app.pulse_admin_email', env('PULSE_ADMIN_EMAIL'));
+return $user->email === config('app.pulse_admin_email');
```

Dan pastikan `config/app.php` sudah memiliki:
```php
'pulse_admin_email' => env('PULSE_ADMIN_EMAIL'),
```

---

#### 3. Validation Rule Inconsistency di registered/index.blade.php

**File:** `resources/views/pages/registered/index.blade.php` (line 197)

**Problem:** Validation rule menggunakan `exists:authorizeds,uuid` tanpa schema prefix.

```php
'addAuthorizedUuid' => ['required', 'string', 'exists:authorizeds,uuid'],
```

**Catatan:** Ini mungkin bekerja karena `search_path` di `config/database.php` sudah include `catera`, tapi untuk konsistensi dan kejelasan lebih baik pakai prefix.

**Fix (Optional tapi Recommended):**
```diff
-'addAuthorizedUuid' => ['required', 'string', 'exists:authorizeds,uuid'],
+'addAuthorizedUuid' => ['required', 'string', 'exists:catera.authorizeds,uuid'],
```

---

### 🔵 IMPROVEMENTS (Performance & Code Quality)

#### 4. Missing Database Indexes di registereds Table

**Problem:** Tabel `registereds` tidak memiliki index pada kolom yang sering digunakan untuk filtering dan sorting.

**Kolom yang perlu index:**
- `authorized_uuid` - Sudah ada foreign key tapi tidak ada explicit index. Digunakan untuk join dengan `authorizeds`.
- `status` - Digunakan untuk filtering tab (pending/success).
- `target_date` - Digunakan untuk ordering.

**Impact:** Query bisa lambat saat data bertambah banyak.

**Fix:** Buat migration baru:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catera.registereds', function (Blueprint $table) {
            $table->index('authorized_uuid');
            $table->index('status');
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::table('catera.registereds', function (Blueprint $table) {
            $table->dropIndex(['authorized_uuid']);
            $table->dropIndex(['status']);
            $table->dropIndex(['target_date']);
        });
    }
};
```

**Command:**
```bash
./vendor/bin/sail artisan make:migration add_indexes_to_registereds_table
```

---

#### 5. Registered Model Missing HasFactory Trait

**File:** `app/Models/Registered.php`

**Problem:** Model `Registered` tidak memiliki `HasFactory` trait, berbeda dengan `Authorized` dan `Unauthorized`.

**Impact:** Tidak bisa menggunakan factory untuk testing atau seeding.

**Fix:**
```diff
 <?php
 
 namespace App\Models;
 
+use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Model;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
 class Registered extends Model
 {
+    /** @use HasFactory<\Database\Factories\RegisteredFactory> */
+    use HasFactory;
+
     protected $table = 'catera.registereds';
```

---

#### 6. No Database Transaction di registered store()

**File:** `resources/views/pages/registered/index.blade.php` (line 192-218)

**Problem:** Method `store()` di registered tidak menggunakan DB transaction, sedangkan `store()` di authorized menggunakan transaction (line 214-224).

**Impact:** Jika terjadi error saat create, data bisa inconsistent.

**Fix:**
```diff
 public function store()
 {
     Gate::authorize('create', Registered::class);
 
     $this->validate([
         'addAuthorizedUuid' => ['required', 'string', 'exists:catera.authorizeds,uuid'],
         'addAddQuota' => ['required', 'integer', 'min:1'],
         'addTargetDate' => ['required', 'date'],
     ]);
 
     try {
-        Registered::create([
-            'authorized_uuid' => $this->addAuthorizedUuid,
-            'add_quota' => $this->addAddQuota,
-            'target_date' => $this->addTargetDate,
-            'status' => 'pending',
-        ]);
+        \Illuminate\Support\Facades\DB::transaction(function () {
+            Registered::create([
+                'authorized_uuid' => $this->addAuthorizedUuid,
+                'add_quota' => $this->addAddQuota,
+                'target_date' => $this->addTargetDate,
+                'status' => 'pending',
+            ]);
+        });
 
         $this->closeAddModal();
```

---

#### 7. Missing Type Hints di scopeActive()

**File:** `app/Models/Authorized.php` (line 32-35)

**Problem:** Method `scopeActive()` tidak memiliki type hint untuk parameter `$query`.

**Fix:**
```diff
-public function scopeActive($query)
+public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
 {
     return $query->where('is_active', true);
 }
```

---

#### 8. Generic Error Handling

**Problem:** Semua catch block hanya menampilkan generic error message tanpa logging exception yang sebenarnya.

**Impact:** Sulit untuk debugging saat terjadi error di production.

**Contoh di `authorized/index.blade.php` line 175:**
```php
} catch (\Exception $e) {
    $this->dispatch('notify', message: 'Failed to delete authorized record.', variant: 'danger');
}
```

**Recommended Fix (untuk semua catch blocks):**
```diff
 } catch (\Exception $e) {
+    \Illuminate\Support\Facades\Log::error('Failed to delete authorized record', [
+        'error' => $e->getMessage(),
+        'authorized_id' => $this->deletingAuthorizedId,
+    ]);
     $this->dispatch('notify', message: 'Failed to delete authorized record.', variant: 'danger');
 }
```

**Lokasi yang perlu ditambahkan logging:**
- `authorized/index.blade.php`: line 142, 175, 230
- `registered/index.blade.php`: line 138, 171, 215
- `unauthorized/index.blade.php`: line 59

---

## Summary Prioritas

### Must Fix (Sebelum Deploy)
1. ✅ **Missing Gate import** di authorized/index.blade.php
2. ✅ **env() usage** di AppServiceProvider

### Should Fix (Performance & Consistency)
3. ✅ **Missing indexes** di registereds table
4. ✅ **DB transaction** di registered store()
5. ✅ **Validation rule prefix** di registered (optional tapi recommended)

### Nice to Have (Code Quality)
6. ✅ **HasFactory trait** di Registered model
7. ✅ **Type hints** di scopeActive()
8. ✅ **Error logging** di semua catch blocks

---

## Verification Steps

Setelah semua fix diimplementasikan:

1. **Run tests:**
```bash
./vendor/bin/sail artisan test --compact
```

2. **Run Pint:**
```bash
./vendor/bin/sail artisan pint
```

3. **Manual testing:**
   - Akses halaman `/authorized` → harus tidak error
   - Coba create, edit, delete di semua halaman
   - Cek log file untuk memastikan error logging bekerja

4. **Database check:**
```bash
./vendor/bin/sail artisan migrate:status
```

---

## Notes

- Gunakan `./vendor/bin/sail artisan` untuk semua command (Docker/Sail)
- Semua perubahan sudah mempertimbangkan schema `catera` dan `portal_application`
- Search path di database config: `catera,portal_application,public`
- Jangan ubah dependency atau struktur folder tanpa approval
