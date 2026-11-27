// Careers page: search, department filter, role details, mobile detail view and the apply pop-up.
(() => {
  const root = document.querySelector('.careers');
  if (!root) return;
  const cards = [...root.querySelectorAll('.job-card')], details = [...root.querySelectorAll('.job-detail')];
  const chips = [...root.querySelectorAll('.chip')], search = document.getElementById('jobSearch');
  const none = document.getElementById('noJobs'), showing = document.getElementById('showing'), panel = root.querySelector('.job-panel');
  const mobile = matchMedia('(max-width: 991.98px)');
  let dept = 'All', current = cards[0] && cards[0].dataset.job;

  const select = (id, open) => {
    current = id;
    cards.forEach(c => { const on = c.dataset.job === id; c.classList.toggle('active', on); on ? c.setAttribute('aria-current', 'true') : c.removeAttribute('aria-current'); });
    details.forEach(d => {
      const on = d.id === 'detail-' + id;
      if (on && d.hidden) { d.classList.remove('swap'); void d.offsetWidth; d.classList.add('swap'); }
      d.hidden = !on;
    });
    if (open && mobile.matches) { panel.classList.add('open'); document.documentElement.style.overflow = 'hidden'; panel.scrollTop = 0; panel.focus(); }
  };
  const close = () => {
    panel.classList.remove('open'); document.documentElement.style.overflow = '';
    const c = root.querySelector('.job-card.active'); if (c) c.focus();
  };

  cards.forEach(c => c.addEventListener('click', ev => { ev.preventDefault(); select(c.dataset.job, true); }));
  root.querySelector('.panel-back').addEventListener('click', close);
  addEventListener('keydown', ev => { if (ev.key === 'Escape' && panel.classList.contains('open')) close(); });

  const filter = () => {
    const q = search.value.trim().toLowerCase();
    let shown = 0, first = null;
    cards.forEach(c => {
      const ok = (dept === 'All' || c.dataset.dept === dept) && (!q || c.dataset.search.includes(q));
      c.parentElement.hidden = !ok;
      if (ok) { shown++; if (!first) first = c.dataset.job; }
    });
    none.hidden = shown > 0; panel.hidden = shown === 0;
    showing.textContent = shown === cards.length ? '' : shown + ' of ' + cards.length + ' roles shown';
    const cur = cards.find(c => c.dataset.job === current);
    if (first && (!cur || cur.parentElement.hidden)) select(first, false);
  };
  chips.forEach(ch => ch.addEventListener('click', () => {
    dept = ch.dataset.dept;
    chips.forEach(x => { const on = x === ch; x.classList.toggle('active', on); x.setAttribute('aria-pressed', on); });
    filter();
  }));
  search.addEventListener('input', filter);

  // Open the role named in the link (#job-...) if there is one
  const hash = decodeURIComponent(location.hash).replace('#job-', '');
  if (hash && cards.some(c => c.dataset.job === hash)) select(hash, false);

  // Apply pop-up: remember which role the person chose
  const modal = document.getElementById('applyModal');
  modal.addEventListener('show.bs.modal', ev => {
    const t = (ev.relatedTarget && ev.relatedTarget.dataset.title) || '';
    const f = modal.querySelector('form'); f.reset(); f.elements.position.value = t;
    modal.querySelector('.js-job-title').textContent = t;
    const m = modal.querySelector('.form-msg'); m.className = 'alert form-msg d-none mb-0'; m.textContent = '';
  });
})();
