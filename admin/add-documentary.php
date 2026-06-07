<?php
require_once 'auth.php';
$adminTitle = 'Add Documentary';
$db = getDB();

$errors = [];
$data   = [];

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

    // Validation
    if (empty($data['title']))            $errors[] = 'Title is required.';
    if (empty($data['youtube_video_id'])) $errors[] = 'YouTube Video ID is required.';
    if ($data['category_id'] === 0)       $errors[] = 'Please select a category.';
    if ($data['rating'] < 0 || $data['rating'] > 10) $errors[] = 'Rating must be between 0 and 10.';

    // Check duplicate video ID
    if (empty($errors)) {
        $dup = $db->prepare("SELECT id FROM documentaries WHERE youtube_video_id = ?");
        $dup->execute([$data['youtube_video_id']]);
        if ($dup->fetch()) $errors[] = 'A documentary with this YouTube Video ID already exists.';
    }

    if (empty($errors)) {
        $slug      = generateSlug($data['title']);
        $thumbnail = "https://img.youtube.com/vi/{$data['youtube_video_id']}/maxresdefault.jpg";
        $year      = $data['year'] > 0 ? $data['year'] : null;
        $rating    = $data['rating'] > 0 ? $data['rating'] : null;
        $catId     = $data['category_id'] > 0 ? $data['category_id'] : null;

        // Ensure unique slug
        $slugBase = $slug;
        $slugN    = 1;
        while (true) {
            $s = $db->prepare("SELECT id FROM documentaries WHERE slug = ?");
            $s->execute([$slug]);
            if (!$s->fetch()) break;
            $slug = $slugBase . '-' . $slugN++;
        }

        $stmt = $db->prepare("
            INSERT INTO documentaries
                (title, slug, description, category_id, thumbnail, youtube_video_id,
                 source, duration, year, rating, is_featured, is_trending, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['title'], $slug, $data['description'], $catId, $thumbnail,
            $data['youtube_video_id'], $data['source'], $data['duration'],
            $year, $rating, $data['is_featured'], $data['is_trending'], $data['is_active']
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Documentary "' . $data['title'] . '" added successfully!'];
        redirect(SITE_URL . '/admin/documentaries.php');
    }
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

include 'header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="documentaries.php" class="btn dh-btn-ghost btn-sm"><i class="bi bi-arrow-left me-2"></i>Back</a>
    <h2 class="mb-0" style="font-family:var(--font-brand);letter-spacing:.5px">Add Documentary</h2>
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
        <!-- Left Column -->
        <div class="col-lg-8">

            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-camera-video me-2 text-accent"></i>Documentary Info</h5>

                <div class="mb-3">
                    <label class="dh-form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control dh-form-control"
                           value="<?= e($data['title'] ?? '') ?>" required placeholder="e.g. Our Planet">
                </div>

                <div class="mb-3">
                    <label class="dh-form-label">Description</label>
                    <textarea name="description" class="form-control dh-form-control" rows="4"
                              placeholder="Brief synopsis of the documentary..."><?= e($data['description'] ?? '') ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="dh-form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select dh-form-control" required>
                            <option value="0">Select a category...</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($data['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="dh-form-label">Source Channel</label>
                        <input type="text" name="source" class="form-control dh-form-control"
                               value="<?= e($data['source'] ?? '') ?>" placeholder="e.g. DW Documentary">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="dh-form-label">Duration</label>
                        <input type="text" name="duration" class="form-control dh-form-control"
                               value="<?= e($data['duration'] ?? '') ?>" placeholder="e.g. 47 min">
                    </div>
                    <div class="col-md-4">
                        <label class="dh-form-label">Year</label>
                        <input type="number" name="year" class="form-control dh-form-control"
                               value="<?= e($data['year'] ?? '') ?>" min="1900" max="<?= date('Y') + 1 ?>" placeholder="<?= date('Y') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="dh-form-label">Rating (0–10)</label>
                        <input type="number" name="rating" class="form-control dh-form-control"
                               value="<?= e($data['rating'] ?? '') ?>" min="0" max="10" step="0.1" placeholder="8.5">
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-lg-4">

            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-youtube me-2 text-danger"></i>YouTube Video</h5>

                <div class="mb-3">
                    <label class="dh-form-label">YouTube Video ID <span class="text-danger">*</span></label>
                    <input type="text" id="youtube_video_id" name="youtube_video_id"
                           class="form-control dh-form-control"
                           value="<?= e($data['youtube_video_id'] ?? '') ?>"
                           placeholder="e.g. dQw4w9WgXcQ" required>
                    <div class="form-text text-muted small mt-1">
                        The ID from: youtube.com/watch?v=<strong>THIS_PART</strong>
                    </div>
                </div>

                <!-- Thumbnail Preview -->
                <div class="mt-3">
                    <label class="dh-form-label">Thumbnail Preview</label>
                    <img id="thumbPreview" src="" alt="Thumbnail preview"
                         style="width:100%;border-radius:8px;display:none;border:1px solid var(--border)">
                    <div id="thumbPlaceholder" style="width:100%;aspect-ratio:16/9;background:var(--bg-primary);border-radius:8px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center">
                        <span class="text-muted small">Enter Video ID to preview</span>
                    </div>
                </div>
            </div>

            <div class="dh-form-card mb-4">
                <h5 class="text-white mb-4"><i class="bi bi-toggles me-2 text-accent"></i>Settings</h5>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                               <?= ($data['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_active">Active (visible to users)</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                               <?= !empty($data['is_featured']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_featured">
                            <span class="dh-badge-featured me-1">★</span> Featured on homepage
                        </label>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_trending" name="is_trending"
                               <?= !empty($data['is_trending']) ? 'checked' : '' ?>>
                        <label class="form-check-label text-white" for="is_trending">
                            <span class="dh-badge-trending me-1">🔥</span> Show in Trending
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn dh-btn-accent py-3 fw-600">
                    <i class="bi bi-plus-circle me-2"></i>Add Documentary
                </button>
                <a href="documentaries.php" class="btn dh-btn-ghost">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
// Show/hide thumbnail placeholder
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
