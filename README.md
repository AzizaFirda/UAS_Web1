# Personal Finance Manager

Aplikasi manajemen keuangan pribadi berbasis web untuk mengelola transaksi, budget, dan laporan keuangan.

## 🚀 Live Demo

**URL:** https://pipil.my.id

## 📋 Fitur

- ✅ Autentikasi User (Login/Register)
- ✅ Dashboard Overview
- ✅ Manajemen Transaksi (Income/Expense)
- ✅ Manajemen Akun
- ✅ Manajemen Kategori
- ✅ Statistik & Charts
- ✅ Export Reports (PDF/Excel)
- ✅ Multi-bahasa (ID/EN)
- ✅ Tema Light/Dark

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla), Chart.js
- **Backend:** PHP 7.4+ (Native MVC)
- **Database:** MySQL/MariaDB
- **Server:** LiteSpeed (Anymhost)

## 📂 Struktur Project

```
FinanceManagerWeb/
├── backend/           # Backend API & Logic
│   ├── api/          # REST API endpoints
│   ├── config/       # Database config
│   ├── controllers/  # Business logic
│   ├── middleware/   # Auth middleware
│   └── models/       # Data models
├── frontend/         # Frontend assets
│   ├── assets/       # CSS, JS, Images
│   ├── components/   # Reusable components
│   └── pages/        # HTML pages
├── uploads/          # User uploads
├── docs/             # Dokumentasi
└── database.sql      # Database schema

```

## 📖 Dokumentasi

Dokumentasi lengkap tersedia di folder [`docs/`](docs/):

- [Laporan Bug & Penyelesaian](docs/LAPORAN-BUG-DAN-PENYELESAIAN.md)
- [Deployment Guide](docs/DEPLOYMENT.md)
- [Audit Summary](docs/AUDIT-SUMMARY.md)
- [Project README](docs/README-PROJECT.md)

## 🔐 Database Setup

1. Import `database.sql` ke MySQL
2. Update kredensial di `backend/config/database.php`
3. Jalankan aplikasi

## 👤 Default Login

Setelah import database, gunakan:

- **Email:** user@example.com
- **Password:** password123

## 📦 Installation

### Local Development

```bash
# Clone repository
git clone https://github.com/AzizaFirda/UAS_Web1.git

# Setup database
mysql -u root -p < database.sql

# Configure database
cp backend/config/database.example.php backend/config/database.php
# Edit database.php dengan kredensial Anda

# Run dengan PHP built-in server
php -S localhost:8000
```

### Production Deployment

Lihat [Deployment Guide](docs/DEPLOYMENT.md)

## 🤝 Contributing

Pull requests are welcome!

## 📝 License

Dibuat untuk UAS Web Programming 1

## 👨‍💻 Developer

**Aziza Firda**

- GitHub: [@AzizaFirda](https://github.com/AzizaFirda)
- Email: -

---

**Version:** 1.0.0  
**Last Updated:** 4 Februari 2026
