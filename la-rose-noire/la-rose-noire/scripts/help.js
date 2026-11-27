// "Request help" pop-up: step 1 checks the secret key, step 2 lets the applicant change their application.
// (These forms use data-endpoint, not data-api, so the generic crud.js handler leaves them alone.)
(() => {
  const modal = document.getElementById('helpModal');
  if (!modal) return;
  const keyForm = document.getElementById('keyForm'), editForm = document.getElementById('editForm');
  const keyInput = keyForm.elements.secret_key, keyMsg = keyForm.querySelector('.form-msg'), editMsg = editForm.querySelector('.form-msg');

  const say = (box, text, type) => {
    box.textContent = text;
    if (type === 'success') box.insertAdjacentHTML('afterbegin', '<i class="bi bi-check-circle-fill check-pop me-2" aria-hidden="true"></i>');
    box.className = 'alert form-msg mt-3 mb-0 flash-in alert-' + type; box.setAttribute('role', 'alert');
    if (type === 'danger') { const c = box.closest('.modal-content'); c.classList.remove('shake'); void c.offsetWidth; c.classList.add('shake'); }
  };
  const clear = box => { box.className = 'alert form-msg d-none mt-3 mb-0'; box.textContent = ''; };
  const busy = (form, on, label) => {
    const b = form.querySelector('button[type=submit]');
    if (on) { b.dataset.label = b.innerHTML; b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + label; }
    else { b.disabled = false; if (b.dataset.label) b.innerHTML = b.dataset.label; }
  };
  const post = async form => {
    const res = await fetch(form.dataset.endpoint, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
    try { return await res.json(); } catch (e) { return { ok: false, message: 'Something went wrong. Please try again.' }; }
  };

  // CV picker for the edit form
  const cv = document.getElementById('he-cv'), drop = editForm.querySelector('.file-drop'), err = editForm.querySelector('.file-error');
  const text = drop.querySelector('strong'), hint = drop.querySelector('small'), icon = drop.querySelector('i');
  const idle = [text.textContent, hint.textContent], max = +cv.dataset.max * 1048576, okName = n => /\.(pdf|docx?)$/i.test(n);
  const refreshCv = () => {
    let f = cv.files[0]; err.hidden = true;
    if (f && (!okName(f.name) || f.size > max)) {
      err.textContent = !okName(f.name) ? 'Please choose a PDF or Word file (.pdf, .doc or .docx).' : 'That file is too big. Please choose one under ' + cv.dataset.max + ' MB.';
      err.hidden = false; cv.value = ''; f = null;
    }
    drop.classList.toggle('has-file', !!f);
    icon.className = f ? 'bi bi-check-circle-fill' : 'bi bi-cloud-arrow-up';
    text.textContent = f ? f.name : idle[0];
    hint.textContent = f ? (f.size >= 104858 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') + ' · select to change it' : idle[1];
  };
  cv.addEventListener('change', refreshCv);
  ['dragenter', 'dragover'].forEach(t => drop.addEventListener(t, e => { e.preventDefault(); drop.classList.add('drag'); }));
  ['dragleave', 'drop'].forEach(t => drop.addEventListener(t, e => { e.preventDefault(); drop.classList.remove('drag'); }));
  drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) { cv.files = e.dataTransfer.files; refreshCv(); } });
  const showCv = name => { const p = editForm.querySelector('.js-cv-current'); p.hidden = !name; p.querySelector('strong').textContent = name || ''; };

  // Fresh start every time the pop-up opens
  modal.addEventListener('show.bs.modal', () => {
    keyForm.reset(); editForm.reset(); refreshCv(); clear(keyMsg); clear(editMsg);
    editForm.elements.secret_key.value = ''; editForm.hidden = true; keyForm.hidden = false;
  });
  modal.addEventListener('shown.bs.modal', () => { if (!keyForm.hidden) keyInput.focus(); });
  keyInput.addEventListener('input', () => { keyInput.value = keyInput.value.toUpperCase(); });

  // Step 1: check the key
  keyForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    if (!keyInput.value.trim()) { say(keyMsg, 'Please enter your secret key.', 'danger'); keyInput.focus(); return; }
    busy(keyForm, true, 'Checking…'); clear(keyMsg);
    try {
      const data = await post(keyForm);
      if (!data.ok) { say(keyMsg, data.message || 'That key did not work.', 'danger'); return; }
      const a = data.application, f = editForm.elements;
      f.secret_key.value = keyInput.value.trim();
      f.applicant_name.value = a.applicant_name; f.email.value = a.email; f.phone.value = a.phone || ''; f.notes.value = a.notes || '';
      if (![...f.position.options].some(o => o.value === a.position)) f.position.add(new Option(a.position, a.position));   // a role that is no longer listed
      f.position.value = a.position;
      showCv(a.cv_name); editForm.querySelector('.js-expires').textContent = data.expires;
      keyForm.hidden = true; editForm.hidden = false;
      document.getElementById('helpEditTitle').focus();
    } catch (e) {
      say(keyMsg, 'Could not reach the server. Check your connection and try again.', 'danger');
    } finally { busy(keyForm, false); }
  });

  // Step 2: save the changes
  editForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    if (!editForm.reportValidity()) return;
    busy(editForm, true, 'Saving…'); clear(editMsg);
    try {
      const data = await post(editForm);
      if (data.ok) {
        say(editMsg, data.message, data.changed ? 'success' : 'info');
        if (data.changed) { if (data.cv_name) showCv(data.cv_name); cv.value = ''; refreshCv(); }
      } else say(editMsg, data.message || 'Something went wrong.', 'danger');
      editMsg.scrollIntoView({ block: 'nearest' });
    } catch (e) {
      say(editMsg, 'Could not reach the server. Check your connection and try again.', 'danger');
    } finally { busy(editForm, false); }
  });
})();
