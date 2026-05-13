/* ============================================================
   TiraHub – Global JavaScript v2 (Full UI Overhaul)
   ============================================================ */

// ── PAGE LOADER ──
const loader = document.getElementById('page-loader');
function showLoader(text = 'Please wait...') {
  if (loader) {
    loader.querySelector('.loader-text').textContent = text;
    loader.classList.add('show');
  }
}
function hideLoader() {
  if (loader) loader.classList.remove('show');
}

// ── SIDEBAR TOGGLE ──
function toggleSidebar() {
  const sidebar  = document.querySelector('.sidebar');
  const overlay  = document.querySelector('.sidebar-overlay');
  sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('show');
}

// ── TOAST NOTIFICATIONS ──
function showToast(message, type = 'success') {
  const icons = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill'
  };
  let container = document.querySelector('.toast-container-custom');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast-custom toast-${type}`;
  toast.innerHTML = `
    <i class="bi ${icons[type] || icons.success}"></i>
    <span>${message}</span>
    <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;font-size:1rem;color:#999;cursor:pointer">
      <i class="bi bi-x"></i>
    </button>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'all .4s';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(120px)';
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

// ── CONFIRMATION MODAL ──
function confirmAction(options) {
  return new Promise(resolve => {
    const existing = document.getElementById('confirmModal');
    if (existing) existing.remove();

    const colors = {
      danger:  { bg: '#fff0f2', color: '#dc3545', icon: 'bi-trash3-fill',          btn: 'btn-danger' },
      warning: { bg: '#fff8e1', color: '#f0a500', icon: 'bi-exclamation-triangle-fill', btn: 'btn-warning' },
      success: { bg: '#e8f5ee', color: '#1a7a4a', icon: 'bi-check-circle-fill',    btn: 'btn-success' },
    };
    const c = colors[options.type || 'danger'];

    const modal = document.createElement('div');
    modal.innerHTML = `
      <div class="modal fade confirm-modal" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
          <div class="modal-content">
            <div class="modal-body text-center p-4">
              <div class="confirm-icon ${options.type||'danger'}" style="background:${c.bg};color:${c.color}">
                <i class="bi ${c.icon}"></i>
              </div>
              <h6 class="fw-800 mb-2">${options.title || 'Are you sure?'}</h6>
              <p class="text-muted small mb-3">${options.message || 'This action cannot be undone.'}</p>
              <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-outline-secondary btn-sm px-3" id="confirmCancel">Cancel</button>
                <button class="btn ${c.btn} btn-sm px-3" id="confirmOk">${options.confirmText || 'Confirm'}</button>
              </div>
            </div>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);

    const bsModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    bsModal.show();

    document.getElementById('confirmOk').onclick = () => {
      bsModal.hide(); resolve(true);
    };
    document.getElementById('confirmCancel').onclick = () => {
      bsModal.hide(); resolve(false);
    };
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
      modal.remove();
    });
  });
}

// ── STEP WIZARD ──
let currentStep = 1;
const totalSteps = document.querySelectorAll('.step-pane').length;

function goToStep(step) {
  if (step < 1 || step > totalSteps) return;

  // Validate current step before going forward
  if (step > currentStep) {
    const currentPane = document.querySelector(`.step-pane[data-step="${currentStep}"]`);
    if (currentPane) {
      const required = currentPane.querySelectorAll('[required]');
      let valid = true;
      required.forEach(f => {
        if (!f.value.trim()) {
          f.classList.add('is-invalid');
          valid = false;
        } else {
          f.classList.remove('is-invalid');
        }
      });
      if (!valid) { showToast('Please fill in all required fields.', 'warning'); return; }
    }
  }

  // Update panes
  document.querySelectorAll('.step-pane').forEach(p => p.classList.remove('active'));
  const nextPane = document.querySelector(`.step-pane[data-step="${step}"]`);
  if (nextPane) nextPane.classList.add('active');

  // Update step indicators
  document.querySelectorAll('.step-item').forEach((item, i) => {
    const n = i + 1;
    item.classList.remove('active', 'completed');
    if (n < step)      item.classList.add('completed');
    else if (n === step) item.classList.add('active');
  });

  // Update nav buttons
  const prevBtn = document.getElementById('stepPrev');
  const nextBtn = document.getElementById('stepNext');
  const submitBtn = document.getElementById('stepSubmit');

  if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
  if (nextBtn) nextBtn.style.display = step === totalSteps ? 'none' : 'inline-flex';
  if (submitBtn) submitBtn.style.display = step === totalSteps ? 'inline-flex' : 'none';

  currentStep = step;

  // Scroll to top of form
  document.querySelector('.step-wizard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── LIVE NOTIFICATION COUNT ──
function refreshNotifCount() {
  fetch('../api/notif_count.php')
    .then(r => r.json())
    .then(d => {
      const count = d.count || 0;
      document.querySelectorAll('.notif-live-count').forEach(el => {
        el.textContent = count > 0 ? count : '';
        el.style.display = count > 0 ? 'inline-block' : 'none';
      });
    })
    .catch(() => {});
}

// ── FORMAT HELPERS ──
function formatPHP(amount) {
  return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function occupancyColor(pct) {
  if (pct >= 100) return 'bar-danger';
  if (pct >= 75)  return 'bar-warning';
  return '';
}

function roomCardClass(status, pct) {
  if (status === 'Full' || pct >= 100)         return 'room-full';
  if (status === 'Under Maintenance')           return 'room-maintenance';
  if (pct >= 75)                                return 'room-warning';
  return '';
}

// ── ANIMATE COUNTER ──
function animateCounter(el, target, duration = 1200) {
  const start     = 0;
  const startTime = performance.now();
  const isFloat   = target.toString().includes('.');
  const decimals  = isFloat ? 2 : 0;

  function update(currentTime) {
    const elapsed  = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased    = 1 - Math.pow(1 - progress, 3);
    const value    = start + (target - start) * eased;
    el.textContent = isFloat
      ? '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : Math.floor(value).toLocaleString();
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

// ── DOM READY ──
document.addEventListener('DOMContentLoaded', () => {

  // Sidebar overlay
  if (!document.querySelector('.sidebar-overlay')) {
    const ov = document.createElement('div');
    ov.className = 'sidebar-overlay';
    ov.onclick   = toggleSidebar;
    document.body.appendChild(ov);
  }

  // Page loader
  if (!document.getElementById('page-loader')) {
    const ldr = document.createElement('div');
    ldr.id = 'page-loader';
    ldr.innerHTML = `
      <div style="text-align:center">
        <div class="loader-spinner"></div>
        <div class="loader-text">Please wait...</div>
      </div>`;
    document.body.appendChild(ldr);
  }

  // Show loader on form submit
  document.querySelectorAll('form:not(.no-loader)').forEach(form => {
    form.addEventListener('submit', () => showLoader('Processing...'));
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s, transform .5s';
      el.style.opacity    = '0';
      el.style.transform  = 'translateY(-10px)';
      setTimeout(() => el.remove(), 500);
    }, 4500);
  });

  // Confirmation modals (data-confirm attribute)
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', async e => {
      e.preventDefault();
      const confirmed = await confirmAction({
        title:       el.dataset.confirmTitle   || 'Are you sure?',
        message:     el.dataset.confirm,
        confirmText: el.dataset.confirmText    || 'Confirm',
        type:        el.dataset.confirmType    || 'danger',
      });
      if (!confirmed) return;
      // If it's a submit button inside a form
      if (el.type === 'submit' && el.form) {
        showLoader();
        el.form.submit();
      } else if (el.href && el.href !== '#') {
        showLoader();
        window.location.href = el.href;
      }
    });
  });

  // Tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });

  // Animate stat counters
  document.querySelectorAll('.stat-value[data-count]').forEach(el => {
    const target = parseFloat(el.dataset.count);
    if (!isNaN(target)) animateCounter(el, target);
  });

  // Step wizard init
  if (document.querySelector('.step-wizard')) {
    goToStep(1);
  }

  // DataTables init
  if (typeof $.fn !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
    document.querySelectorAll('.th-datatable').forEach(tbl => {
      $(tbl).DataTable({
        pageLength: 15,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>',
        language: {
          search:        '',
          searchPlaceholder: '🔍 Search...',
          lengthMenu:    'Show _MENU_ entries',
          info:          'Showing _START_ to _END_ of _TOTAL_ records',
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next:     '<i class="bi bi-chevron-right"></i>',
          }
        },
      });
    });
  }

  // Live notification refresh every 30s
  if (document.querySelector('.notif-live-count')) {
    refreshNotifCount();
    setInterval(refreshNotifCount, 30000);
  }

  // Room card radio selection
  document.querySelectorAll('#roomPicker .room-card').forEach(card => {
    card.addEventListener('click', () => {
      if (card.classList.contains('room-full') || card.classList.contains('room-maintenance')) return;
      document.querySelectorAll('#roomPicker .room-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      const radio = card.querySelector('input[type=radio]');
      if (radio) radio.checked = true;
    });
  });

  // Flash messages as toasts
  const flashEl = document.querySelector('[data-flash-type]');
  if (flashEl) {
    showToast(flashEl.dataset.flashMsg, flashEl.dataset.flashType);
  }

  // Hide loader when page is fully loaded
  window.addEventListener('load', hideLoader);
  hideLoader();
});
