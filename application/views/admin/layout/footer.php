<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
  </main>
  <footer class="admin-foot">
    <small>&copy; <?= date('Y') ?> AcademicMCQ admin<?php if ($admin): ?> · signed in as <?= e($admin['email']) ?><?php endif; ?></small>
  </footer>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
