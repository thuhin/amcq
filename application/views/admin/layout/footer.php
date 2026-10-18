<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
  </main>
</div>
<footer class="admin-container admin-foot">
  <small class="amcq-muted">AcademicMCQ admin<?php if ($admin): ?> · signed in as <?= e($admin['email']) ?><?php endif; ?></small>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
