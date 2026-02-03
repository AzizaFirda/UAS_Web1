// Multi-language Translation System
const translations = {
    id: {
        // Navbar & Navigation
        dashboard: "Dashboard",
        transactions: "Transaksi",
        statistics: "Statistik",
        accounts: "Akun",
        settings: "Pengaturan",
        logout: "Logout",
        
        // Dashboard
        dashboardTitle: "Dashboard",
        financialSummary: "Ringkasan Keuangan",
        totalIncome: "Total Pemasukan",
        totalExpense: "Total Pengeluaran",
        totalSavings: "Total Tabungan",
        recentTransactions: "Transaksi Terbaru",
        transactionCount: "Transaksi",
        noTransactions: "Tidak ada transaksi",
        addTransaction: "Tambah Transaksi",
        incomeThisMonth: "Pemasukan Bulan Ini",
        expenseThisMonth: "Pengeluaran Bulan Ini",
        expenseByCategory: "Pengeluaran per Kategori",
        
        // Transactions Page
        transactionPageTitle: "Transaksi",
        filters: "Filter",
        allTypes: "Semua Tipe",
        income: "Pemasukan",
        expense: "Pengeluaran",
        transfer: "Transfer",
        allAccounts: "Semua Akun",
        fromDate: "Dari Tanggal",
        toDate: "Sampai Tanggal",
        filterBtn: "Filter",
        resetBtn: "Reset",
        transactionList: "Daftar Transaksi",
        amount: "Jumlah",
        date: "Tanggal",
        category: "Kategori",
        account: "Akun",
        notes: "Catatan",
        save: "Simpan",
        cancel: "Batal",
        addNewTransaction: "Tambah Transaksi Baru",
        editTransaction: "Edit Transaksi",
        selectCategory: "Pilih Kategori",
        selectAccount: "Pilih Akun",
        optional: "Opsional",
        fromAccount: "Dari Akun",
        toAccount: "Ke Akun",
        
        // Statistics Page
        statisticsTitle: "Statistik",
        incomeExpenseChart: "Grafik Pemasukan & Pengeluaran",
        categoryBreakdown: "Rincian Kategori",
        period: "Periode",
        monthly: "Bulanan",
        yearly: "Tahunan",
        
        // Accounts Page
        accountsTitle: "Akun",
        totalAssets: "Total Aset",
        totalLiabilities: "Total Liabilitas",
        netWorth: "Nilai Bersih",
        assets: "Aset",
        liabilities: "Liabilitas",
        addAccount: "Tambah Akun",
        editAccount: "Edit Akun",
        selectIcon: "Pilih Ikon",
        selectColor: "Pilih Warna",
        cashAccounts: "Kas & Tunai",
        bankAccounts: "Rekening Bank",
        ewalletAccounts: "E-Wallet",
        debtAccounts: "Hutang",
        accountName: "Nama Akun",
        accountType: "Tipe Akun",
        initialBalance: "Saldo Awal",
        
        // Settings Page
        settingsTitle: "Pengaturan",
        profileSettings: "Pengaturan Profil",
        basicSettings: "Pengaturan Dasar",
        language: "Bahasa",
        selectLanguage: "Pilih Bahasa",
        languageSettings: "Pengaturan Bahasa",
        languageInfoText: "Pilih bahasa untuk mengubah seluruh tampilan website",
        changePhoto: "Ubah Foto",
        changePassword: "Ubah Password",
        newPassword: "Password Baru",
        confirmPassword: "Konfirmasi Password",
        updatePassword: "Ubah Password",
        email: "Email",
        fullName: "Nama Lengkap",
        theme: "Tema",
        lightTheme: "Light",
        darkTheme: "Dark",
        currency: "Mata Uang",
        currencyIDR: "IDR - Rupiah",
        currencyUSD: "USD - US Dollar",
        currencyEUR: "EUR - Euro",
        currencySGD: "SGD - Singapore Dollar",
        dateFormat: "Format Tanggal",
        dateFormat1: "DD/MM/YYYY",
        dateFormat2: "MM/DD/YYYY",
        dateFormat3: "YYYY-MM-DD",
        saveProfile: "Simpan Profil",
        saveSettings: "Simpan Pengaturan",
        
        // Month names
        month: "Bulan",
        year: "Tahun",
        january: "Januari",
        february: "Februari",
        march: "Maret",
        april: "April",
        may: "Mei",
        june: "Juni",
        july: "Juli",
        august: "Agustus",
        september: "September",
        october: "Oktober",
        november: "November",
        december: "Desember",
        
        // Chart & Reports
        refresh: "Refresh",
        exportPdf: "Export PDF",
        exportExcel: "Export Excel",
        balance: "Saldo",
        incomeExpenseChart: "Grafik Pemasukan & Pengeluaran",
        
        // Buttons & Actions
        add: "Tambah",
        edit: "Edit",
        delete: "Hapus",
        search: "Cari",
        export: "Ekspor",
        import: "Impor",
        close: "Tutup",
        
        // Messages
        loadingData: "Memuat data...",
        noData: "Tidak ada data",
        success: "Berhasil",
        error: "Error",
        warning: "Peringatan",
        confirm: "Konfirmasi",
        confirmDelete: "Apakah Anda yakin ingin menghapus item ini?",
        
        // Footer
        copyright: "Hak Cipta",
        allRightsReserved: "Semua hak dilindungi.",
        createdBy: "Dibuat oleh",
        
        // Auth Pages
        login: "Masuk",
        register: "Daftar",
        forgotPassword: "Lupa Kata Sandi?",
        dontHaveAccount: "Belum punya akun?",
        alreadyHaveAccount: "Sudah punya akun?",
        password: "Kata Sandi",
        rememberMe: "Ingat Saya"
    },
    en: {
        // Navbar & Navigation
        dashboard: "Dashboard",
        transactions: "Transactions",
        statistics: "Statistics",
        accounts: "Accounts",
        settings: "Settings",
        logout: "Logout",
        
        // Dashboard
        dashboardTitle: "Dashboard",
        financialSummary: "Financial Summary",
        totalIncome: "Total Income",
        totalExpense: "Total Expense",
        totalSavings: "Total Savings",
        recentTransactions: "Recent Transactions",
        transactionCount: "Transactions",
        noTransactions: "No transactions",
        addTransaction: "Add Transaction",
        incomeThisMonth: "Income This Month",
        expenseThisMonth: "Expense This Month",
        expenseByCategory: "Expense by Category",
        
        // Transactions Page
        transactionPageTitle: "Transactions",
        filters: "Filters",
        allTypes: "All Types",
        income: "Income",
        expense: "Expense",
        transfer: "Transfer",
        allAccounts: "All Accounts",
        fromDate: "From Date",
        toDate: "To Date",
        filterBtn: "Filter",
        resetBtn: "Reset",
        transactionList: "Transaction List",
        amount: "Amount",
        date: "Date",
        category: "Category",
        account: "Account",
        notes: "Notes",
        save: "Save",
        cancel: "Cancel",
        addNewTransaction: "Add New Transaction",
        editTransaction: "Edit Transaction",
        selectCategory: "Select Category",
        selectAccount: "Select Account",
        optional: "Optional",
        fromAccount: "From Account",
        toAccount: "To Account",
        
        // Statistics Page
        statisticsTitle: "Statistics",
        incomeExpenseChart: "Income & Expense Chart",
        categoryBreakdown: "Category Breakdown",
        period: "Period",
        monthly: "Monthly",
        yearly: "Yearly",
        
        // Accounts Page
        accountsTitle: "Accounts",
        totalAssets: "Total Assets",
        totalLiabilities: "Total Liabilities",
        netWorth: "Net Worth",
        assets: "Assets",
        liabilities: "Liabilities",
        addAccount: "Add Account",
        editAccount: "Edit Account",
        selectIcon: "Select Icon",
        selectColor: "Select Color",
        cashAccounts: "Cash & Petty Cash",
        bankAccounts: "Bank Accounts",
        ewalletAccounts: "E-Wallet",
        debtAccounts: "Debts",
        accountName: "Account Name",
        accountType: "Account Type",
        initialBalance: "Initial Balance",
        
        // Settings Page
        settingsTitle: "Settings",
        profileSettings: "Profile Settings",
        basicSettings: "Basic Settings",
        language: "Language",
        selectLanguage: "Select Language",
        languageSettings: "Language Settings",
        languageInfoText: "Select a language to change the entire website display",
        changePhoto: "Change Photo",
        changePassword: "Change Password",
        newPassword: "New Password",
        confirmPassword: "Confirm Password",
        updatePassword: "Update Password",
        email: "Email",
        fullName: "Full Name",
        theme: "Theme",
        lightTheme: "Light",
        darkTheme: "Dark",
        currency: "Currency",
        currencyIDR: "IDR - Rupiah",
        currencyUSD: "USD - US Dollar",
        currencyEUR: "EUR - Euro",
        currencySGD: "SGD - Singapore Dollar",
        dateFormat: "Date Format",
        dateFormat1: "DD/MM/YYYY",
        dateFormat2: "MM/DD/YYYY",
        dateFormat3: "YYYY-MM-DD",
        saveProfile: "Save Profile",
        saveSettings: "Save Settings",
        
        // Month names
        month: "Month",
        year: "Year",
        january: "January",
        february: "February",
        march: "March",
        april: "April",
        may: "May",
        june: "June",
        july: "July",
        august: "August",
        september: "September",
        october: "October",
        november: "November",
        december: "December",
        
        // Chart & Reports
        refresh: "Refresh",
        exportPdf: "Export PDF",
        exportExcel: "Export Excel",
        balance: "Balance",
        incomeExpenseChart: "Income & Expense Chart",
        
        // Buttons & Actions
        add: "Add",
        edit: "Edit",
        delete: "Delete",
        search: "Search",
        export: "Export",
        import: "Import",
        close: "Close",
        
        // Messages
        loadingData: "Loading data...",
        noData: "No data",
        success: "Success",
        error: "Error",
        warning: "Warning",
        confirm: "Confirm",
        confirmDelete: "Are you sure you want to delete this item?",
        
        // Footer
        copyright: "Copyright",
        allRightsReserved: "All rights reserved.",
        createdBy: "Created by",
        
        // Auth Pages
        login: "Login",
        register: "Register",
        forgotPassword: "Forgot Password?",
        dontHaveAccount: "Don't have an account?",
        alreadyHaveAccount: "Already have an account?",
        password: "Password",
        rememberMe: "Remember Me"
    }
};

