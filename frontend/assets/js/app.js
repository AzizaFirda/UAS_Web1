// frontend/assets/js/app.js
// ========================================
// Personal Finance Manager - Main App JS
// ========================================

// ==================== CONFIGURATION ====================
// Get the correct base path dynamically
const currentPath = window.location.pathname;
console.log('currentPath:', currentPath);

const pathParts = currentPath.split('/').filter(p => p.length > 0);
console.log('pathParts:', pathParts);

let basePath = '';

// Find the base path dynamically (everything up to 'frontend')
const frontendIndex = pathParts.indexOf('frontend');
console.log('frontendIndex:', frontendIndex);

if (frontendIndex > 0) {
    basePath = '/' + pathParts.slice(0, frontendIndex).join('/');
} else {
    // Fallback: if 'frontend' not found, check if it's at least one level deep
    if (pathParts.length > 1 && pathParts[0] !== 'backend' && pathParts[0] !== 'pages') {
        basePath = '/' + pathParts[0];
    }
}

console.log('basePath:', basePath);

const APP_CONFIG = {
    API_URL: basePath + '/backend/api',
    APP_NAME: 'Personal Finance Manager',
    VERSION: '1.0.0',
    CURRENCY: 'IDR',
    DATE_FORMAT: 'DD/MM/YYYY',
    TIMEZONE: 'Asia/Jakarta'
};

console.log('APP_CONFIG.API_URL:', APP_CONFIG.API_URL);

// ==================== GLOBAL STATE ====================
const AppState = {
    user: null,
    isAuthenticated: false,
    currentPage: '',
    theme: 'light'
};

// ==================== UTILITY FUNCTIONS ====================

/**
 * Format number as currency
 */
function formatCurrency(amount, currency = APP_CONFIG.CURRENCY) {
    const formatted = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount);
    
    switch(currency) {
        case 'IDR':
            return 'Rp ' + formatted;
        case 'USD':
            return '$' + formatted;
        case 'EUR':
            return '€' + formatted;
        default:
            return formatted;
    }
}

/**
 * Format date
 */
function formatDate(dateString, format = 'DD/MM/YYYY') {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    switch(format) {
        case 'DD/MM/YYYY':
            return `${day}/${month}/${year}`;
        case 'MM/DD/YYYY':
            return `${month}/${day}/${year}`;
        case 'YYYY-MM-DD':
            return `${year}-${month}-${day}`;
        default:
            return date.toLocaleDateString('id-ID');
    }
}

/**
 * Format date time
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function getRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} menit yang lalu`;
    if (diffHour < 24) return `${diffHour} jam yang lalu`;
    if (diffDay < 7) return `${diffDay} hari yang lalu`;
    
    return formatDate(dateString);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info', duration = 3000) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${getToastIcon(type)} me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Show loading overlay
 */
function showLoading(message = 'Memuat...') {
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            backdrop-filter: blur(5px);
        `;
        overlay.innerHTML = `
            <div style="text-align: center; color: white;">
                <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div id="loadingMessage" style="font-size: 18px; font-weight: 600;">${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        overlay.style.display = 'flex';
        document.getElementById('loadingMessage').textContent = message;
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Confirm dialog
 */
function confirmDialog(message, onConfirm, onCancel = null) {
    if (confirm(message)) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    } else {
        if (typeof onCancel === 'function') {
            onCancel();
        }
    }
}

// ==================== API FUNCTIONS ====================

/**
 * Make API request
 */
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(`${APP_CONFIG.API_URL}${endpoint}`, config);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.message || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * GET request
 */
async function apiGet(endpoint) {
    return apiRequest(endpoint, { method: 'GET' });
}

/**
 * POST request
 */
async function apiPost(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

/**
 * PUT request
 */
async function apiPut(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'PUT',
        body: JSON.stringify(data)
    });
}

/**
 * DELETE request
 */
async function apiDelete(endpoint) {
    return apiRequest(endpoint, { method: 'DELETE' });
}

// ==================== AUTHENTICATION ====================

/**
 * Check if user is authenticated
 */
async function checkAuthentication() {
    try {
        const data = await apiGet('/auth.php?action=check');
        AppState.isAuthenticated = data.data.authenticated;
        
        if (!AppState.isAuthenticated && !isPublicPage()) {
            window.location.href = '/frontend/pages/login.html';
        }
        
        return AppState.isAuthenticated;
    } catch (error) {
        console.error('Auth check failed:', error);
        return false;
    }
}

/**
 * Check if current page is public (login/register)
 */
function isPublicPage() {
    const publicPages = ['login.html', 'register.html'];
    const currentPage = window.location.pathname.split('/').pop();
    return publicPages.includes(currentPage);
}

/**
 * Get current user info
 */
async function getCurrentUser() {
    try {
        const data = await apiGet('/auth.php?action=me');
        AppState.user = data.data.user;
        return AppState.user;
    } catch (error) {
        console.error('Failed to get user:', error);
        return null;
    }
}

/**
 * Logout user
 */
async function logout() {
    confirmDialog('Apakah Anda yakin ingin logout?', async () => {
        try {
            await apiPost('/auth.php?action=logout');
            AppState.user = null;
            AppState.isAuthenticated = false;
            window.location.href = '/frontend/pages/login.html';
        } catch (error) {
            showToast('Gagal logout', 'danger');
        }
    });
}

// ==================== STORAGE HELPERS ====================

/**
 * Save to localStorage
 */
function saveToStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
    } catch (error) {
        console.error('Storage error:', error);
        return false;
    }
}

/**
 * Get from localStorage
 */
function getFromStorage(key, defaultValue = null) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (error) {
        console.error('Storage error:', error);
        return defaultValue;
    }
}

/**
 * Remove from localStorage
 */
function removeFromStorage(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (error) {
        console.error('Storage error:', error);
        return false;
    }
}

// ==================== VALIDATION ====================

/**
 * Validate email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number
 */
function isValidPhone(phone) {
    const re = /^(\+62|62|0)[0-9]{9,12}$/;
    return re.test(phone);
}

/**
 * Validate required fields
 */
function validateRequired(value) {
    return value !== null && value !== undefined && value.toString().trim() !== '';
}

// ==================== INITIALIZATION ====================

/**
 * Initialize app
 */
document.addEventListener('DOMContentLoaded', async function() {
    console.log(`${APP_CONFIG.APP_NAME} v${APP_CONFIG.VERSION} initialized`);
    
    // Check authentication on protected pages
    if (!isPublicPage()) {
        await checkAuthentication();
    }
    
    // Set current page
    AppState.currentPage = window.location.pathname.split('/').pop().replace('.html', '');
    
    // Load user preferences
    AppState.theme = 'light';
    
    // Apply light theme
    document.body.setAttribute('data-theme', 'light');
});

// ==================== EXPORTS ====================
// Make functions available globally
window.AppConfig = APP_CONFIG;
window.AppState = AppState;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.getRelativeTime = getRelativeTime;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.confirmDialog = confirmDialog;
window.apiGet = apiGet;
window.apiPost = apiPost;
window.apiPut = apiPut;
window.apiDelete = apiDelete;
window.checkAuthentication = checkAuthentication;
window.getCurrentUser = getCurrentUser;
window.logout = logout;
window.saveToStorage = saveToStorage;
window.getFromStorage = getFromStorage;
window.removeFromStorage = removeFromStorage;
window.isValidEmail = isValidEmail;
window.isValidPhone = isValidPhone;
window.validateRequired = validateRequired;
window.debounce = debounce;
window.throttle = throttle;

// Log app ready
console.log('✅ App.js loaded successfully');