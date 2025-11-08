/**
 * Toast Notification System
 * Displays non-intrusive notifications to the user
 */

class Toast {
  constructor() {
    this.container = null;
    this.init();
  }

  init() {
    // Create toast container if it doesn't exist
    if (!document.getElementById('toastContainer')) {
      this.container = document.createElement('div');
      this.container.id = 'toastContainer';
      this.container.setAttribute('role', 'region');
      this.container.setAttribute('aria-label', 'Notifications');
      document.body.appendChild(this.container);
    } else {
      this.container = document.getElementById('toastContainer');
    }
  }

  show(message, type = 'info', duration = 4000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    
    // Icons for different types
    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ'
    };

    toast.innerHTML = `
      <div class="toast-content">
        <span class="toast-icon">${icons[type] || icons['info']}</span>
        <span class="toast-message">${escapeHtmlForToast(message)}</span>
        <button class="toast-close" aria-label="Close notification">×</button>
      </div>
    `;

    this.container.appendChild(toast);

    // Add close button handler
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => this.dismiss(toast));

    // Auto dismiss after duration
    const timeoutId = setTimeout(() => this.dismiss(toast), duration);

    // Cancel timeout on hover
    toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
    toast.addEventListener('mouseleave', () => {
      setTimeout(() => this.dismiss(toast), duration);
    });

    return toast;
  }

  success(message, duration = 4000) {
    return this.show(message, 'success', duration);
  }

  error(message, duration = 5000) {
    return this.show(message, 'error', duration);
  }

  warning(message, duration = 4000) {
    return this.show(message, 'warning', duration);
  }

  info(message, duration = 4000) {
    return this.show(message, 'info', duration);
  }

  dismiss(toastElement) {
    toastElement.classList.add('toast-removing');
    setTimeout(() => {
      if (toastElement.parentNode) {
        toastElement.remove();
      }
    }, 300);
  }
}

// Create global toast instance
const toast = new Toast();

// Helper function to escape HTML in toast messages
function escapeHtmlForToast(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
