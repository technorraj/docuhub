<!-- Footer -->
<footer class="dh-footer mt-5">
    <div class="container-fluid px-3 px-lg-5">
        <div class="row g-4 py-5">
            <div class="col-lg-4">
                <div class="dh-logo mb-3 fs-4">
                    <i class="bi bi-camera-reels-fill text-accent me-2"></i>DocumentaryHub
                </div>
                <p class="text-muted small">Your ultimate destination for quality documentaries from around the world. Explore nature, science, history, and more.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="dh-social-link"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="dh-social-link"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="dh-social-link"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="dh-social-link"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-600 mb-3">Browse</h6>
                <ul class="list-unstyled dh-footer-links">
                    <li><a href="<?= SITE_URL ?>">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/categories.php">Categories</a></li>
                    <li><a href="<?= SITE_URL ?>/search.php">Search</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-600 mb-3">Account</h6>
                <ul class="list-unstyled dh-footer-links">
                    <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/dashboard.php">Dashboard</a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php">Logout</a></li>
                    <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="text-white fw-600 mb-3">Top Categories</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php
                    $db = getDB();
                    $footerCats = $db->query("SELECT name, slug FROM categories ORDER BY name LIMIT 8")->fetchAll();
                    foreach ($footerCats as $fc):
                    ?>
                    <a href="<?= SITE_URL ?>/categories.php?cat=<?= e($fc['slug']) ?>" class="badge dh-badge-cat">
                        <?= e($fc['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="py-3 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <p class="text-muted small mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Built as a BCA project. Not for commercial use.</p>
            <p class="text-muted small mb-0 mt-2 mt-md-0">Powered by PHP, MySQL & Bootstrap 5</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
