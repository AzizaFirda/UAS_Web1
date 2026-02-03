// frontend/assets/js/dashboard.js
// ========================================
// Dashboard Helper Functions
// ========================================

const Dashboard = {
    charts: {},
    
    /**
     * Load dashboard data
     */
    loadData: async function() {
        try {
            showLoading('Memuat dashboard...');
            
            const response = await fetch('/backend/api/dashboard.php');
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.message);
            }
            
            hideLoading();
            return data.data;
        } catch (error) {
            hideLoading();
            showToast('Gagal memuat dashboard: ' + error.message, 'danger');
            throw error;
        }
    },
    
    /**
     * Update summary cards
     */
    updateSummary: function(summary) {
        // Monthly summary
        const monthIncome = document.getElementById('monthIncome');
        const monthExpense = document.getElementById('monthExpense');
        const monthBalance = document.getElementById('monthBalance');
        const totalAssets = document.getElementById('totalAssets');
        
        if (monthIncome) {
            monthIncome.textContent = formatCurrency(summary.month.income || 0);
        }
        
        if (monthExpense) {
            monthExpense.textContent = formatCurrency(summary.month.expense || 0);
        }
        
        if (monthBalance) {
            const balance = summary.month.balance || 0;
            monthBalance.textContent = formatCurrency(balance);
            monthBalance.className = 'mb-0 ' + (balance >= 0 ? 'text-success' : 'text-danger');
        }
        
        if (totalAssets) {
            totalAssets.textContent = formatCurrency(summary.accounts.assets || 0);
        }
    },
    
    /**
     * Render recent transactions
     */
    renderRecentTransactions: function(transactions) {
        const container = document.getElementById('recentTransactions');
        
        if (!container) return;
        
        if (!transactions || transactions.length === 0) {
            container.innerHTML = '<p class="text-center text-muted p-4">Belum ada transaksi</p>';
            return;
        }
        
        let html = '';
        transactions.slice(0, 5).forEach(t => {
            const typeClass = t.type === 'income' ? 'success' : (t.type === 'expense' ? 'danger' : 'primary');
            const iconBg = t.type === 'income' ? 'rgba(39, 174, 96, 0.1)' : 
                         (t.type === 'expense' ? 'rgba(231, 76, 60, 0.1)' : 'rgba(52, 152, 219, 0.1)');
            
            html += `
                <div class="transaction-item">
                    <div class="d-flex align-items-center">
                        <div class="transaction-icon" style="background: ${iconBg}; color: var(--${typeClass});">
                            <i class="fas fa-${t.category_icon || 'circle'}"></i>
                        </div>
                        <div>
                            <strong>${t.category_name || 'Transfer'}</strong>
                            <br><small class="text-muted">${t.account_name} • ${formatDate(t.transaction_date)}</small>
                        </div>
                    </div>
                    <div class="text-${typeClass} fw-bold">
                        ${t.type === 'expense' ? '-' : '+'} ${formatCurrency(t.amount)}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    },
    
    /**
     * Create expense pie chart
     */
    createExpenseChart: function(data) {
        const canvas = document.getElementById('expenseChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart
        if (this.charts.expense) {
            this.charts.expense.destroy();
        }
        
        this.charts.expense = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    data: data.map(d => d.total),
                    backgroundColor: data.map(d => d.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + formatCurrency(context.raw);
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Create trend chart
     */
    createTrendChart: function(data) {
        const canvas = document.getElementById('trendChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Destroy existing chart
        if (this.charts.trend) {
            this.charts.trend.destroy();
        }
        
        this.charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: data.map(d => d.income),
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Pengeluaran',
                        data: data.map(d => d.expense),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatCurrency(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Initialize dashboard
     */
    init: async function() {
        try {
            const dashboardData = await this.loadData();
            
            // Update UI
            this.updateSummary(dashboardData.summary);
            this.renderRecentTransactions(dashboardData.recent_transactions);
            this.createExpenseChart(dashboardData.expense_by_category);
            this.createTrendChart(dashboardData.monthly_trend);
            
            showToast('Dashboard dimuat!', 'success', 2000);
        } catch (error) {
            console.error('Dashboard init error:', error);
        }
    }
};

// Make available globally
window.Dashboard = Dashboard;

console.log('✅ Dashboard.js loaded successfully');