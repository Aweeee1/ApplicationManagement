</main>
<footer class="site-footer">© <?= date('Y') ?> <?= APP_NAME ?></footer>
<?php if (!empty($_SESSION['user'])): ?>
<div id="idleWarn" class="idle-warn alert alert-warning shadow d-none" role="alertdialog" aria-live="assertive" aria-label="Inactivity warning">
  <div><i class="bi bi-hourglass-split"></i> You will be signed out in <strong id="idleCount">60</strong> seconds because nothing has happened for a while.</div>
  <button type="button" class="btn btn-dark mt-2" id="idleStay">Stay signed in</button>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('scripts/app.js') ?>"></script>
<script src="<?= url('scripts/crud.js') ?>"></script>
</body>
</html>
