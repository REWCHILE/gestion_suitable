/**
 * Suitable CRM & Outreach Orchestrator - Vanilla JS
 */

function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${type === 'success' ? '✓' : '⚠'}</span> <span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Copy text to clipboard
async function copyToClipboard(text, successMessage = 'Copiado al portapapeles') {
  try {
    await navigator.clipboard.writeText(text);
    showToast(successMessage, 'success');
  } catch (err) {
    // Fallback
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast(successMessage, 'success');
  }
}

// Update client status via AJAX
async function updateClientStatus(clientId, newStatus, callback = null) {
  try {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('client_id', clientId);
    formData.append('status', newStatus);

    const res = await fetch('api.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      showToast(data.message || 'Estado actualizado correctamente', 'success');
      if (typeof callback === 'function') {
        callback(data);
      } else {
        setTimeout(() => location.reload(), 600);
      }
    } else {
      showToast(data.error || 'Error al actualizar estado', 'error');
    }
  } catch (e) {
    showToast('Error de conexión al servidor', 'error');
  }
}

// Global click handler to close modals when clicking backdrop
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('active');
    document.body.style.overflow = '';
  }
});
