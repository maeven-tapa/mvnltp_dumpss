

class Toast {
  constructor() {
    this.container = null;
    this.init();
  }

  init() {

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


    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => this.dismiss(toast));


    const timeoutId = setTimeout(() => this.dismiss(toast), duration);


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


const toast = new Toast();


function escapeHtmlForToast(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
