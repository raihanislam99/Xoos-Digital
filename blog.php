<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

$blogs = [];
try {
    $blogStmt = db()->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
    $blogs = $blogStmt->fetchAll();
} catch (Exception $e) {
    error_log('Blog fetch error: ' . $e->getMessage());
}

$pageTitle = 'Blog | Xoos Digital';
$pageDesc = 'Read the latest articles from Xoos Digital on branding, web development, digital marketing, SEO, and business growth.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="t-label-pill"><span class="t-label-dot"></span><span class="t-label-text">OUR BLOG</span></div>
    <h1 class="page-hero-title"><span class="brand-line">INSIGHTS</span> & ARTICLES</h1>
    <p class="page-hero-sub">Tips, guides, and thoughts on branding, design, development, and digital growth.</p>
  </div>
</section>

<section class="blog-section overflow-safe">
<?php if (count($blogs)): ?>
  <?php $featured = $blogs[0]; ?>
  <div class="container-xl" style="padding-top:2rem;padding-bottom:1rem">
    <article class="blog-card blog-card-featured" data-href="post/<?= h($featured['slug']) ?>">
      <div class="blog-featured-grid">
        <div class="blog-img-wrap">
          <img src="<?= h(image_url($featured['featured_image'] ?: 'images/placeholder.svg')) ?>" alt="<?= h($featured['title']) ?>" width="900" height="506" loading="lazy">
          <div class="blog-img-overlay blog-overlay-1"></div>
          <div class="blog-author-badge">
            <img src="images/Icons/user1.svg" alt="" width="20" height="20" loading="lazy">
            XOOS DIGITAL
          </div>
        </div>
        <div class="blog-featured-body">
          <div class="blog-date">
            <img src="images/Icons/date1.svg" alt="" width="20" height="20" loading="lazy">
            <span><?= strtoupper(date('d M Y', strtotime($featured['created_at']))) ?></span>
          </div>
          <h3 style="font-size:1.4rem"><?= h($featured['title']) ?></h3>
          <p style="color:#9CA3AF;font-family:'Inter',sans-serif;font-size:0.9rem;line-height:1.7;margin-top:0.75rem">
            <?= h(!empty($featured['meta_description']) ? $featured['meta_description'] : substr(strip_tags($featured['content']), 0, 200) . '...') ?>
          </p>
          <a href="post/<?= h($featured['slug']) ?>" class="blog-read-more" style="margin-top:1rem">READ MORE →</a>
        </div>
      </div>
    </article>
  </div>

  <div class="blog-grid-full">
    <?php foreach ($blogs as $bi => $post): ?>
    <?php if ($bi === 0) continue; ?>
    <article class="blog-card" data-animate data-href="post/<?= h($post['slug']) ?>">
      <div class="blog-img-wrap">
        <img src="<?= h(image_url($post['featured_image'] ?: 'images/placeholder.svg')) ?>" alt="<?= h($post['title']) ?>" width="900" height="506" loading="lazy">
        <div class="blog-img-overlay blog-overlay-<?= ($bi % 3) + 1 ?>"></div>
        <div class="blog-author-badge">
          <img src="images/Icons/user1.svg" alt="" width="20" height="20" loading="lazy">
          XOOS DIGITAL
        </div>
      </div>
      <div class="blog-body">
        <div class="blog-date">
          <img src="images/Icons/date1.svg" alt="" width="20" height="20" loading="lazy">
          <span><?= strtoupper(date('d M Y', strtotime($post['created_at']))) ?></span>
        </div>
        <h3><?= h($post['title']) ?></h3>
        <a href="post/<?= h($post['slug']) ?>" class="blog-read-more">READ MORE →</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="blog-empty">
    <p>No blog posts published yet. Check back soon!</p>
  </div>
<?php endif; ?>
</section>

<style>
.blog-card-featured {
  background: #111;
  border: 1px solid #1e1e1e;
  border-radius: 1.25rem;
  overflow: hidden;
  transition: border-color 0.4s;
  cursor: pointer;
}
.blog-card-featured:hover {
  border-color: rgba(204,255,0,0.35);
}
.blog-featured-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
}
.blog-featured-grid .blog-img-wrap img {
  aspect-ratio: auto;
  height: 100%;
  object-fit: cover;
}
.blog-featured-body {
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.blog-featured-body h3 {
  font-family: 'Orbitron', sans-serif;
  font-weight: 700;
  color: white;
  line-height: 1.4;
  transition: color .3s;
}
.blog-card-featured:hover .blog-featured-body h3 {
  color: #CCFF00;
}
@media(max-width:768px) {
  .blog-featured-grid { grid-template-columns: 1fr; }
  .blog-featured-grid .blog-img-wrap img { max-height: 240px; }
  .blog-featured-body { padding: 1.5rem; }
}
</style>

<?php
require __DIR__ . '/inc/footer.php';
require __DIR__ . '/inc/project-form.php';
require __DIR__ . '/inc/chatbot.php';
require __DIR__ . '/inc/scripts.php';
?>
