# Laporan Bug dan Penyelesaian

## Personal Finance Manager - Deployment ke pipil.my.id

**Tanggal:** 4 Februari 2026  
**Domain:** https://pipil.my.id  
**Repository:** https://github.com/AzizaFirda/UAS_Web1.git  
**Hosting:** Anymhost (LiteSpeed)

---

## 📋 Daftar Isi

1. [Bug #1: Session Tidak Persisten](#bug-1-session-tidak-persisten)
2. [Bug #2: CORS Policy Error](#bug-2-cors-policy-error)
3. [Bug #3: Hardcoded API Paths](#bug-3-hardcoded-api-paths)
4. [Bug #4: Domain Redirect Salah](#bug-4-domain-redirect-salah)
5. [Bug #5: Missing Credentials di Login/Register](#bug-5-missing-credentials-di-loginregister)
6. [Bug #6: API_URL Path Salah di Production](#bug-6-api_url-path-salah-di-production)
7. [Ringkasan Perubahan File](#ringkasan-perubahan-file)

---

## Bug #1: Session Tidak Persisten

### 🔴 Gejala

- User berhasil login tapi saat klik menu (Transaksi, Statistik, dll) langsung redirect ke login page
- Session ID berubah setiap request
- Auth check selalu gagal walaupun sudah login

### 🔍 Penyebab

1. **Session Regeneration Berlebihan**
   - File: `backend/middleware/AuthMiddleware.php`
   - Fungsi `login()` memanggil `session_regenerate_id(true)` yang membuat session ID baru
   - Session baru tidak memiliki data user dari session lama

2. **Cookie Domain Mismatch**
   - Session cookie menggunakan parameter `domain` yang tidak cocok dengan hosting
   - Cookie tidak terkirim ke server karena domain mismatch

### ✅ Solusi

**File:** `backend/middleware/AuthMiddleware.php`

```php
// SEBELUM (BERMASALAH):
public static function login($userId) {
    session_regenerate_id(true); // ❌ Membuat session baru, data hilang
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
    session_write_close();
}

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'], // ❌ Domain mismatch di hosting
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// SESUDAH (FIXED):
public static function login($userId) {
    // ✅ Hapus session_regenerate_id()
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    session_write_close();
}

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    // ✅ Hapus parameter 'domain', biarkan browser yang tentukan
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

**Commit:** `Fix session persistence - remove regeneration and domain param`

---

## Bug #2: CORS Policy Error

### 🔴 Gejala

```
Access to fetch at 'https://pipil.my.id/backend/api/auth.php' from origin 'https://pipil.my.id'
has been blocked by CORS policy: The value of the 'Access-Control-Allow-Origin' header
must not be the wildcard '*' when the request's credentials mode is 'include'.
```

### 🔍 Penyebab

1. **CORS Wildcard dengan Credentials**
   - Header `Access-Control-Allow-Origin: *` tidak kompatibel dengan `credentials: 'include'`
   - Browser memblokir request yang menggunakan credentials dengan wildcard origin

2. **Header CORS Tidak Konsisten**
   - Setiap file API set header sendiri-sendiri
   - Ada yang pakai wildcard `*`, ada yang pakai origin spesifik
   - Tidak ada fungsi terpusat untuk CORS

### ✅ Solusi

**File:** `backend/config/database.php`

Tambahkan fungsi `setCORSHeaders()`:

```php
function setCORSHeaders() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    // Allow specific origin or localhost
    if ($origin) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
```

**Update Semua File API** (9 files):

- `backend/api/auth.php`
- `backend/api/accounts.php`
- `backend/api/budgets.php`
- `backend/api/categories.php`
- `backend/api/dashboard.php`
- `backend/api/reports.php`
- `backend/api/statistics.php`
- `backend/api/transactions.php`
- `backend/api/users.php`

```php
// Di awal setiap file API, ganti:
header("Access-Control-Allow-Origin: *");
// Dengan:
setCORSHeaders();
```

**Commit:** `Add centralized CORS headers function with credentials support`

---

## Bug #3: Hardcoded API Paths

### 🔴 Gejala

- Di localhost berfungsi normal
- Di production (pipil.my.id) semua request transaksi error 404
- Console menunjukkan request ke `/backend/api/transactions.php` (salah, seharusnya ada prefix)

### 🔍 Penyebab

**File:** `frontend/assets/js/transactions.js`

Hardcoded path tidak memperhitungkan base path yang berbeda:

```javascript
// ❌ BERMASALAH - Hardcoded path
fetch("/backend/api/transactions.php?action=list");
fetch("/backend/api/accounts.php?action=list");
fetch("/backend/api/categories.php?action=list&type=income");
```

### ✅ Solusi

Ganti semua hardcoded path dengan dynamic `APP_CONFIG.API_URL`:

```javascript
// ✅ FIXED - Dynamic API URL
fetch(`${APP_CONFIG.API_URL}/transactions.php?action=list`, {
  credentials: "include",
});

fetch(`${APP_CONFIG.API_URL}/accounts.php?action=list`, {
  credentials: "include",
});

fetch(`${APP_CONFIG.API_URL}/categories.php?action=list&type=income`, {
  credentials: "include",
});
```

**Total Perubahan:** 5 fetch calls di transactions.js

**Commit:** `Replace hardcoded API paths with dynamic APP_CONFIG.API_URL`

---

## Bug #4: Domain Redirect Salah

### 🔴 Gejala

- Akses https://pipil.my.id menampilkan website SMP Darussalam (bukan Finance Manager)
- Finance Manager hanya bisa diakses via https://pipil.my.id/frontend/pages/login.html

### 🔍 Penyebab

- Default document root (`public_html/index.html`) mengarah ke project lain
- Tidak ada redirect otomatis ke Finance Manager
- Structure file: `public_html/` berisi multiple projects

### ✅ Solusi

**File:** `public_html/index.html` (root level)

```html
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="refresh" content="0;url=/frontend/pages/login.html" />
    <title>Redirecting...</title>
  </head>
  <body>
    <p>Redirecting to Finance Manager...</p>
    <script>
      window.location.href = "/frontend/pages/login.html";
    </script>
  </body>
</html>
```

**File:** `public_html/.htaccess`

```apache
DirectoryIndex index.html index.php

# Redirect root to Finance Manager
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ /frontend/pages/login.html [L,R=302]
</IfModule>
```

**Commit:** `Add root redirect to Finance Manager login page`

---

## Bug #5: Missing Credentials di Login/Register

### 🔴 Gejala

- Login berhasil di server (response 200) tapi session tidak tersimpan di browser
- Register berhasil create user tapi tidak auto-login
- Setelah login, refresh page kembali ke login page

### 🔍 Penyebab

**Files:** `frontend/pages/login.html`, `frontend/pages/register.html`

Fetch call tidak mengirim `credentials: 'include'`:

```javascript
// ❌ BERMASALAH
fetch("/backend/api/auth.php?action=login", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ email, password }),
});
```

### ✅ Solusi

Tambahkan `credentials: 'include'` di semua auth fetch calls:

```javascript
// ✅ FIXED - Login
fetch(`${APP_CONFIG.API_URL}/auth.php?action=login`, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  credentials: "include", // ← Tambahkan ini
  body: JSON.stringify({ email, password }),
});

// ✅ FIXED - Register
fetch(`${APP_CONFIG.API_URL}/auth.php?action=register`, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  credentials: "include", // ← Tambahkan ini
  body: JSON.stringify(formData),
});
```

**Bonus Fix:** Ganti relative paths dengan absolute paths:

```javascript
// Image paths
<img src="/frontend/assets/img/pict1.png" />;

// Redirect after login
window.location.href = "/frontend/pages/dashboard.html";
```

**Commit:** `Add credentials to login/register and fix absolute paths`

---

## Bug #6: API_URL Path Salah di Production

### 🔴 Gejala

- Console log menunjukkan: `APP_CONFIG.API_URL: /frontend/backend/api` ❌
- Seharusnya: `APP_CONFIG.API_URL: /backend/api` ✅
- Semua API calls ke statistics dan transactions return 404
- Error: `GET https://pipil.my.id/frontend/backend/api/statistics.php 404 (Not Found)`

### 🔍 Penyebab

**File:** `frontend/assets/js/app.js`

Logic penentuan `basePath` tidak membedakan localhost vs production:

```javascript
// ❌ BERMASALAH
const frontendIndex = pathParts.indexOf("frontend"); // = 0
console.log("frontendIndex:", frontendIndex); // Output: 0

// Karena frontendIndex = 0, maka:
if (frontendIndex > 0) {
  // Tidak masuk sini (karena 0 bukan > 0)
} else {
  // Masuk sini
  basePath = "/" + pathParts.slice(0, frontendIndex).join("/");
  // basePath = "/" + [].join("/") = "/" ← SALAH!
  // Karena slice(0, 0) = array kosong
}
```

**Masalah Sebenarnya:** Ada kode lama yang belum ter-update di production (browser cache).

### ✅ Solusi

**Step 1:** Fix logic di `app.js`:

```javascript
// ✅ FIXED
const frontendIndex = pathParts.indexOf("frontend");
console.log("frontendIndex:", frontendIndex);

// Bedakan antara localhost (nested structure) vs production (root level)
if (frontendIndex > 0 && window.location.hostname === "localhost") {
  // Localhost development dengan nested path
  // Contoh: localhost/FinanceManagerWeb/frontend/pages/login.html
  basePath = "/" + pathParts.slice(0, frontendIndex).join("/");
  apiUrl = basePath + "/backend/api";
} else {
  // Production: selalu gunakan /backend/api dari root
  // pipil.my.id/frontend/pages/login.html → /backend/api
  basePath = "";
  apiUrl = "/backend/api";
}

console.log("basePath:", basePath);
console.log("apiUrl:", apiUrl);
```

**Step 2:** Deploy fix:

```bash
git add frontend/assets/js/app.js
git commit -m "Fix: Force /backend/api path for production environment"
git push origin main
```

**Step 3:** Pull di server:

```bash
ssh rdyaazzw@pipil.my.id
cd ~/public_html
git pull origin main
```

**Step 4:** Clear browser cache dengan **Hard Refresh**:

- Tekan `Ctrl + Shift + R` atau `Ctrl + F5`
- Atau: DevTools → Right-click Refresh → "Empty Cache and Hard Reload"

**Hasil Setelah Fix:**

```
✅ currentPath: /frontend/pages/statistics.html
✅ pathParts: ["frontend", "pages", "statistics.html"]
✅ frontendIndex: 0
✅ basePath: (empty string)
✅ apiUrl: /backend/api
✅ APP_CONFIG.API_URL: /backend/api
```

**Commit:** `Fix: Force /backend/api path for production environment`

---

## 📊 Ringkasan Perubahan File

### Backend Files

| File                                    | Perubahan                                                                       | Alasan                      |
| --------------------------------------- | ------------------------------------------------------------------------------- | --------------------------- |
| `backend/middleware/AuthMiddleware.php` | - Hapus `session_regenerate_id()`<br>- Hapus `domain` param<br>- Tambah logging | Session tidak persisten     |
| `backend/config/database.php`           | Tambah fungsi `setCORSHeaders()`                                                | Centralized CORS management |
| `backend/api/*.php` (9 files)           | Ganti header manual dengan `setCORSHeaders()`                                   | CORS credentials support    |

### Frontend Files

| File                                 | Perubahan                                                                     | Alasan                |
| ------------------------------------ | ----------------------------------------------------------------------------- | --------------------- |
| `frontend/assets/js/app.js`          | - Fix API_URL calculation<br>- Tambah hostname check                          | Production path salah |
| `frontend/assets/js/transactions.js` | - Ganti hardcoded paths<br>- Tambah `credentials: 'include'`                  | 404 di production     |
| `frontend/pages/login.html`          | - Tambah `credentials: 'include'`<br>- Fix image paths<br>- Absolute redirect | Session & paths       |
| `frontend/pages/register.html`       | - Tambah `credentials: 'include'`<br>- Fix image paths<br>- Absolute redirect | Session & paths       |

### Configuration Files

| File                     | Perubahan                           | Alasan          |
| ------------------------ | ----------------------------------- | --------------- |
| `public_html/.htaccess`  | Tambah DirectoryIndex & RewriteRule | Domain redirect |
| `public_html/index.html` | Redirect HTML & JS ke login page    | Domain redirect |

---

## 🎯 Lessons Learned

### 1. Session Management

- ❌ **Jangan** regenerate session ID di setiap login
- ❌ **Jangan** set cookie domain secara manual kecuali multi-subdomain
- ✅ **Gunakan** session default behavior untuk single domain
- ✅ **Tambahkan** logging untuk debugging session

### 2. CORS Configuration

- ❌ **Jangan** pakai wildcard `*` dengan credentials
- ❌ **Jangan** set CORS header berbeda di setiap file
- ✅ **Gunakan** fungsi terpusat untuk consistency
- ✅ **Support** dynamic origin detection

### 3. API Path Management

- ❌ **Jangan** hardcode absolute paths
- ❌ **Jangan** assume struktur folder sama di semua environment
- ✅ **Gunakan** dynamic configuration (APP_CONFIG)
- ✅ **Deteksi** environment (localhost vs production)

### 4. Credentials & Authentication

- ✅ **Selalu** tambahkan `credentials: 'include'` untuk session-based auth
- ✅ **Gunakan** `httponly: true` untuk security
- ✅ **Set** `SameSite: Lax` untuk CSRF protection

### 5. Deployment Best Practices

- ✅ **Test** di localhost sebelum deploy
- ✅ **Hard refresh** setelah deploy untuk clear cache
- ✅ **Check** console log untuk debugging
- ✅ **Commit** dengan pesan yang jelas

---

## ✅ Status Akhir

### Working Features

- ✅ Login & Register
- ✅ Session Persistence
- ✅ Dashboard
- ✅ Transaksi (CRUD)
- ✅ Statistik (Charts & Reports)
- ✅ Akun Management
- ✅ Kategori Management
- ✅ Pengaturan

### Environment

- ✅ Production: https://pipil.my.id
- ✅ Repository: https://github.com/AzizaFirda/UAS_Web1.git
- ✅ SSL: Valid (Let's Encrypt)
- ✅ Server: LiteSpeed
- ✅ Database: rdyaazzw_db_finance

---

## 📞 Troubleshooting Guide

Jika mengalami masalah setelah deployment:

1. **Session tidak tersimpan**
   - Cek: Browser console untuk error CORS
   - Solusi: Pastikan `credentials: 'include'` di semua fetch

2. **404 Not Found di API**
   - Cek: Console log `APP_CONFIG.API_URL`
   - Solusi: Hard refresh (Ctrl+Shift+R)

3. **Redirect ke login terus**
   - Cek: Network tab → Response cookies
   - Solusi: Pastikan CORS allow credentials

4. **Blank page setelah login**
   - Cek: Console errors
   - Solusi: Pastikan semua assets path absolute

---

**Dokumentasi dibuat:** 4 Februari 2026  
**Total Bugs Fixed:** 6  
**Total Files Modified:** 15+  
**Total Commits:** 8  
**Status:** ✅ Production Ready
