<?php
require_once 'auth.php';
$adminTitle = 'Edit Documentary';
$db = getDB();

$id  = (int)($_GET['id'] ?? 0);
$doc = null;

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM documentaries WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
}

if (!$doc) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Documentary not found.'];
    redirect(SITE_URL . '/admin/documentaries.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title'            => trim($_POST['title'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'category_id'      => (int)($_POST['category_id'] ?? 0),
        'youtube_video_id' => trim($_POST['youtube_video_id'] ?? ''),
        'source'           => trim($_POST['source'] ?? ''),
        'duration'         => trim($_POST['duration'] ?? ''),
        'year'             => (int)($_POST['year'] ?? 0),
        'rating'           => (float)($_POST['rating'] ?? 0),
        'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
        'is_trending'      => isset($_POST['is_trending']) ? 1 : 0,
        'is_active'        => isset($_POST['is_active']) ? 1 : 0,
    ];

    if (empty($data['title']))            $errors[] = 'Title is required.';
    if (empty($data['youtube_video_id'])) $errors[] = 'YouTube Video ID is required.';
    if ($data['category_id'] === 0)       $errors[] = 'Please select a category.';

    // Check duplicate video ID (excluding current)
    if (empty($errors)) {
        $dup = $db->prepare("SELECT id FROM documentaries WHERE youtube_video_id = ? AND id != ?");
        $dup->execute([$data['youtube_video_id'], $id]);
        if ($dup->fetch()) $errors[] = 'Another documentary already uses this YouTube Video ID.';
    }

    if (empty($errors)) {
        $thumbnail = "https://img.youtube.com/vi/{$data['youtube_video_id']}/maxresdefault.jpg";
        $year      = $data['year'] > 0 ? $data['year'] : null;
        $rating    = $data['rating'] > 0 ? $data['rating'] : null;
        $catId     = $data['category_id'] > 0 ? $data['category_id'] : null;

        $stmt = $db->prepare("
            UPDATE documentaries SET
                title=?, description=?, category_id=?, thumbnail=?,
                youtube_video_id=?, source=?, duration=?, year=?, rating=?,
                is_featured=?, is_trending=?, is_active=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['title'], $data['description'], $catId, $thumbnail,
            $data['youtube_video_id'], $data['source'], $data['duration'],
            $year, $rating, $data['is_featured'], $data['is_trending'], $data['is_active'], $id
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Documentary updated successfully!'];
        redirect(SITE_URL . '/admin/documentaries.php');
    }

    // Keep form data on error
    $doc = array_merge($doc, $data);
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

include 'header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="documentaries.php" class="btn dh-btn-ghost btn-sm"><i class="bi bi-arrow-left me-2"></i>Back</a>
    <h2 class="mb-0" style="font-family:var(--font-brand);letter-spacing:.5px">Edit Documentary</h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-pencil me-2 text-accent"></i>Edit Info</h5>

                <div class="mb-3">
                    <label class="dh-form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control dh-form-control"
                           value="<?= e($doc['title']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="dh-form-label">Description</label>
                    <textarea name="description" class="form-control dh-form-control" rows="4"><?= e($doc['description']) ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="dh-form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select dh-form-control" required>
                            <option value="0">Select a category...</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $doc['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="dh-form-label">Source Channel</label>
                        <input type="text" name="source" class="form-control dh-form-control" value="<?= e($doc['source']) ?>">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="dh-form-label">Duration</label>
                        <input type="text" name="duration" class="form-control dh-form-control" value="<?= e($doc['duration']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="dh-form-label">Year</label>
                        <input type="number" name="year" class="form-control dh-form-control" value="<?= e($doc['year']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="dh-form-label">Rating (0–10)</label>
                        <input type="number" name="rating" class="form-control dh-form-control"
                               value="<?= e($doc['rating']) ?>" step="0.1" min="0" max="10">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-youtube me-2 text-danger"></i>YouTube Video</h5>
                <div class="mb-3">
                    <label class="dh-form-label">Video ID <span class="text-danger">*</span></label>
                    <input type="text" id="youtube_video_id" name="youtube_video_id"
                           class="form-control dh-form-control" value="<?= e($doc['youtube_video_id']) ?>" required>
                </div>
                <div>
                    <label class="dh-form-label">Thumbnail Preview</label>
                    <img id="thumbPreview" src="" alt="Preview"
                         style="width:100%;border-radius:8px;display:none;border:1px solid var(--border)">
                    <div id="thumbPlaceholder" style="width:100%;aspect-ratio:16/9;background:var(--bg-primary);border-radius:8px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center">
                        <span class="text-muted small">Thumbnail preview</span>
                    </div>
                </div>
            </div>

            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-toggles me-2 text-accent"></i>Settings</h5>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= $doc['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_active">Active</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= $doc['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_featured">Featured</label>
                    </div>
                </div>
                <div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_trending" name="is_trending" <?= $doc['is_trending'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_trending">Trending</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn dh-btn-accent py-3 fw-600">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
                <a href="<?= SITE_URL ?>/watch.php?id=<?= $id ?>" target="_blank" class="btn dh-btn-ghost">
                    <i class="bi bi-eye me-2"></i>Preview
                </a>
                <a href="delete.php?type=documentary&id=<?= $id ?>"
                   class="btn btn-outline-danger btn-delete-confirm">
                    <i class="bi bi-trash me-2"></i>Delete
                </a>
            </div>
        </div>
    </div>
</form>

<script>
const inp = document.getElementById('youtube_video_id');
const preview = document.getElementById('thumbPreview');
const placeholder = document.getElementById('thumbPlaceholder');
if (inp) {
    inp.addEventListener('input', function () {
        const vid = this.value.trim();
        if (vid.length > 5) {
            preview.src = `https://img.youtube.com/vi/${vid}/mqdefault.jpg`;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
        }
    });
    if (inp.value) inp.dispatchEvent(new Event('input'));
}
</script>

<?php include 'footer.php'; ?>
