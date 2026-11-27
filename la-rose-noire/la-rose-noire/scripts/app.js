// Display settings, idle sign-out, count-up numbers, and the sign-in / sign-up helpers.
(() => {
  const root = document.documentElement;
  const base = document.body.dataset.base || '';
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches || root.dataset.motion === '1';
  const save = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };

  // ---- Personal display settings (only exist for signed-in users; saved to the account) ----
  const panel = document.getElementById('prefsPanel');
  if (panel) {
    const status = document.getElementById('prefStatus');
    let timer;
    const apply = p => {
      const dark = p.theme === 'dark' || (p.theme === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches);
      root.classList.add('theme-fade');
      root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
      root.style.fontSize = p.size + '%'; root.style.setProperty('--accent', p.accent);
      const sw = panel.querySelector('input[data-pref="accent"]:checked'); if (sw) root.style.setProperty('--on-accent', sw.dataset.on);
      ['font', 'space', 'corners', 'contrast', 'motion'].forEach(k => root.dataset[k] = p[k]);
      setTimeout(() => root.classList.remove('theme-fade'), 450);
    };
    const read = () => { const o = {}; panel.querySelectorAll('input[data-pref]:checked').forEach(i => o[i.dataset.pref] = i.value); return o; };
    const saveNow = () => {
      const body = new FormData(); body.append('csrf', document.body.dataset.csrf);
      Object.entries(read()).forEach(([k, v]) => body.append(k, v));
      status.textContent = 'Saving…';
      fetch(base + '/api/prefs.php', { method: 'POST', body, credentials: 'same-origin' })
        .then(r => r.json()).then(d => { status.textContent = d.ok ? 'Saved to your account ✓' : 'Could not save.'; })
        .catch(() => { status.textContent = 'Could not save. Check your connection.'; });
    };
    panel.addEventListener('change', e => { if (!e.target.dataset.pref) return; apply(read()); clearTimeout(timer); timer = setTimeout(saveNow, 350); });
    document.getElementById('prefReset').addEventListener('click', () => {
      panel.querySelectorAll('input[data-pref]').forEach(i => { i.checked = i.hasAttribute('data-default'); });
      apply(read()); clearTimeout(timer); saveNow();
    });
  }

  // ---- Numbers count up on the dashboard ----
  document.querySelectorAll('[data-count]').forEach(el => {
    const end = +el.dataset.count;
    if (calm || end < 2) return;
    const t0 = performance.now(), dur = 900;
    const step = now => {
      const p = Math.min(1, (now - t0) / dur);
      el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    };
    el.textContent = '0';
    requestAnimationFrame(step);
  });

  // ---- Sign in / sign up helpers ----
  document.querySelectorAll('.pw-toggle').forEach(btn => {
    const input = btn.parentElement.querySelector('input');
    btn.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.querySelector('i').className = 'bi bi-eye' + (show ? '-slash' : '');
      btn.querySelector('span').textContent = show ? 'Hide' : 'Show';
      input.focus();
    });
    const note = btn.parentElement.parentElement.querySelector('.caps-note');
    if (note) {
      const caps = e => note.classList.toggle('d-none', !(e.getModifierState && e.getModifierState('CapsLock')));
      input.addEventListener('keyup', caps); input.addEventListener('keydown', caps); input.addEventListener('blur', () => note.classList.add('d-none'));
    }
  });

  const signup = document.querySelector('.js-signup');
  if (signup) {
    const pw = signup.elements.password, cf = signup.elements.confirm;
    const meter = signup.querySelector('.meter'), help = document.getElementById('pwHelp'), match = document.getElementById('matchNote');
    const min = +pw.getAttribute('minlength') || 10, words = ['', 'Too weak', 'Okay', 'Good', 'Strong'];
    const check = () => {
      const v = pw.value, n = v.length;
      let lvl = n === 0 ? 0 : n < min ? 1 : n >= 18 ? 4 : n >= 14 ? 3 : 2;
      if (n && new Set(v).size < 5) lvl = 1;
      meter.dataset.level = lvl; meter.firstElementChild.style.width = (lvl * 25) + '%';
      if (n) help.textContent = n < min ? 'Add ' + (min - n) + ' more character' + (min - n > 1 ? 's' : '') + '.' : words[lvl] + '. This password is long enough.';
      if (cf.value) {
        const ok = cf.value === v;
        match.textContent = ok ? 'The passwords match.' : 'The passwords do not match yet.';
        match.className = 'form-text ' + (ok ? 'ok-note' : 'bad-note');
      } else match.textContent = '';
    };
    pw.addEventListener('input', check); cf.addEventListener('input', check);
    const rules = signup.querySelectorAll('.pw-rules li');
    const upd = () => rules.forEach(li => {
      const ok = li.dataset.rule === 'len' ? pw.value.length >= min : cf.value !== '' && cf.value === pw.value;
      li.classList.toggle('done', ok); li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    });
    pw.addEventListener('input', upd); cf.addEventListener('input', upd);
  }

  // Friendly checks + a loading state on the sign-in / sign-up buttons
  document.querySelectorAll('form.js-auth').forEach(form => {
    const anchor = i => i.closest('.pw-field') || i;
    const clear = i => { i.classList.remove('is-invalid'); i.removeAttribute('aria-invalid'); const n = anchor(i).nextElementSibling; if (n && n.classList.contains('invalid-note')) n.remove(); };
    form.querySelectorAll('input').forEach(i => i.addEventListener('input', () => clear(i)));
    form.addEventListener('submit', ev => {
      const empty = [...form.querySelectorAll('[required]')].filter(i => !i.value.trim());
      if (empty.length) {
        ev.preventDefault();
        empty.forEach(i => {
          clear(i); i.classList.add('is-invalid'); i.setAttribute('aria-invalid', 'true');
          anchor(i).insertAdjacentHTML('afterend', '<div class="invalid-note bad-note" role="alert">Please fill this in.</div>');
        });
        empty[0].focus();
        const card = form.closest('.card'); card.classList.remove('shake'); void card.offsetWidth; card.classList.add('shake');
        return;
      }
      const btn = form.querySelector('.js-submit');
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Please wait…'; }
    });
  });

  // ---- Gentle reveal as sections scroll into view ----
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !calm) {
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12 });
    reveals.forEach(r => io.observe(r));
  } else reveals.forEach(r => r.classList.add('in'));

  // ---- Idle sign-out (the server enforces the same limit) ----
  if (document.body.dataset.auth) {
    const LIMIT = 15 * 60 * 1000, WARN = LIMIT - 60 * 1000;
    const warn = document.getElementById('idleWarn'), count = document.getElementById('idleCount');
    let last = Date.now(), lastPing = Date.now();
    const ping = () => fetch(base + '/api/ping.php', { credentials: 'same-origin' })
      .then(r => { if (r.status === 401) location.href = base + '/auth/login.php'; }).catch(() => {});
    const activity = () => {
      last = Date.now();
      if (last - lastPing > 20000) { lastPing = last; ping(); }   // keeps the PHP session alive while the person is active
    };
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(ev => addEventListener(ev, activity, { passive: true }));
    document.getElementById('idleStay')?.addEventListener('click', () => { last = lastPing = Date.now(); ping(); warn.classList.add('d-none'); });
    setInterval(() => {
      const idle = Date.now() - last;
      if (idle >= LIMIT) { location.href = base + '/auth/logout.php?idle=1'; return; }
      warn.classList.toggle('d-none', idle < WARN);
      if (idle >= WARN) count.textContent = Math.max(0, Math.ceil((LIMIT - idle) / 1000));
    }, 1000);
  }
})();
