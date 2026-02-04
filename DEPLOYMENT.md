# 🚀 Panduan Deployment ke pipil.my.id

## 📋 Checklist Persiapan

- [x] Project sudah di push ke GitHub
- [ ] Akses SSH ke hosting Anymhost
- [ ] Database MySQL sudah dibuat di hosting
- [ ] Domain pipil.my.id sudah pointing ke hosting

---

## 🔧 LANGKAH 1: Koneksi SSH ke Hosting

```bash
# Login SSH (ganti dengan kredensial Anda)
ssh username@pipil.my.id
# atau
ssh username@server-ip-address
```

---

## 🗑️ LANGKAH 2: Hapus Project Lama (PENTING!)

```bash
# Masuk ke direktori public_html atau www
cd public_html
# atau
cd www
# atau sesuai struktur hosting Anda

# Backup dulu (opsional, untuk jaga-jaga)
tar -czf backup-old-project-$(date +%Y%m%d).tar.gz *

# Hapus semua file lama KECUALI database.php (kalau mau simpan kredensialnya)
# Atau hapus semua
rm -rf *
rm -rf .htaccess  # hapus hidden files juga

# Cek bersih
ls -la
```

---

## 📦 LANGKAH 3: Clone dari GitHub

```bash
# Clone repository (pastikan masih di public_html/www)
git clone https://github.com/AzizaFirda/UAS_Web1.git .

# Tanda titik (.) penting! agar clone langsung ke folder current,
# bukan buat subfolder baru

# Atau jika mau ke subfolder:
# git clone https://github.com/AzizaFirda/UAS_Web1.git finance-manager

# Cek hasil clone
ls -la
```

---

## 🗄️ LANGKAH 4: Setup Database

### A. Buat Database di cPanel/phpMyAdmin

1. Login ke cPanel hosting
2. Buka **MySQL Database Wizard** atau **phpMyAdmin**
3. Buat database baru: `namauser_finance` (sesuaikan prefix hosting)
4. Buat user database dengan password kuat
5. Assign user ke database dengan ALL PRIVILEGES
6. **CATAT:** Database name, username, password

### B. Import SQL

```bash
# Via SSH (jika ada akses mysql command)
mysql -u username -p database_name < database.sql

# Atau via phpMyAdmin:
# 1. Buka phpMyAdmin
# 2. Pilih database yang baru dibuat
# 3. Tab "Import"
# 4. Choose file: database.sql
# 5. Click "Go"
```

---

## ⚙️ LANGKAH 5: Konfigurasi Database

```bash
# Copy template database config
cd backend/config
cp database.example.php database.php

# Edit database.php
nano database.php
# atau
vi database.php
```

**Edit bagian production:**

```php
} else {
    // Production (hosting)
    $this->host = 'localhost';  // biasanya localhost
    $this->db_name = 'namauser_finance';  // GANTI dengan database name
    $this->username = 'namauser_dbuser';  // GANTI dengan database user
    $this->password = 'PASSWORD_KUAT_ANDA';  // GANTI dengan password
}
```

**Simpan:**

- Nano: `Ctrl + X`, tekan `Y`, tekan `Enter`
- Vi: tekan `ESC`, ketik `:wq`, tekan `Enter`

---

## 🔒 LANGKAH 6: Set Permission Folder Upload

```bash
# Buat folder upload jika belum ada
mkdir -p uploads/profile
mkdir -p uploads/profile_photos

# Set permission (755 untuk folder, 644 untuk file)
chmod 755 uploads
chmod 755 uploads/profile
chmod 755 uploads/profile_photos

# Set permission untuk .htaccess
chmod 644 uploads/.htaccess
chmod 644 uploads/profile/.htaccess
chmod 644 uploads/profile_photos/.htaccess

# Jika server butuh 777 (tidak recommended, tapi kadang perlu)
# chmod 777 uploads
# chmod 777 uploads/profile
# chmod 777 uploads/profile_photos
```

---

## 🌐 LANGKAH 7: Setup .htaccess (Opsional)

Buat `.htaccess` di root jika perlu rewrite rules:

```bash
nano .htaccess
```

Isi:

```apache
# Enable Rewrite Engine
RewriteEngine On

# Force HTTPS (opsional, jika sudah ada SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent directory listing
Options -Indexes

# Protect config files
<FilesMatch "^(database\.php|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# PHP settings (opsional)
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
```

---

## ✅ LANGKAH 8: Test & Verifikasi

### A. Test Database Connection

```bash
# Akses test script (jika ada)
curl http://pipil.my.id/test-db-connection.php
```

### B. Test di Browser

1. **Homepage**: `http://pipil.my.id/`
2. **Register**: Buat akun baru
3. **Login**: Test login
4. **Dashboard**: Cek apakah dashboard load
5. **Transaksi**: Test tambah transaksi
6. **Upload**: Test upload foto profil

---

## 🔄 LANGKAH 9: Update di Masa Depan (Sangat Mudah!)

```bash
# SSH ke hosting
ssh username@pipil.my.id

# Masuk ke direktori project
cd public_html

# Pull perubahan terbaru dari GitHub
git pull origin main

# Selesai! 🎉
```

---

## 🚨 Troubleshooting

### Problem: Permission Denied

```bash
# Cek ownership
ls -la

# Change owner (ganti 'username' dengan user hosting Anda)
chown -R username:username .
```

### Problem: Database Connection Failed

```bash
# Test MySQL connection
mysql -u username -p -h localhost database_name

# Cek database.php kredensial
cat backend/config/database.php
```

### Problem: 500 Internal Server Error

```bash
# Cek error log
tail -f ~/logs/error_log
# atau
tail -f /var/log/apache2/error.log

# Cek PHP version
php -v

# Project ini butuh PHP 7.4+ dengan PDO extension
```

### Problem: Upload tidak bisa

```bash
# Set permission 777 (temporary)
chmod -R 777 uploads/

# Cek PHP upload settings
php -i | grep upload

# Edit .htaccess atau php.ini jika perlu
```

---

## 🎯 Best Practices

### 1. **Jangan Commit database.php**

✅ Sudah di .gitignore, aman!

### 2. **Update Rutin**

```bash
# Di local
git add .
git commit -m "Update feature X"
git push

# Di hosting
git pull
```

### 3. **Backup Database**

```bash
# Di hosting, buat cron job untuk backup
mysqldump -u username -p database_name > backup-$(date +%Y%m%d).sql
```

### 4. **Monitor Error Logs**

```bash
tail -f ~/logs/error_log
```

---

## 📞 Support

Jika ada masalah:

1. Cek error log di hosting
2. Test database connection
3. Pastikan PHP version >= 7.4
4. Cek permission folders

---

## ✨ Keuntungan Metode Git Clone

✅ Update cukup `git pull`  
✅ Rollback mudah jika ada bug  
✅ History lengkap semua perubahan  
✅ Collaboration mudah dengan tim  
✅ Consistent deployment  
✅ Tidak perlu upload manual lagi

---

**🎉 Selamat! Project Anda sudah production-ready!**

Domain: https://pipil.my.id
Repo: https://github.com/AzizaFirda/UAS_Web1.git
