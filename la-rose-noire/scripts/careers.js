
// Careers page: search, department filter, role details, mobile detail view and the apply pop-up.
(() => {
  const root = document.querySelector('.careers');
  if (!root) return;

  const cards = [...root.querySelectorAll('.job-card')];
  const details = [...root.querySelectorAll('.job-detail')];
  const chips = [...root.querySelectorAll('.chip')];

  const search = document.getElementById('jobSearch');
  const none = document.getElementById('noJobs');
  const showing = document.getElementById('showing');
  const panel = root.querySelector('.job-panel');

  const mobile = matchMedia('(max-width: 991.98px)');

  let dept = 'All';
  let current = cards[0] && cards[0].dataset.job;

  const select = (id, open) => {
    current = id;

    cards.forEach(c => {
      const on = c.dataset.job === id;

      c.classList.toggle('active', on);

      if (on) {
        c.setAttribute('aria-current', 'true');
      } else {
        c.removeAttribute('aria-current');
      }
    });

    details.forEach(d => {
      const on = d.id === 'detail-' + id;

      if (on && d.hidden) {
        d.classList.remove('swap');
        void d.offsetWidth;
        d.classList.add('swap');
      }

      d.hidden = !on;
    });

    if (open && mobile.matches) {
      panel.classList.add('open');
      document.documentElement.style.overflow = 'hidden';
      panel.scrollTop = 0;
      panel.focus();
    }
  };

  const close = () => {
    panel.classList.remove('open');
    document.documentElement.style.overflow = '';

    const c = root.querySelector('.job-card.active');

    if (c) c.focus();
  };

  cards.forEach(c => {
    c.addEventListener('click', ev => {
      ev.preventDefault();
      select(c.dataset.job, true);
    });
  });

  root.querySelector('.panel-back').addEventListener('click', close);

  addEventListener('keydown', ev => {
    if (ev.key === 'Escape' && panel.classList.contains('open')) {
      close();
    }
  });

  const filter = () => {
    const q = search.value.trim().toLowerCase();

    let shown = 0;
    let first = null;

    cards.forEach(c => {
      const ok =
        (dept === 'All' || c.dataset.dept === dept) &&
        (!q || c.dataset.search.includes(q));

      c.parentElement.hidden = !ok;

      if (ok) {
        shown++;
        if (!first) first = c.dataset.job;
      }
    });

    none.hidden = shown > 0;
    panel.hidden = shown === 0;

    showing.textContent =
      shown === cards.length
        ? ''
        : shown + ' of ' + cards.length + ' roles shown';

    const cur = cards.find(c => c.dataset.job === current);

    if (first && (!cur || cur.parentElement.hidden)) {
      select(first, false);
    }
  };

  chips.forEach(ch => {
    ch.addEventListener('click', () => {
      dept = ch.dataset.dept;

      chips.forEach(x => {
        const on = x === ch;

        x.classList.toggle('active', on);
        x.setAttribute('aria-pressed', on);
      });

      filter();
    });
  });

  search.addEventListener('input', filter);

  // Open the role named in the link.
  const hash = decodeURIComponent(location.hash).replace('#job-', '');

  if (hash && cards.some(c => c.dataset.job === hash)) {
    select(hash, false);
  }

  // Application modal.
  const modal = document.getElementById('applyModal');

  const keyPanel = modal.querySelector('.key-panel');
  const applyForm = modal.querySelector('form');
  const keyBox = document.getElementById('secretKey');

  const keySavedBox = document.getElementById('keySaved');
  const keyDoneBtn = document.getElementById('keyDone');
  const copyBtn = document.getElementById('keyCopy');

  let keyData = null;

  const showKey = on => {
    keyPanel.hidden = !on;

    [...applyForm.children].forEach(c => {
      if (c !== keyPanel && !c.matches('input, .d-none')) {
        c.hidden = on;
      }
    });

    if (!on) {
      keyData = null;
      keyBox.textContent = '';
      keySavedBox.checked = false;
      keyDoneBtn.disabled = true;
      copyBtn.lastElementChild.textContent = 'Copy key';
    }
  };

  // Prepare the application form whenever the modal opens.
  modal.addEventListener('show.bs.modal', ev => {
    const t = (ev.relatedTarget && ev.relatedTarget.dataset.title) || '';

    applyForm.reset();

    applyForm.elements.position.value = t;

    modal.querySelector('.js-job-title').textContent = t;

    const m = modal.querySelector('.form-msg');

    m.className = 'alert form-msg d-none mb-0';
    m.textContent = '';

    showKey(false);
  });

  // Display the generated secret key.
  applyForm.addEventListener('form:ok', ev => {
    const d = ev.detail;

    if (!d.secret_key) return;

    // CRITICAL: prevent crud.js from resetting the form.
    ev.preventDefault();

    keyData = d;

    keyBox.textContent = d.secret_key;

    keyPanel.querySelector('.js-key-role').textContent =
      d.position || '';

    keyPanel.querySelector('.js-key-expires').textContent =
      d.expires || '';

    showKey(true);

    keyPanel.querySelector('.modal-title').scrollIntoView({
      block: 'nearest'
    });

    keyBox.focus();
  });

  // Applicant confirmation.
  keySavedBox.addEventListener('change', () => {
    keyDoneBtn.disabled = !keySavedBox.checked;
  });

  // Copy secret key.
  copyBtn.addEventListener('click', async () => {
    if (!keyData) return;

    try {
      await navigator.clipboard.writeText(keyData.secret_key);
    } catch (e) {
      const r = document.createRange();

      r.selectNodeContents(keyBox);

      const sel = getSelection();

      sel.removeAllRanges();
      sel.addRange(r);

      document.execCommand('copy');

      sel.removeAllRanges();
    }

    copyBtn.lastElementChild.textContent = 'Copied!';
  });

  // Download secret key.
  document.getElementById('keyDownload').addEventListener('click', () => {
    if (!keyData) return;

    const text =
      'La Rose Noire - application secret key\r\n\r\n' +
      'Secret key: ' + keyData.secret_key + '\r\n' +
      'Role: ' + keyData.position + '\r\n' +
      'Valid until: ' + keyData.expires + '\r\n\r\n' +
      'Keep this safe. Use it with the Request help button on the careers page to change your application.\r\n';

    const a = document.createElement('a');

    a.href = URL.createObjectURL(
      new Blob([text], { type: 'text/plain' })
    );

    a.download = 'la-rose-noire-secret-key.txt';

    document.body.appendChild(a);

    a.click();
    a.remove();

    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  });

  // CV / resume picker.
  const cv = document.getElementById('pub-cv');

  if (cv) {
    const drop = modal.querySelector('.file-drop');

    const text = drop.querySelector('strong');
    const hint = drop.querySelector('small');
    const icon = drop.querySelector('i');
    const err = modal.querySelector('.file-error');

    const idle = [text.textContent, hint.textContent];

    const max = +cv.dataset.max * 1048576;

    const okName = n => /\.(pdf|docx?)$/i.test(n);

    const refresh = () => {
      let f = cv.files[0];

      err.hidden = true;

      if (f && (!okName(f.name) || f.size > max)) {
        err.textContent = !okName(f.name)
          ? 'Please choose a PDF or Word file (.pdf, .doc or .docx).'
          : 'That file is too big. Please choose one under ' + cv.dataset.max + ' MB.';

        err.hidden = false;

        cv.value = '';
        f = null;
      }

      drop.classList.toggle('has-file', !!f);

      icon.className = f
        ? 'bi bi-check-circle-fill'
        : 'bi bi-cloud-arrow-up';

      text.textContent = f ? f.name : idle[0];

      hint.textContent = f
        ? (
            f.size >= 104858
              ? (f.size / 1048576).toFixed(1) + ' MB'
              : Math.max(1, Math.round(f.size / 1024)) + ' KB'
          ) + ' · select to change it'
        : idle[1];
    };

    cv.addEventListener('change', refresh);

    applyForm.addEventListener('reset', () => setTimeout(refresh));

    ['dragenter', 'dragover'].forEach(t => {
      drop.addEventListener(t, e => {
        e.preventDefault();
        drop.classList.add('drag');
      });
    });

    ['dragleave', 'drop'].forEach(t => {
      drop.addEventListener(t, e => {
        e.preventDefault();
        drop.classList.remove('drag');
      });
    });

    drop.addEventListener('drop', e => {
      if (e.dataTransfer.files.length) {
        cv.files = e.dataTransfer.files;
        refresh();
      }
    });
  }

})();
