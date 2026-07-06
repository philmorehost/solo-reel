<?php

namespace App\Controllers;

use App\Core\Database;

class SeoController {
    public function sitemap() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM site_config WHERE setting_key = 'seo_sitemap_enabled'");
        if ($stmt->fetchColumn() == '0') {
            http_response_code(404);
            die();
        }

        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $domain = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

        // Static routes
        $routes = ['/', '/search', '/login', '/register', '/coin-shop'];
        foreach ($routes as $route) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($domain . $route) . "</loc>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }

        // Dynamic routes: Series
        $stmt = $db->query("SELECT slug, updated_at FROM series");
        while ($row = $stmt->fetch()) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($domain . '/movie/' . $row['slug']) . "</loc>\n";
            echo "    <lastmod>" . date('c', strtotime($row['updated_at'] ?? 'now')) . "</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.9</priority>\n";
            echo "  </url>\n";
        }

        // Dynamic routes: Episodes
        $stmt = $db->query("SELECT slug, created_at FROM episodes");
        while ($row = $stmt->fetch()) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($domain . '/episodes/' . $row['slug']) . "</loc>\n";
            echo "    <lastmod>" . date('c', strtotime($row['created_at'])) . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        echo "</urlset>\n";
        die();
    }

    public function llms() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM site_config WHERE setting_key = 'seo_llms_enabled'");
        if ($stmt->fetchColumn() == '0') {
            http_response_code(404);
            die();
        }

        header("Content-Type: text/plain; charset=utf-8");
        echo "# SOLOREEL - Vertical Short Dramas\n\n";
        echo "SOLOREEL is a premium platform for vertical short dramas and series.\n\n";
        echo "## Categories (Shelves)\n";

        $stmt = $db->query("SELECT name FROM shelves WHERE is_active = 1");
        while ($row = $stmt->fetch()) {
            echo "- " . $row['name'] . "\n";
        }

        echo "\n## Latest Series\n";
        $stmt = $db->query("SELECT title, synopsis FROM series ORDER BY created_at DESC LIMIT 10");
        while ($row = $stmt->fetch()) {
            echo "### " . $row['title'] . "\n";
            echo $row['synopsis'] . "\n\n";
        }

        die();
    }

    /** GET /ads.txt — admin-configurable, required for AdSense/programmatic ad verification. */
    public function adstxt() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM site_config WHERE setting_key = 'ads_txt_content'");
        $content = $stmt->fetchColumn();

        header("Content-Type: text/plain; charset=utf-8");
        echo $content ?: '';
        die();
    }
}
