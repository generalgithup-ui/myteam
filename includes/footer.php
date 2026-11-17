<?php if ($isAdminPage ?? false): ?>
      </div>
    </main>
  </div>
<?php else: ?>
  <footer class="public-footer"><div class="container">TEAM Assignment - M5 <span class="mx-2">·</span> Shared, up-to-date team information</div></footer>
<?php endif; ?>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('/assets/js/script.js')) ?>"></script>
</body>
</html>
