// frontend/assets/js/statistics.js
// ========================================
// Statistics Module - Charts & Reports
// ========================================

// Ensure APP_CONFIG is available (it should be loaded by app.js first)
if (typeof APP_CONFIG === "undefined") {
  console.error("APP_CONFIG not defined. Make sure app.js is loaded first.");
}

const Statistics = {
  charts: {},
  currentFilters: {
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
  },

  /**
   * Initialize statistics page
   */
  init: async function () {
    try {
      showLoading("Memuat statistik...");

      // Set default month and year
      const today = new Date();
      const month = String(today.getMonth() + 1).padStart(2, "0");
      const year = today.getFullYear();

      const monthSelect = document.getElementById("filterMonth");
      const yearInput = document.getElementById("filterYear");

      if (monthSelect) monthSelect.value = month;
      if (yearInput) yearInput.value = year;

      this.currentFilters.month = month;
      this.currentFilters.year = year;

      await this.loadAll();
      hideLoading();
      showToast("Statistik dimuat!", "success", 2000);
    } catch (error) {
      hideLoading();
      showToast("Gagal memuat statistik: " + error.message, "danger");
      console.error("Statistics init error:", error);
    }
  },

  /**
   * Load all statistics data
   */
  loadAll: async function () {
    try {
      await Promise.all([
        this.loadOverview(),
        this.loadCategoryStats(),
        this.loadAccountStats(),
        this.loadTrendData(),
        this.loadIncomeExpenseChart(),
      ]);
    } catch (error) {
      console.error("Error loading statistics:", error);
      throw error;
    }
  },

  /**
   * Load overview statistics
   */
  loadOverview: async function () {
    try {
      const url = `${APP_CONFIG.API_URL}/statistics.php?action=overview&month=${this.currentFilters.month}&year=${this.currentFilters.year}`;
      const response = await fetch(url, {
        credentials: "include",
      });
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      const stats = data.data.monthly;
      this.updateOverviewCards(stats);
    } catch (error) {
      showToast("Gagal memuat overview: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Update overview cards
   */
  updateOverviewCards: function (stats) {
    const income = stats.income || 0;
    const expense = stats.expense || 0;
    const balance = income - expense;

    const totalIncomeEl = document.getElementById("totalIncome");
    const totalExpenseEl = document.getElementById("totalExpense");
    const totalBalanceEl = document.getElementById("totalBalance");
    const totalTransactionsEl = document.getElementById("totalTransactions");

    if (totalIncomeEl) totalIncomeEl.textContent = formatCurrency(income);
    if (totalExpenseEl) totalExpenseEl.textContent = formatCurrency(expense);

    if (totalBalanceEl) {
      totalBalanceEl.textContent = formatCurrency(balance);
      totalBalanceEl.className =
        "mb-0 " + (balance >= 0 ? "text-success" : "text-danger");
    }

    if (totalTransactionsEl) {
      const totalTrans = (stats.income_count || 0) + (stats.expense_count || 0);
      totalTransactionsEl.textContent = totalTrans;
    }
  },

  /**
   * Load category statistics
   */
  loadCategoryStats: async function () {
    try {
      const url = `${APP_CONFIG.API_URL}/statistics.php?action=category&month=${this.currentFilters.month}&year=${this.currentFilters.year}&type=expense`;
      const response = await fetch(url, {
        credentials: "include",
      });
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      const categories = data.data.categories || [];
      this.createExpensePieChart(categories);
      this.displayCategoryList(categories);
    } catch (error) {
      showToast("Gagal memuat kategori: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Create expense pie chart
   */
  createExpensePieChart: function (categories) {
    const canvas = document.getElementById("expensePieChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    // Destroy existing chart
    if (this.charts.expensePie) {
      this.charts.expensePie.destroy();
    }

    this.charts.expensePie = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: categories.map((c) => c.name),
        datasets: [
          {
            data: categories.map((c) => parseFloat(c.total) || 0),
            backgroundColor: categories.map((c) => c.color || "#3498db"),
            borderWidth: 2,
            borderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "right",
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const label = context.label || "";
                const value = formatCurrency(context.raw);
                return label + ": " + value;
              },
            },
          },
        },
      },
    });
  },

  /**
   * Display category list with progress bars
   */
  displayCategoryList: function (categories) {
    const container = document.getElementById("expenseCategoryList");

    if (!container) return;

    if (!categories || categories.length === 0) {
      container.innerHTML =
        '<p class="text-center text-muted">Belum ada data pengeluaran</p>';
      return;
    }

    const total = categories.reduce(
      (sum, c) => sum + parseFloat(c.total || 0),
      0,
    );

    let html = "";
    categories.forEach((cat) => {
      const categoryTotal = parseFloat(cat.total || 0);
      const percentage =
        total > 0 ? ((categoryTotal / total) * 100).toFixed(1) : 0;

      html += `
                <div class="category-item">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="category-icon" style="background: ${cat.color}20; color: ${cat.color};">
                            <i class="fas fa-${cat.icon || "tag"}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>${cat.name}</strong>
                                <span>${formatCurrency(categoryTotal)}</span>
                            </div>
                            <div class="progress progress-custom" style="height: 8px;">
                                <div class="progress-bar" style="width: ${percentage}%; background: ${cat.color};"></div>
                            </div>
                            <small class="text-muted">${cat.count || 0} transaksi • ${percentage}%</small>
                        </div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  },

  /**
   * Load account statistics
   */
  loadAccountStats: async function () {
    try {
      const response = await fetch(
        `${APP_CONFIG.API_URL}/statistics.php?action=account`,
        {
          credentials: "include",
        },
      );
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      const accounts = data.data.accounts || [];
      this.displayAccountList(accounts);
    } catch (error) {
      showToast("Gagal memuat akun: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Display account list
   */
  displayAccountList: function (accounts) {
    const container = document.getElementById("accountList");

    if (!container) return;

    if (!accounts || accounts.length === 0) {
      container.innerHTML =
        '<p class="text-center text-muted">Belum ada akun</p>';
      return;
    }

    let html = "";
    accounts.forEach((acc) => {
      const currentBalance = parseFloat(acc.current_balance || 0);
      const change = parseFloat(acc.change || 0);
      const changeClass = change >= 0 ? "success" : "danger";
      const changeSign = change >= 0 ? "+" : "";

      // Map account type to display name
      const typeMap = {
        cash: "Cash",
        bank: "M-Banking",
        mbanking: "M-Banking",
        ewallet: "E-Wallet",
        debt: "Utang",
      };
      const displayType = typeMap[acc.type] || acc.type;

      html += `
                <div class="category-item">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="category-icon" style="background: ${acc.color}20; color: ${acc.color};">
                            <i class="fas fa-${acc.icon || "wallet"}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>${acc.name}</strong>
                                <span class="text-${changeClass} fw-bold">${changeSign}${formatCurrency(change)}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">${displayType}</small>
                                <strong>${formatCurrency(currentBalance)}</strong>
                            </div>
                            <small class="text-muted">${acc.transaction_count || 0} transaksi</small>
                        </div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  },

  /**
   * Load trend data
   */
  loadTrendData: async function () {
    try {
      const response = await fetch(
        `${APP_CONFIG.API_URL}/statistics.php?action=trend&months=12`,
        {
          credentials: "include",
        },
      );
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      const trend = data.data.trend || [];
      this.createTrendChart(trend);
    } catch (error) {
      showToast("Gagal memuat tren: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Create trend chart (line chart)
   */
  createTrendChart: function (trendData) {
    const canvas = document.getElementById("trendChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    // Destroy existing chart
    if (this.charts.trend) {
      this.charts.trend.destroy();
    }

    this.charts.trend = new Chart(ctx, {
      type: "line",
      data: {
        labels: trendData.map((d) => d.label || d.period),
        datasets: [
          {
            label: "Pemasukan",
            data: trendData.map((d) => parseFloat(d.income || 0)),
            borderColor: "#27ae60",
            backgroundColor: "rgba(39, 174, 96, 0.1)",
            tension: 0.4,
            fill: true,
            borderWidth: 2,
            pointRadius: 5,
            pointBackgroundColor: "#27ae60",
            pointBorderColor: "#fff",
          },
          {
            label: "Pengeluaran",
            data: trendData.map((d) => parseFloat(d.expense || 0)),
            borderColor: "#e74c3c",
            backgroundColor: "rgba(231, 76, 60, 0.1)",
            tension: 0.4,
            fill: true,
            borderWidth: 2,
            pointRadius: 5,
            pointBackgroundColor: "#e74c3c",
            pointBorderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "top",
          },
          tooltip: {
            mode: "index",
            intersect: false,
            callbacks: {
              label: function (context) {
                return (
                  context.dataset.label + ": " + formatCurrency(context.raw)
                );
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatCurrency(value);
              },
            },
          },
        },
        interaction: {
          mode: "nearest",
          axis: "x",
          intersect: false,
        },
      },
    });
  },

  /**
   * Load income vs expense chart
   */
  loadIncomeExpenseChart: async function () {
    try {
      const response = await fetch(
        `${APP_CONFIG.API_URL}/statistics.php?action=trend&months=12`,
        {
          credentials: "include",
        },
      );
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      const trend = data.data.trend || [];
      this.createIncomeExpenseChart(trend);
    } catch (error) {
      showToast("Gagal memuat grafik: " + error.message, "danger");
      throw error;
    }
  },

  /**
   * Create income vs expense bar chart
   */
  createIncomeExpenseChart: function (trendData) {
    const canvas = document.getElementById("incomeExpenseChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    // Destroy existing chart
    if (this.charts.incomeExpense) {
      this.charts.incomeExpense.destroy();
    }

    // Get last 6 months
    const lastSixMonths = trendData.slice(-6);

    this.charts.incomeExpense = new Chart(ctx, {
      type: "bar",
      data: {
        labels: lastSixMonths.map((d) => d.label || d.period),
        datasets: [
          {
            label: "Pemasukan",
            data: lastSixMonths.map((d) => parseFloat(d.income || 0)),
            backgroundColor: "#27ae60",
            borderRadius: 5,
            borderWidth: 0,
          },
          {
            label: "Pengeluaran",
            data: lastSixMonths.map((d) => parseFloat(d.expense || 0)),
            backgroundColor: "#e74c3c",
            borderRadius: 5,
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "top",
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return (
                  context.dataset.label + ": " + formatCurrency(context.raw)
                );
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatCurrency(value);
              },
            },
          },
        },
      },
    });
  },

  /**
   * Refresh statistics with new filters
   */
  refresh: async function () {
    try {
      showLoading("Memperbarui statistik...");

      const monthSelect = document.getElementById("filterMonth");
      const yearInput = document.getElementById("filterYear");

      if (monthSelect) this.currentFilters.month = monthSelect.value;
      if (yearInput) this.currentFilters.year = yearInput.value;

      await this.loadAll();
      hideLoading();
      showToast("Statistik diperbarui!", "success", 2000);
    } catch (error) {
      hideLoading();
      showToast("Gagal memperbarui statistik: " + error.message, "danger");
      console.error("Refresh error:", error);
    }
  },
};

/**
 * Load statistics data (wrapper function for HTML onclick)
 */
async function loadStatistics() {
  await Statistics.refresh();
}

/**
 * Export report function
 */
async function exportReport(format) {
  try {
    const month =
      document.getElementById("filterMonth")?.value ||
      new Date().getMonth() + 1;
    const year =
      document.getElementById("filterYear")?.value || new Date().getFullYear();

    showToast("Mengexport laporan " + format.toUpperCase() + "...", "info");

    // Call backend export API
    const response = await fetch(
      `${APP_CONFIG.API_URL}/export.php?format=${format}&month=${month}&year=${year}`,
      {
        credentials: "include",
      },
    );

    if (!response.ok) {
      const errorText = await response.text();
      try {
        const errorData = JSON.parse(errorText);
        throw new Error(errorData.message || "Export failed");
      } catch (e) {
        throw new Error("Export failed: " + response.statusText);
      }
    }

    if (format === "pdf") {
      // Get response as JSON for PDF generation
      const data = await response.json();

      // Generate PDF from HTML using html2pdf
      if (typeof html2pdf !== "undefined") {
        const element = document.createElement("div");
        element.innerHTML = data.html;

        const options = {
          margin: 10,
          filename: data.filename,
          image: { type: "jpeg", quality: 0.98 },
          html2canvas: { scale: 2 },
          jsPDF: { orientation: "portrait", unit: "mm", format: "a4" },
        };

        html2pdf().set(options).from(element).save();
      } else {
        throw new Error("PDF library tidak tersedia");
      }
    } else {
      // For Excel, download the blob directly
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `Laporan_Keuangan_${month}_${year}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    }

    showToast("Laporan berhasil diexport!", "success");
  } catch (error) {
    showToast("Gagal mengexport laporan: " + error.message, "danger");
    console.error("Export error:", error);
  }
}

/**
 * Logout function
 */
function logout() {
  confirmDialog("Apakah Anda yakin ingin logout?", () => {
    fetch(`${APP_CONFIG.API_URL}/auth.php?action=logout`, {
      method: "POST",
      credentials: "include",
    }).then(() => {
      window.location.href = "login.html";
    });
  });
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  Statistics.init();
});

// Make available globally
window.Statistics = Statistics;
window.loadStatistics = loadStatistics;
window.exportReport = exportReport;
window.logout = logout;
