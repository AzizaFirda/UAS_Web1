// frontend/assets/js/transactions.js
// ========================================
// Transaction Management Helper Functions
// ========================================

const Transactions = {
  currentFilters: {},

  /**
   * Load all transactions
   */
  loadAll: async function (filters = {}) {
    try {
      this.currentFilters = filters;

      let url = "/backend/api/transactions.php?";

      if (filters.type) url += `type=${filters.type}&`;
      if (filters.account_id) url += `account_id=${filters.account_id}&`;
      if (filters.category_id) url += `category_id=${filters.category_id}&`;
      if (filters.date_from) url += `date_from=${filters.date_from}&`;
      if (filters.date_to) url += `date_to=${filters.date_to}&`;
      if (filters.limit) url += `limit=${filters.limit}&`;

      const response = await fetch(url, {
        credentials: "include",
      });
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      return data.data.transactions;
    } catch (error) {
      showToast("Gagal memuat transaksi: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Get single transaction
   */
  getById: async function (id) {
    try {
      const response = await fetch(`/backend/api/transactions.php?id=${id}`, {
        credentials: "include",
      });
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      return data.data.transaction;
    } catch (error) {
      showToast("Gagal memuat transaksi: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Create new transaction
   */
  create: async function (transactionData) {
    try {
      showLoading("Menyimpan transaksi...");

      const response = await fetch("/backend/api/transactions.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(transactionData),
      });

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      showToast("Transaksi berhasil ditambahkan!", "success");
      return data.data;
    } catch (error) {
      hideLoading();
      showToast("Gagal menambahkan transaksi: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Update transaction
   */
  update: async function (id, transactionData) {
    try {
      showLoading("Mengupdate transaksi...");

      const response = await fetch(`/backend/api/transactions.php?id=${id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(transactionData),
      });

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      showToast("Transaksi berhasil diupdate!", "success");
      return data;
    } catch (error) {
      hideLoading();
      showToast("Gagal mengupdate transaksi: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Delete transaction
   */
  delete: async function (id) {
    return new Promise((resolve, reject) => {
      confirmDialog(
        "Apakah Anda yakin ingin menghapus transaksi ini?",
        async () => {
          try {
            showLoading("Menghapus transaksi...");

            const response = await fetch(
              `/backend/api/transactions.php?id=${id}`,
              {
                method: "DELETE",
                credentials: "include",
              },
            );

            const data = await response.json();
            hideLoading();

            if (data.error) {
              throw new Error(data.message);
            }

            showToast("Transaksi berhasil dihapus!", "success");
            resolve(data);
          } catch (error) {
            hideLoading();
            showToast("Gagal menghapus transaksi: " + error.message, "danger");
            reject(error);
          }
        },
        () => {
          reject(new Error("Cancelled"));
        },
      );
    });
  },

  /**
   * Render transactions list
   */
  render: function (transactions, containerId = "transactionsList") {
    const container = document.getElementById(containerId);

    if (!container) return;

    if (!transactions || transactions.length === 0) {
      container.innerHTML =
        '<p class="text-center text-muted p-4">Belum ada transaksi</p>';
      return;
    }

    let html = "";
    transactions.forEach((t) => {
      const typeClass =
        t.type === "income"
          ? "success"
          : t.type === "expense"
            ? "danger"
            : "primary";
      const typeLabel =
        t.type === "income"
          ? "Pemasukan"
          : t.type === "expense"
            ? "Pengeluaran"
            : "Transfer";
      const iconBg =
        t.type === "income"
          ? "rgba(39, 174, 96, 0.1)"
          : t.type === "expense"
            ? "rgba(231, 76, 60, 0.1)"
            : "rgba(52, 152, 219, 0.1)";

      html += `
                <div class="transaction-item">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="transaction-icon" style="background: ${iconBg}; color: var(--${typeClass});">
                            <i class="fas fa-${t.category_icon || "exchange-alt"}"></i>
                        </div>
                        <div>
                            <strong>${t.category_name || "Transfer"}</strong>
                            <br><small class="text-muted">${t.account_name} • ${formatDate(t.transaction_date)}</small>
                            ${t.notes ? `<br><small class="text-muted"><i class="fas fa-sticky-note me-1"></i>${t.notes}</small>` : ""}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-${typeClass} fw-bold mb-1">
                            ${t.type === "expense" ? "-" : "+"} ${formatCurrency(t.amount)}
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editTransaction(${t.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction(${t.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;

    // Update count
    const countEl = document.getElementById("transactionCount");
    if (countEl) {
      countEl.textContent = `${transactions.length} Transaksi`;
    }
  },

  /**
   * Get transaction statistics
   */
  getStats: function (transactions) {
    const stats = {
      total: transactions.length,
      income: 0,
      expense: 0,
      transfer: 0,
      totalIncome: 0,
      totalExpense: 0,
      balance: 0,
    };

    transactions.forEach((t) => {
      if (t.type === "income") {
        stats.income++;
        stats.totalIncome += parseFloat(t.amount);
      } else if (t.type === "expense") {
        stats.expense++;
        stats.totalExpense += parseFloat(t.amount);
      } else if (t.type === "transfer") {
        stats.transfer++;
      }
    });

    stats.balance = stats.totalIncome - stats.totalExpense;

    return stats;
  },
};

// Make available globally
window.Transactions = Transactions;

console.log("✅ Transactions.js loaded successfully");
