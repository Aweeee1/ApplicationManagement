// Generic CRUD wiring: fills modals, submits forms to the api/ endpoints with fetch, shows errors.
(() => {
  const base = document.body.dataset.base || '';
  document.querySelectorAll('.modal').forEach(m => document.body.appendChild(m));   // top-level modals cannot be hidden behind the backdrop
  const show = (box, text, type) => {
    if (!box) return;
    box.textContent = text;
    if (type === 'success') box.insertAdjacentHTML('afterbegin', '<i class="bi bi-check-circle-fill check-pop me-2" aria-hidden="true"></i>');
    box.className = 'alert form-msg mb-0 flash-in alert-' + type;
    box.setAttribute('role', 'alert');
    if (type === 'danger') { const c = box.closest('.modal-content, .card'); if (c) { c.classList.remove('shake'); void c.offsetWidth; c.classList.add('shake'); } }
  };

  // Create/edit modals: a trigger button with data-json = edit, without = create
  document.querySelectorAll('.modal[data-crud="form"]').forEach(modal => {
    const form = modal.querySelector('form');
    modal.addEventListener('show.bs.modal', ev => {
      form.reset();
      modal.querySelector('.form-msg').classList.add('d-none');
      const raw = ev.relatedTarget && ev.relatedTarget.dataset.json;
      const mode = raw ? 'edit' : 'new';
      form.elements.action.value = raw ? 'update' : 'create';
      if (raw) {
        const d = JSON.parse(raw);
        Object.keys(d).forEach(k => { if (k !== 'action' && form.elements[k] && d[k] !== null) form.elements[k].value = d[k]; });
      }
      modal.querySelector('.modal-title').textContent = modal.querySelector('.modal-title').dataset[mode];
      const pw = form.elements.password;
      if (pw) pw.required = !raw;
      const hint = modal.querySelector('.js-pw-hint');
      if (hint) hint.textContent = hint.dataset[mode];
    });
  });

  // Delete modals
  document.querySelectorAll('.modal[data-crud="delete"]').forEach(modal => {
    modal.addEventListener('show.bs.modal', ev => {
      modal.querySelector('[name=id]').value = ev.relatedTarget.dataset.id;
      modal.querySelector('.js-name').textContent = ev.relatedTarget.dataset.name;
      modal.querySelector('.form-msg').classList.add('d-none');
    });
  });

  // Submit any form[data-api] with fetch; the API returns JSON
  document.addEventListener('submit', async ev => {
    const form = ev.target.closest('form[data-api]');
    if (!form) return;
    ev.preventDefault();
    const btn = form.querySelector('button[type=submit]');
    const box = form.querySelector('.form-msg');
    const label = btn ? btn.innerHTML : '';
    let leaving = false;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Please wait…'; }
    try {
      const res = await fetch(form.dataset.api, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
      if (res.status === 401 && document.body.dataset.auth) { location.href = base + '/auth/login.php'; return; }
      const data = await res.json();
      if (data.ok) {
        if (form.dataset.reload === '0') { form.reset(); show(box, data.message, 'success'); form.dispatchEvent(new CustomEvent('form:ok', { detail: data })); }
        else { leaving = true; location.reload(); } // the API stored a flash message that the reloaded page shows
      } else {
        show(box, data.message || 'Something went wrong.', 'danger');
      }
    } catch (e) {
      show(box, 'Could not reach the server. Check your connection and try again.', 'danger');
    } finally {
      if (btn && !leaving) { btn.disabled = false; btn.innerHTML = label; }
    }
  });

  // Dashboard deep links: ?open=ID opens that record, ?new=1 opens the create form
  const p = new URLSearchParams(location.search);
  if (p.get('open')) {
    const row = document.getElementById('row-' + p.get('open'));
    if (row) { row.classList.add('flash-row'); row.scrollIntoView({ block: 'center' }); row.querySelector('[data-json]').click(); }
  } else if (p.get('new') && document.getElementById('addBtn')) {
    document.getElementById('addBtn').click();
  }

  // Public page: "Apply for this role" preselects the role
  document.querySelectorAll('.js-pick-role').forEach(a => a.addEventListener('click', () => {
    const sel = document.getElementById('publicPosition');
    if (sel) sel.value = a.dataset.position;
  }));
})();