// Language Management System
class LanguageManager {
    constructor() {
        this.currentLang = localStorage.getItem('selectedLanguage') || 'id';
        this.initLanguage();
    }

    initLanguage() {
        this.setLanguage(this.currentLang);
    }

    setLanguage(lang) {
        if (!translations[lang]) {
            console.warn(`Language '${lang}' not found. Defaulting to 'id'`);
            lang = 'id';
        }
        
        this.currentLang = lang;
        localStorage.setItem('selectedLanguage', lang);
        this.updatePageContent();
        this.updateLanguageButtons();
        
        // Dispatch event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
    }

    updatePageContent() {
        // Update elements with data-i18n attribute
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const text = this.t(key);
            
            if (element.tagName === 'INPUT' || element.tagName === 'BUTTON') {
                element.placeholder = text;
                element.value = text;
            } else {
                element.textContent = text;
            }
        });

        // Update elements with data-i18n-placeholder
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.getAttribute('data-i18n-placeholder');
            const text = this.t(key);
            element.placeholder = text;
        });

        // Update page title
        const pageTitle = document.querySelector('[data-i18n-title]');
        if (pageTitle) {
            document.title = this.t(pageTitle.getAttribute('data-i18n-title'));
        }
    }

    updateLanguageButtons() {
        const langButtons = document.querySelectorAll('.lang-btn');
        langButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-lang') === this.currentLang) {
                btn.classList.add('active');
            }
        });
    }

    t(key) {
        if (!translations[this.currentLang][key]) {
            console.warn(`Translation key '${key}' not found for language '${this.currentLang}'`);
            return key;
        }
        return translations[this.currentLang][key];
    }

    getCurrentLang() {
        return this.currentLang;
    }
}

// Initialize language manager globally
let langManager;
document.addEventListener('DOMContentLoaded', function() {
    langManager = new LanguageManager();
});
