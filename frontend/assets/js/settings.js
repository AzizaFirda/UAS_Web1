// frontend/assets/js/settings.js
// ========================================
// Settings Helper Functions
// ========================================

const Settings = {
  /**
   * Load user settings
   */
  loadUserSettings: async function () {
    try {
      const response = await fetch("/backend/api/auth.php?action=me");
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      return data.data.user;
    } catch (error) {
      showToast("Gagal memuat pengaturan: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Update user profile
   */
  updateProfile: async function (profileData) {
    try {
      showLoading("Menyimpan profil...");

      const response = await fetch("/backend/api/users.php?action=update", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(profileData),
      });

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      showToast("Profil berhasil diupdate!", "success");
      return data;
    } catch (error) {
      hideLoading();
      showToast("Gagal mengupdate profil: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Change password
   */
  changePassword: async function (newPassword) {
    try {
      showLoading("Mengubah password...");

      const response = await fetch(
        "/backend/api/users.php?action=change-password",
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ password: newPassword }),
        },
      );

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      showToast("Password berhasil diubah!", "success");
      return data;
    } catch (error) {
      hideLoading();
      showToast("Gagal mengubah password: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Upload profile photo
   */
  uploadProfilePhoto: async function (file) {
    try {
      showLoading("Mengunggah foto...");

      const formData = new FormData();
      formData.append("profile_photo", file);

      const response = await fetch(
        "/backend/api/users.php?action=upload-photo",
        {
          method: "POST",
          body: formData,
        },
      );

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      showToast("Foto profil berhasil diupdate!", "success");
      return data;
    } catch (error) {
      hideLoading();
      showToast("Gagal mengunggah foto: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Apply currency format
   */
  applyCurrency: function (currency) {
    saveToStorage("currency", currency);
    APP_CONFIG.CURRENCY = currency;
    showToast("Mata uang diupdate!", "success", 2000);
  },

  /**
   * Apply date format
   */
  applyDateFormat: function (format) {
    saveToStorage("dateFormat", format);
    APP_CONFIG.DATE_FORMAT = format;
    showToast("Format tanggal diupdate!", "success", 2000);
  },

  /**
   * Apply language
   */
  applyLanguage: function (language) {
    saveToStorage("language", language);
    showToast(
      "Bahasa diupdate! Refresh halaman untuk melihat perubahan.",
      "info",
    );
  },

  /**
   * Export all data
   */
  exportData: async function () {
    try {
      showLoading("Mengekspor data...");

      // Get all user data
      const transactions = await Transactions.loadAll();
      const user = await this.loadUserSettings();

      const exportData = {
        user: user,
        transactions: transactions,
        exportDate: new Date().toISOString(),
        version: APP_CONFIG.VERSION,
      };

      // Create download link
      const dataStr = JSON.stringify(exportData, null, 2);
      const dataBlob = new Blob([dataStr], { type: "application/json" });
      const url = URL.createObjectURL(dataBlob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `finance-manager-backup-${formatDate(new Date().toISOString(), "YYYY-MM-DD")}.json`;
      link.click();

      hideLoading();
      showToast("Data berhasil diekspor!", "success");
    } catch (error) {
      hideLoading();
      showToast("Gagal mengekspor data: " + error.message, "danger");
    }
  },

  /**
   * Clear all data (with confirmation)
   */
  clearAllData: function () {
    confirmDialog(
      "PERINGATAN! Ini akan menghapus SEMUA data Anda termasuk transaksi, akun, dan kategori. Apakah Anda yakin?",
      async () => {
        confirmDialog(
          "Konfirmasi sekali lagi. Data yang dihapus TIDAK DAPAT dikembalikan!",
          async () => {
            try {
              showLoading("Menghapus semua data...");

              // Call API to clear all user data
              const response = await fetch(
                "/backend/api/users.php?action=clear-data",
                {
                  method: "DELETE",
                },
              );

              const data = await response.json();
              hideLoading();

              if (data.error) {
                throw new Error(data.message);
              }

              showToast("Semua data berhasil dihapus!", "success");

              // Logout and redirect
              setTimeout(() => {
                Auth.logout();
              }, 2000);
            } catch (error) {
              hideLoading();
              showToast("Gagal menghapus data: " + error.message, "danger");
            }
          },
        );
      },
    );
  },
};

// Make available globally
window.Settings = Settings;
