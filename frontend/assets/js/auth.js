// frontend/assets/js/auth.js
// ========================================
// Authentication Helper Functions
// ========================================

const Auth = {
  /**
   * Login user
   */
  login: async function (email, password) {
    try {
      showLoading("Memproses login...");

      const response = await fetch("/backend/api/auth.php?action=login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      // Save user info
      AppState.user = data.data.user;
      AppState.isAuthenticated = true;

      // Save user name to localStorage
      if (data.data.user && data.data.user.name) {
        localStorage.setItem("user_name", data.data.user.name);
      }

      return data;
    } catch (error) {
      hideLoading();
      throw error;
    }
  },

  /**
   * Register new user
   */
  register: async function (userData) {
    try {
      showLoading("Mendaftarkan akun...");

      const response = await fetch("/backend/api/auth.php?action=register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(userData),
      });

      const data = await response.json();
      hideLoading();

      if (data.error) {
        throw new Error(data.message);
      }

      // Save user name to localStorage
      if (userData && userData.name) {
        localStorage.setItem("user_name", userData.name);
      }

      return data;
    } catch (error) {
      hideLoading();
      throw error;
    }
  },

  /**
   * Logout user
   */
  logout: async function () {
    try {
      showLoading("Logging out...");

      await fetch("/backend/api/auth.php?action=logout", {
        method: "POST",
      });

      // Clear state
      AppState.user = null;
      AppState.isAuthenticated = false;

      // Clear storage
      removeFromStorage("user");
      removeFromStorage("token");
      localStorage.removeItem("user_name");

      hideLoading();

      // Redirect to login
      window.location.href = "/frontend/pages/login.html";
    } catch (error) {
      hideLoading();
      console.error("Logout error:", error);
    }
  },

  /**
   * Check authentication status
   */
  checkAuth: async function () {
    try {
      const response = await fetch("/backend/api/auth.php?action=check");
      const data = await response.json();

      if (data.error) {
        return false;
      }

      AppState.isAuthenticated = data.data.authenticated;
      return data.data.authenticated;
    } catch (error) {
      console.error("Auth check error:", error);
      return false;
    }
  },

  /**
   * Get current user
   */
  getCurrentUser: async function () {
    try {
      const response = await fetch("/backend/api/auth.php?action=me", {
        method: "GET",
        credentials: "include",
      });
      const data = await response.json();

      if (data.error) {
        throw new Error(data.message);
      }

      AppState.user = data.data.user;

      // Save user name to localStorage
      if (data.data.user && data.data.user.name) {
        localStorage.setItem("user_name", data.data.user.name);
      }

      return data.data.user;
    } catch (error) {
      console.error("Get user error:", error);
      return null;
    }
  },

  /**
   * Require authentication (redirect if not authenticated)
   */
  requireAuth: async function () {
    const isAuth = await this.checkAuth();

    if (!isAuth) {
      window.location.href = "/frontend/pages/login.html";
      return false;
    }

    return true;
  },

  /**
   * Require guest (redirect if authenticated)
   */
  requireGuest: async function () {
    const isAuth = await this.checkAuth();

    if (isAuth) {
      window.location.href = "/frontend/pages/dashboard.html";
      return false;
    }

    return true;
  },
};

// Make available globally
window.Auth = Auth;

console.log("✅ Auth.js loaded successfully");
