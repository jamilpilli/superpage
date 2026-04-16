<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$sites = db_fetch_all(
    "SELECT slug, updated_at FROM sites WHERE status = 'active' ORDER BY updated_at DESC"
);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://superpage.co.uk/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach ($sites as $s): ?>
    <url>
        <loc>https://superpage.co.uk/<?= htmlspecialchars($s['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($s['updated_at'] ?? 'now')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
