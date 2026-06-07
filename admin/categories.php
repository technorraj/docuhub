<?php
require_once 'auth.php';
$adminTitle = 'Manage Categories';
$db = getDB();

$errors = [];
$editCat = null;

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-collection-play');
    $cid  = (int)($_POST['cat_id'] ?? 0);

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        $slug = generateSlug($name);

        if ($cid > 0) {
            // Edit
            $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, description=?, icon=? WHERE id=?");
            $stmt->execute([$name, $slug, $desc, $icon, $cid]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category updated!'];
        } else {
            // Add - check duplicate slug
            $dup = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $dup->execute([$slug]);
            if ($dup->fetch()) {
                $errors[] = 'A category with this name already exists.';
            } else {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?,?,?,?)");
                $stmt->execute([$name, $slug, $desc, $icon]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category "' . $name . '" added!'];
            }
        }

        if (empty($errors)) redirect(SITE_URL . '/admin/categories.php');
    }
}

// Load for edit
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}

// All categories with doc count
$categories = $db->query("
    SELECT c.*, COUNT(d.id) as doc_count
    FROM categories c
    LEFT JOIN documentaries d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

// Bootstrap icon options
$iconOptions = [
    'bi-tree' => 'Nature', 'bi-cpu' => 'Technology', 'bi-hourglass' => 'History',
    'bi-people' => 'Society', 'bi-stars' => 'Space', 'bi-globe' => 'Environment',
    'bi-shield' => 'Politics', 'bi-search' => 'Crime', 'bi-heart-pulse' => 'Health',
    'bi-music-note' => 'Art', 'bi-collection-play' => 'General', 'bi-camera-video' => 'Film',
    'bi-book' => 'Education', 'bi-lightning' => 'Action', 'bi-geo-alt' => 'Travel',
];

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="mb-0" style="font-family:var(--font-brand);letter-spacing:.5px">Categories</h2>
</div>

<div class="row g-4">
    <!-- Add / Edit Form -->
    <div class="col-lg-4">
        <div class="dh-form-card">
            <h5 class="text-white mb-4">
                <?= $editCat ? '<i class="bi bi-pencil me-2 text-accent"></i>Edit Category' : '<i class="bi bi-plus-circle me-2 text-accent"></i>Add Category' ?>
            </h5>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-3">
                <?php foreach ($errors as $e): ?><?= e($e) ?><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <?php if ($editCat): ?>
                <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="dh-form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control dh-form-control"
                           value="<?= e($editCat['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="dh-form-label">Description</label>
                    <textarea name="description" class="form-control dh-form-control" rows="3"><?= e($editCat['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-4">
                    <label class="dh-form-label">Icon</label>
                    <select name="icon" id="iconSelect" class="form-select dh-form-control">
                        <?php foreach ($iconOptions as $cls => $label): ?>
                        <option value="<?= $cls ?>" <?= ($editCat['icon'] ?? 'bi-collection-play') === $cls ? 'selected' : '' ?>>
                            <?= $label ?> (<?= $cls ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Icon Preview -->
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <span class="text-muted small">Preview:</span>
                        <i id="iconPreview" class="bi <?= e($editCat['icon'] ?? 'bi-collection-play') ?>" style="font-size:1.5rem;color:var(--accent)"></i>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn dh-btn-accent">
                        <?= $editCat ? 'Update Category' : 'Add Category' ?>
                    </button>
                    <?php if ($editCat): ?>
                    <a href="categories.php" class="btn dh-btn-ghost">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="dh-table">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Docs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <i class="bi <?= e($cat['icon']) ?>" style="font-size:1.3rem;color:var(--accent)"></i>
                        </td>
                        <td>
                            <div style="font-weight:600;color:white"><?= e($cat['name']) ?></div>
                            <?php if ($cat['description']): ?>
                            <div style="font-size:.75rem;color:var(--text-muted)"><?= e(substr($cat['description'], 0, 50)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td><code style="color:var(--accent);font-size:.78rem"><?= e($cat['slug']) ?></code></td>
                        <td>
                            <span style="font-weight:600;color:white"><?= $cat['doc_count'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="categories.php?edit=<?= $cat['id'] ?>"
                                   class="btn btn-sm dh-btn-ghost py-1 px-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($cat['doc_count'] == 0): ?>
                                <a href="delete.php?type=category&id=<?= $cat['id'] ?>"
                                   class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-confirm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary py-1 px-2" disabled title="Cannot delete: has documentaries">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const iconSelect = document.getElementById('iconSelect');
const iconPreview = document.getElementById('iconPreview');
if (iconSelect && iconPreview) {
    iconSelect.addEventListener('change', function () {
        iconPreview.className = 'bi ' + this.value;
    });
}
</script>

<?php include 'footer.php'; ?>
