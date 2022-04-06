<?php
/*
  Plugin Name: RSS WordPress Plugin
  Plugin URI: https://github.com/lzomedia/rss.wordpress.plugin
  Description: This plugin lets you set up an import posts from one or several rss-feeds and save them as posts on your site, simple and flexible.
  Author: LzoMedia
  Version: 5.0
  Author URI: https://development.sh/
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: rss.WordPress.plugin
 */

// define some constants
if (!defined('RSS_PI_PATH')) {
	define('RSS_PI_PATH', trailingslashit(plugin_dir_path(__FILE__)));
}

if (!defined('RSS_PL_PATH')) {
	define('RSS_PL_PATH', trailingslashit(plugin_dir_path(dirname(__FILE__))));
}


if (!defined('RSS_PI_URL')) {
	define('RSS_PI_URL', trailingslashit(plugin_dir_url(__FILE__)));
}

if (!defined('RSS_PI_BASENAME')) {
	define('RSS_PI_BASENAME', plugin_basename(__FILE__));
}

if (!defined('RSS_PI_VERSION')) {
	define('RSS_PI_VERSION', '5.0');
}

if (!defined('RSS_PI_LOG_PATH')) {
	define('RSS_PI_LOG_PATH', trailingslashit(WP_CONTENT_DIR) . 'rsspi-log/');
}

if (!is_dir(RSS_PI_LOG_PATH) && !mkdir($concurrentDirectory = RSS_PI_LOG_PATH) && !is_dir($concurrentDirectory)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}



// helper classes
include_once RSS_PI_PATH . 'app/classes/helpers/class-rss-pi-log.php';
include_once RSS_PI_PATH . 'app/classes/helpers/class-rss-pi-featured-image.php';
include_once RSS_PI_PATH . 'app/classes/helpers/class-rss-pi-parser.php';
include_once RSS_PI_PATH . 'app/classes/helpers/rss-pi-functions.php';
include_once RSS_PI_PATH . 'app/classes/helpers/class-OPMLParser.php'; // OPML Parser

// admin classes
include_once RSS_PI_PATH . 'app/classes/admin/class-rss-pi-admin-processor.php';
include_once RSS_PI_PATH . 'app/classes/admin/class-rss-pi-admin.php';
include_once RSS_PI_PATH . 'app/classes/admin/class-rss-pi-export-to-csv.php';
include_once RSS_PI_PATH . 'app/classes/admin/class-rss-pi-stats.php';
include_once RSS_PI_PATH . 'app/classes/admin/class-rss-pi-opml.php';

// Front classes
include_once RSS_PI_PATH . 'app/classes/front/class-rss-pi-front.php';

// main importers
include_once RSS_PI_PATH . 'app/classes/import/class-rss-pi-engine.php';
include_once RSS_PI_PATH . 'app/classes/import/class-rss-pi-cron.php';

// the main loader class
include_once RSS_PI_PATH . 'app/class-rss-post-importer.php';

// initialise plugin as a global var
global $rss_post_importer;
$rss_post_importer = new rssPostImporter();
$rss_post_importer->init();

// initiate the updater logic
include_once 'update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/lzomedia/rss.wordpress.plugin',
    __FILE__,
    'rss.wordpress.plugin'
);

$myUpdateChecker->setBranch('master');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();
