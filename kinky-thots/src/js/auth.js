/**
 * Authentication Manager for Kinky-Thots
 * Handles user registration, login, and JWT token management
 */

const AUTH_TOKEN_KEY = 'kt_auth_token';
const AUTH_USER_KEY = 'kt_auth_user';

export class AuthManager {
  constructor() {
    this.token = localStorage.getItem(AUTH_TOKEN_KEY);
    this.user = JSON.parse(localStorage.getItem(AUTH_USER_KEY) || 'null');
    this.modal = null;
    this.onAuthChange = null;
  }

  /**
   * Initialize the auth manager
   * @returns {AuthManager}
   */
  init() {
    this.modal = document.getElementById('authModal');
    this.bindEvents();
    this.updateUI();
    return this;
  }

  /**
   * Bind all event listeners
   */
  bindEvents() {
    // Auth trigger button (Login/Username)
    document.getElementById('authTrigger')?.addEventListener('click', (e) => {
      e.preventDefault();
      this.isAuthenticated() ? this.showUserMenu() : this.showModal();
    });

    // Close button
    document.getElementById('authModalClose')?.addEventListener('click', () => this.hideModal());

    // Click outside to close
    this.modal?.addEventListener('click', (e) => {
      if (e.target === this.modal) this.hideModal();
    });

    // Tab switching
    document.querySelectorAll('.auth-tab').forEach(tab => {
      tab.addEventListener('click', () => this.switchTab(tab.dataset.tab));
    });

    // Login form submission
    document.getElementById('loginForm')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleLogin();
    });

    // Register form submission
    document.getElementById('registerForm')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleRegister();
    });

    // Forgot password form
    document.getElementById('forgotForm')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleForgotPassword();
    });

    // Forgot password link
    document.getElementById('forgotPasswordLink')?.addEventListener('click', (e) => {
      e.preventDefault();
      this.showForgotForm();
    });

    // Back to login link
    document.getElementById('backToLoginLink')?.addEventListener('click', (e) => {
      e.preventDefault();
      this.showLoginForm();
    });

    // Escape key to close modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.modal?.classList.contains('active')) {
        this.hideModal();
      }
    });
  }

  /**
   * Show forgot password form
   */
  showForgotForm() {
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('forgotForm')?.classList.add('active');
    document.getElementById('authModalTitle').textContent = 'Reset Password';
    document.getElementById('forgotEmail')?.focus();
  }

  /**
   * Show login form
   */
  showLoginForm() {
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
    document.getElementById('loginForm')?.classList.add('active');
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.auth-tab[data-tab="login"]')?.classList.add('active');
    document.getElementById('authModalTitle').textContent = 'Welcome Back';
    document.getElementById('loginEmail')?.focus();
  }

  /**
   * Handle forgot password submission
   */
  async handleForgotPassword() {
    const email = document.getElementById('forgotEmail').value;
    const errorEl = document.getElementById('forgotError');
    const successEl = document.getElementById('forgotSuccess');
    const submitBtn = document.querySelector('#forgotForm .auth-submit');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';
    errorEl.classList.remove('visible');
    successEl.classList.remove('visible');

    try {
      const response = await fetch('/api/auth/forgot-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const data = await response.json();

      if (response.ok) {
        successEl.textContent = data.message || 'Reset link sent! Check your email.';
        successEl.classList.add('visible');
        document.getElementById('forgotEmail').value = '';
      } else {
        errorEl.textContent = data.error || 'Failed to send reset email';
        errorEl.classList.add('visible');
      }
    } catch (err) {
      errorEl.textContent = 'Network error. Please try again.';
      errorEl.classList.add('visible');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Send Reset Link';
    }
  }

  /**
   * Handle login form submission
   */
  async handleLogin() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errorEl = document.getElementById('loginError');
    const submitBtn = document.querySelector('#loginForm .auth-submit');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';
    errorEl.classList.remove('visible');

    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await response.json();

      if (response.ok && data.success) {
        this.setAuth(data.token, data.user);
        this.hideModal();
        this.updateUI();
        this.onAuthChange?.(true, data.user);
      } else {
        errorEl.textContent = data.error || 'Login failed';
        errorEl.classList.add('visible');
      }
    } catch (err) {
      errorEl.textContent = 'Network error. Please try again.';
      errorEl.classList.add('visible');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Login';
    }
  }

  /**
   * Handle registration form submission
   */
  async handleRegister() {
    const username = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const confirm = document.getElementById('registerConfirm').value;
    const errorEl = document.getElementById('registerError');
    const submitBtn = document.querySelector('#registerForm .auth-submit');

    // Validate password match
    if (password !== confirm) {
      errorEl.textContent = 'Passwords do not match';
      errorEl.classList.add('visible');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating account...';
    errorEl.classList.remove('visible');

    try {
      const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, email, password })
      });
      const data = await response.json();

      if (response.ok && data.success) {
        this.setAuth(data.token, data.user);
        this.hideModal();
        this.updateUI();
        this.onAuthChange?.(true, data.user);
      } else {
        errorEl.textContent = data.error || 'Registration failed';
        errorEl.classList.add('visible');
      }
    } catch (err) {
      errorEl.textContent = 'Network error. Please try again.';
      errorEl.classList.add('visible');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Create Account';
    }
  }

  /**
   * Store authentication data
   * @param {string} token - JWT token
   * @param {object} user - User data
   */
  setAuth(token, user) {
    this.token = token;
    this.user = user;
    localStorage.setItem(AUTH_TOKEN_KEY, token);
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
  }

  /**
   * Clear authentication data
   */
  clearAuth() {
    this.token = null;
    this.user = null;
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
  }

  /**
   * Log out the user
   */
  logout() {
    this.clearAuth();
    this.updateUI();
    this.onAuthChange?.(false, null);
    window.location.reload();
  }

  /**
   * Check if user is authenticated
   * @returns {boolean}
   */
  isAuthenticated() {
    return !!this.token && !!this.user;
  }

  /**
   * Get the current JWT token
   * @returns {string|null}
   */
  getToken() {
    return this.token;
  }

  /**
   * Get the current user data
   * @returns {object|null}
   */
  getUser() {
    return this.user;
  }

  /**
   * Show the auth modal
   */
  showModal() {
    this.modal?.classList.add('active');
    document.getElementById('loginEmail')?.focus();
  }

  /**
   * Hide the auth modal
   */
  hideModal() {
    this.modal?.classList.remove('active');
    document.getElementById('loginForm')?.reset();
    document.getElementById('registerForm')?.reset();
    document.querySelectorAll('.auth-error').forEach(el => el.classList.remove('visible'));
  }

  /**
   * Switch between login and register tabs
   * @param {string} tab - Tab name ('login' or 'register')
   */
  switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(t => {
      t.classList.toggle('active', t.dataset.tab === tab);
    });
    document.querySelectorAll('.auth-form').forEach(f => {
      f.classList.toggle('active', f.id === `${tab}Form`);
    });
    document.getElementById('authModalTitle').textContent =
      tab === 'login' ? 'Welcome Back' : 'Create Account';
  }

  /**
   * Update UI based on auth state
   */
  updateUI() {
    const trigger = document.getElementById('authTrigger');
    if (!trigger) return;

    if (this.isAuthenticated()) {
      trigger.textContent = this.user.username;
      trigger.classList.add('authenticated');
    } else {
      trigger.textContent = 'Login';
      trigger.classList.remove('authenticated');
    }
  }

  /**
   * Show user menu (for logged in users)
   */
  showUserMenu() {
    if (confirm(`Logged in as ${this.user.username}\n\nLogout?`)) {
      this.logout();
    }
  }
}

// Export singleton instance
export const auth = new AuthManager();
