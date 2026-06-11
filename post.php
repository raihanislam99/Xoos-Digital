<?php
require_once __DIR__ . '/inc/page-cache.php';
page_cache_start('post', 600);

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$slug = $_GET['slug'] ?? '';
$post = null;
$recent = [];

if ($slug) {
    try {
        $stmt = db()->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $post = $stmt->fetch();
        if ($post && $post['category_id']) {
            $catStmt = db()->prepare("SELECT name FROM blog_categories WHERE id = ? LIMIT 1");
            $catStmt->execute([$post['category_id']]);
            $cat = $catStmt->fetch();
            $post['category_name'] = $cat['name'] ?? '';
        }
    } catch (Exception $e) {
        error_log('Blog post fetch error: ' . $e->getMessage());
    }
}

if ($post) {
    try {
        $stmt = db()->prepare("SELECT id, slug, title, featured_image, created_at FROM blog_posts WHERE status = 'published' AND slug != ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$slug]);
        $recent = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Blog recent posts fetch error: ' . $e->getMessage());
    }
}

if (!$post) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle = h($post['meta_title'] ?: $post['title']) . ' — Xoos Digital';
$pageDesc = h($post['meta_description'] ?: '');
$pageImage = image_url($post['featured_image'] ?: 'images/logo.png');
require __DIR__ . '/inc/head.php';
?>

<style>
  .post-section { padding-bottom: 5rem; }
  .post-section, .post-section * { cursor: auto; }
  .post-header { text-align: center; padding: 8rem 2rem 3rem; max-width: 800px; margin: 0 auto; }
  .post-header .brands-pill { margin-bottom: 1.25rem; }
  .post-title { font-family: 'Orbitron', sans-serif; font-size: clamp(1.6rem, 4vw, 2.8rem); font-weight: 900; text-transform: uppercase; letter-spacing: .02em; line-height: 1.15; }
  .post-meta { color: #9CA3AF; font-size: .85rem; margin-top: 1rem; }
  .post-meta span { color: #CCFF00; }
  .post-featured-img { width: 100%; max-width: 900px; margin: 0 auto 3rem; border-radius: 1.25rem; overflow: hidden; border: 1px solid #1e1e1e; display: block; }
  .post-featured-img img { width: 100%; height: auto; display: block; }
  .post-layout { max-width: 1100px; margin: 0 auto; padding: 0 2rem; display: flex; gap: 3rem; align-items: flex-start; }
  .post-content { flex: 1; min-width: 0; max-width: 750px; }
  .post-body { user-select: text; -webkit-user-select: text; }
  .post-body h2 { font-family: 'Orbitron', sans-serif; font-size: clamp(1.2rem, 2.5vw, 1.6rem); font-weight: 800; text-transform: uppercase; letter-spacing: .02em; margin: 2.5rem 0 1rem; color: #CCFF00; }
  .post-body h3 { font-family: 'Orbitron', sans-serif; font-size: clamp(1rem, 2vw, 1.2rem); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; margin: 2rem 0 .75rem; color: #fff; }
  .post-body p { color: #d1d5db; line-height: 1.9; font-size: 1.05rem; margin-bottom: 1.25rem; }
  .post-body ul, .post-body ol { color: #d1d5db; line-height: 1.9; margin: 0 0 1.25rem 1.5rem; }
  .post-body li { margin-bottom: .5rem; }
  .post-body a { color: #CCFF00; text-decoration: underline; }
  .post-body a:hover { color: #d4ff4d; }
  .post-body img { max-width: 100%; border-radius: .75rem; margin: 1.5rem 0; border: 1px solid #1e1e1e; }
  .post-body blockquote { border-left: 3px solid #CCFF00; padding: 1rem 1.5rem; margin: 1.5rem 0; background: rgba(204,255,0,0.03); border-radius: 0 .75rem .75rem 0; color: #ccc; font-style: italic; }
  .post-tags { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; margin-top: 1.5rem; }
  .post-tag { background: rgba(204,255,0,0.06); border: 1px solid rgba(204,255,0,0.15); color: #CCFF00; font-size: .7rem; font-family: 'Orbitron', sans-serif; font-weight: 700; padding: 4px 14px; border-radius: 999px; letter-spacing: .06em; text-transform: uppercase; text-decoration: none; }
  .post-back { display: inline-flex; align-items: center; gap: 8px; color: #9CA3AF; text-decoration: none; font-family: 'Orbitron', sans-serif; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; transition: color .2s; }
  .post-back:hover { color: #CCFF00; }
  .post-nav-top { max-width: 1100px; margin: 0 auto; padding: 2rem 2rem 0; }
  .post-sidebar { width: 280px; flex-shrink: 0; position: sticky; top: 100px; }
  .ps-heading { font-family: 'Orbitron', sans-serif; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #CCFF00; margin-bottom: 1.25rem; padding-bottom: .75rem; border-bottom: 1px solid #1e1e1e; }
  .ps-item { display: flex; gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #1a1a1a; text-decoration: none; transition: opacity .2s; }
  .ps-item:hover { opacity: .7; }
  .ps-item:last-child { border-bottom: none; }
  .ps-thumb { width: 60px; height: 45px; border-radius: .5rem; object-fit: cover; flex-shrink: 0; border: 1px solid #1e1e1e; background: #111; }
  .ps-info { flex: 1; min-width: 0; }
  .ps-title { color: #e5e7eb; font-size: .8rem; font-weight: 600; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .ps-date { color: #6b7280; font-size: .7rem; margin-top: 3px; }
  @media(max-width:900px) {
    .post-layout { flex-direction: column; }
    .post-sidebar { width: 100%; position: static; }
  }
  @media(max-width:768px) {
    .post-header { padding: 6rem 1.5rem 2rem; }
    .post-layout { padding: 0 1.5rem; }
  }
</style>

<?php require __DIR__ . '/inc/navbar.php'; ?>

<section class="post-section" style="background:linear-gradient(135deg, rgba(204,255,0,0.02) 0%, transparent 50%, rgba(0,0,0,0) 100%); padding-top: 2rem;">

  <div class="post-nav-top">
    <a href="blog" class="post-back"><i class="ti ti-arrow-left"></i> Back to Blog</a>
  </div>

  <div class="post-header">
    <span class="brands-pill"><?= h($post['category_name'] ?? 'Blog') ?></span>
    <h1 class="post-title"><?= h($post['title']) ?></h1>
    <div class="post-meta">
      <i class="ti ti-calendar" style="vertical-align: middle;"></i>
      <?= date('F j, Y', strtotime($post['created_at'])) ?>
      · <i class="ti ti-user" style="vertical-align: middle;"></i> Xoos Digital
    </div>
    <?php if ($post['tags']): $tags = array_map('trim', explode(',', $post['tags'])); ?>
    <div class="post-tags">
      <?php foreach ($tags as $tag): ?><span class="post-tag"><?= h($tag) ?></span><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($post['featured_image']): ?>
  <div class="post-featured-img">
    <img src="<?= h(image_url($post['featured_image'])) ?>" alt="<?= h($post['title']) ?>" loading="lazy" width="900" height="506">
  </div>
  <?php endif; ?>

  <div class="post-layout">
    <div class="post-content">
      <div class="post-body"><?= $post['content'] ?></div>
    </div>
    <?php if (!empty($recent)): ?>
    <aside class="post-sidebar">
      <div class="ps-heading">Recent Posts</div>
      <?php foreach ($recent as $r): ?>
      <a href="post/<?= urlencode($r['slug']) ?>" class="ps-item">
        <?php if ($r['featured_image']): ?>
        <img class="ps-thumb" src="<?= h(image_url($r['featured_image'])) ?>" alt="<?= h($r['title']) ?>" loading="lazy" width="60" height="45">
        <?php endif; ?>
        <div class="ps-info">
          <div class="ps-title"><?= h($r['title']) ?></div>
          <div class="ps-date"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </aside>
    <?php endif; ?>
  </div>
</section>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
page_cache_end();
