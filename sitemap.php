<?php
/**
 * sitemap.php — 동적 sitemap.xml 생성
 */
if (!defined('ROOT')) { define('ROOT', __DIR__); }
require_once ROOT . '/config/config.php';

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$db    = DB::getInstance();
$today = date('Y-m-d');

$staticPages = array(
    array('url' => SITE_URL,              'priority' => '1.0', 'freq' => 'daily'),
    array('url' => SITE_URL . '/greeting','priority' => '0.8', 'freq' => 'monthly'),
    array('url' => SITE_URL . '/history', 'priority' => '0.7', 'freq' => 'monthly'),
    array('url' => SITE_URL . '/business','priority' => '0.8', 'freq' => 'monthly'),
);
foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($p['url']) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>{$p['freq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

$boards = $db->fetchAll("SELECT slug FROM boards WHERE is_active = 1");
foreach ($boards as $b) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars(SITE_URL . '/board/' . $b['slug']) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

$posts = $db->fetchAll(
    "SELECT p.id, p.slug, p.updated_at, p.created_at, b.slug as board_slug
     FROM posts p JOIN boards b ON p.board_id = b.id
     WHERE p.status = 'active' AND p.is_secret = 0 AND b.read_auth = 'all'
     ORDER BY p.id DESC LIMIT 1000"
);
foreach ($posts as $p) {
    $lastmod = $p['updated_at']
        ? date('Y-m-d', strtotime($p['updated_at']))
        : date('Y-m-d', strtotime($p['created_at']));
    $url = SITE_URL . '/board/' . $p['board_slug'] . '/' . $p['id'] . '/' . $p['slug'];
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    echo "    <lastmod>$lastmod</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
