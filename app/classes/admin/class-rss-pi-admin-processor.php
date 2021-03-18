<?php

/**
 * Processes the admin screen form submissions
 *
 */
class rssPIAdminProcessor {

    /**
     * If we have a valid api key
     *
     * @var boolean
     */
    var $is_key_valid = true;

    /**
     * Process the form result
     *
     * @global object $rss_post_importer
     * @return null
     */
    function process() {
        global $rss_post_importer;

        // bail if there's nothing to process or the data is invalid
        if (! isset($_POST['info_update']) ||
            ! wp_verify_nonce($_POST['rss_pi_nonce'], 'settings_page')) {
            return;
        }

        // formulate the settings array
        $settings = $this->process_settings();

        // check result for "invalid_key" flag
        $invalid_api_key = isset($settings['invalid_api_key']);
        unset($settings['invalid_api_key']);

        // update cron settings
        $this->update_cron($settings['frequency']);

        $feeds = $this->process_feeds($rss_post_importer->options['feeds']);

        // import CSV file
        if (isset($_FILES['import_csv']) && $settings['is_key_valid']) {
            $feeds = $this->import_csv($feeds);
        }

        // import OPML file
        // @since v2.1.3
        if (isset($_FILES['import_opml']) && is_uploaded_file($_FILES['import_opml']['tmp_name'])) {
            $opml = new Rss_pi_opml();
            $feeds = $opml->import($feeds);
            $opml_errors = $opml->errors;
        } else {
            $opml_errors = array();
        }

        // save and reload the options
        $this->save_reload_options($settings, $feeds);

        wp_redirect(add_query_arg(
            array(
                'settings-updated' => 'true',
                // yield the routine for import feeds via AJAX when needed
                'import' => ( $_POST['save_to_db'] == 'true' ),
                'message' => $invalid_api_key ? 2 : 1,
                //'opml_errors' => $opml_errors ? urlencode(implode('<br/>', $opml_errors)) : '',
            ),
            $rss_post_importer->page_link
        ));

        exit;
    }

    /**
     * Purge "deleted_posts" cache from wp_options
     * @return void
     */
    function purge_deleted_posts_cache() {
        if (! isset($_POST['purge_deleted_cache'])) return;

        delete_option('rss_pi_deleted_posts');
        delete_option('rss_pi_imported_posts');

        global $rss_post_importer;

        wp_redirect(add_query_arg(
            array(
                'deleted_cache_purged' => 'true',
            ),
            $rss_post_importer->page_link
        ));

        exit;

    }

    /**
     * Import CSV function to import CSV file data into database

     * @return array
     */
    private function import_csv($feeds) {

        if (is_uploaded_file($_FILES['import_csv']['tmp_name'])) {
            $file = $_FILES['import_csv']['tmp_name'];
            $fcount = file($file);
            $linescount = count($fcount) - 1;
            $file_handle = fopen($file, "r");
            $t = 0;
            $titlearray = array();
            while ($csv_line = fgetcsv($file_handle, 1024)) {

                if ($t <> 0) {

                    for ($i = 0, $j = count($csv_line); $i < $j; $i++) {
                        if ($i == 0)
                            $importdata['feeds'][$t - 1]['id'] = uniqid("54d4c" . $t);

                        $importdata['feeds'][$t - 1][$titlearray[$i]] = $csv_line[$i];
                    }
                }
                else {
                    for ($i = 0, $j = count($csv_line); $i < $j; $i++) {
                        $titlearray[] = $csv_line[$i];
                    }
                }
                $t++;
            }
            fclose($file_handle) or die("can't close file");

            if (!empty($importdata['feeds'])) {
                for ($r = 0; $r < count($importdata['feeds']); $r++) {
                    if ( isset($importdata['feeds'][$r]['category_id']) ) {
                        $importdata['feeds'][$r]['category_id'] = explode(',',$importdata['feeds'][$r]['category_id']);
                        $importdata['feeds'][$r]['tags_id'] = explode(',',$importdata['feeds'][$r]['tags_id']);
                        $importdata['feeds'][$r]['keywords'] = explode(',',$importdata['feeds'][$r]['keywords']);
                        $importdata['feeds'][$r]['strip_html'] = $importdata['feeds'][$r]['strip_html']; // this is a STRING, not a BOOLEAN
                    } else {
                        $importdata['feeds'][$r]['category_id'] = array(1);
                        $importdata['feeds'][$r]['tags_id'] = "";
                        $importdata['feeds'][$r]['keywords'] = "";
                        $importdata['feeds'][$r]['strip_html'] = "false";
                    }

                    $check_result = $this->check_feed_exist($feeds, $importdata['feeds'][$r]);

                    if ($check_result) {
                        unset($importdata['feeds'][$r]);
                    } else {
                        array_push($feeds, $importdata['feeds'][$r]);
                    }
                }
            }
        }

        return $feeds;
    }

    function check_feed_exist($feeds, $csvlink) {
        if (!empty($feeds) && !empty($csvlink)) {

            for ($i = 0; $i < count($feeds); $i++) {
                if ($feeds[$i]['url'] == $csvlink['url']) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Process submitted data to formulate settings array
     *
     * @global object $rss_post_importer
     * @return array
     */
    private function process_settings() {

        // Get selected settings for all imported posts

        // Code added for custom frequency
        $frequency_check = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : '';

        if ($frequency_check == "custom_frequency") {
            $rss_custom_frequency = intval($_POST['rss_custom_frequency']);
            $frequency = "minutes_" . $rss_custom_frequency;
            $custom_frequency = 'true';
            // Adding option for custom cron
            $rss_custom_cron_frequency = serialize(
                array(
                    'time' => $rss_custom_frequency,
                    'frequency' => $frequency
                )
            );

            delete_option( 'rss_custom_cron_frequency' );
            add_option('rss_custom_cron_frequency', $rss_custom_cron_frequency);
        } else {
            $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : '';
            $custom_frequency = 'false';

            // Delete custom cron if not exixts
            delete_option( 'rss_custom_cron_frequency' );
        }


        $settings = array(
            'frequency' => $frequency,
            'feeds_api_key' => '',
            'post_template' => stripslashes_deep($_POST['post_template']),
            'post_status' => sanitize_text_field($_POST['post_status']),
            'author_id' => 1,
            'allow_comments' => isset($_POST['allow_comments']) ? sanitize_text_field($_POST['allow_comments']) : '',
            'block_indexing' => sanitize_text_field($_POST['block_indexing']),
            'nofollow_outbound' =>sanitize_text_field($_POST['nofollow_outbound']),
            'enable_logging' => sanitize_text_field($_POST['enable_logging']),
            'tw_show' => isset($_POST['tw_show']) ? sanitize_text_field($_POST['tw_show']) : '',
            'gg_show' => isset($_POST['gg_show']) ? sanitize_text_field($_POST['gg_show']) : '',
            'og_show' => isset($_POST['og_show']) ? sanitize_text_field($_POST['og_show']) : '',
            'import_images_locally' => sanitize_text_field($_POST['import_images_locally']),
            'disable_thumbnail' => sanitize_text_field($_POST['disable_thumbnail']),
            // these values are setup after key_validity check via filter()
            'keywords' => array(),
            'cache_deleted' => 'true',
            'custom_frequency' => $custom_frequency
        );

        global $rss_post_importer;

        // save key validity state
        $settings['is_key_valid'] = true;

        // filter the settings and then send them back for saving
        return $this->filter($settings);
    }

    /**
     * Update the frequency of the import cron job
     *
     * @param string $frequency
     */
    private function update_cron($frequency) {

        // If cron settings have changed
        if (wp_get_schedule('rss_pi_cron') != $frequency) {

            // Reset cron
            wp_clear_scheduled_hook('rss_pi_cron');
            wp_schedule_event(time(), $frequency, 'rss_pi_cron');
        }
    }

    /**
     * Creates the feeds array from the submitted data
     *
     * @return array
     */
    private function process_feeds($feeds) {
        $paused_feeds = array();
        if (isset($_POST['paused_feeds'])) {
            $paused_feeds = explode(',', $_POST['paused_feeds']);
        }

        $deleted_feeds = array();
        if (isset($_POST['deleted_feeds'])) {
            $deleted_feeds = explode(',', $_POST['deleted_feeds']);
        }

        $modified_feeds = array();
        if (isset($_POST['modified_feeds'])) {
            $modified_feeds = explode(',', $_POST['modified_feeds']);
        }

        $new_feeds = array();
        if (isset($_POST['new_feeds'])) {
            $new_feeds = explode(',', $_POST['new_feeds']);
        }

        foreach ($feeds as $key => $feed) {
            if (in_array($feed['id'], $paused_feeds)) {
                $feeds[$key]['feed_status'] = 'pause';
            } else {
                $feeds[$key]['feed_status'] = 'active';
            }

            if (in_array($feed['id'], $deleted_feeds)) {
                unset($feeds[$key]);
            }

            if (in_array($feed['id'], $modified_feeds)) {
                $keywords = array();

                if (isset($_POST[$feed['id'] . '-keywords'])) {
                    $keyword_str = $_POST[$feed['id'] . '-keywords'];
                }
                if (! empty($keyword_str)) {
                    $keywords = explode(',', $keyword_str);
                }

                $feed['url'] = $_POST[$feed['id'] . '-url'];
                $feed['name'] = $_POST[$feed['id'] . '-name'];
                $feed['max_posts'] = intval($_POST[$feed['id'] . '-max_posts']);
                $feed['author_id'] = ($this->is_key_valid && isset($_POST[$feed['id'] . '-author_id'])) ? intval($_POST[$feed['id'] . '-author_id']) : intval($_POST['author_id']);
                $feed['category_id'] = (isset($_POST[$feed['id'] . '-category_id'])) ? $_POST[$feed['id'] . '-category_id'] : '';
                $feed['tags_id'] = (isset($_POST[$feed['id'] . '-tags_id'])) ? $_POST[$feed['id'] . '-tags_id'] : '';
                $feed['keywords'] = array_map('trim', $keywords);
                $feed['strip_html'] = (isset($_POST[$feed['id'] . '-strip_html'])) ? $_POST[$feed['id'] . '-strip_html'] : '';
                $feed['nofollow_outbound'] = (isset($_POST[$feed['id'] . '-nofollow_outbound'])) ? $_POST[$feed['id'] . '-nofollow_outbound'] : '';
                $feed['automatic_import_categories'] = (isset($_POST[$feed['id'] . '-automatic_import_categories'])) ? $_POST[$feed['id'] . '-automatic_import_categories'] : '';
                $feed['automatic_import_author'] = (isset($_POST[$feed['id'] . '-automatic_import_author'])) ? $_POST[$feed['id'] . '-automatic_import_author'] : '';
                $feed['canonical_urls'] = (isset($_POST[$feed['id'] . '-canonical_urls'])) ? $_POST[$feed['id'] . '-canonical_urls'] : '';

                $feeds[$key] = $feed;
            }
        }

        foreach ($new_feeds as $id) {
            if (! $id)  continue;

            $keywords = array();
            if (isset($_POST[$id . '-keywords'])) {
                $keyword_str = $_POST[$id . '-keywords'];
            }
            if (! empty($keyword_str)) {
                $keywords = explode(',', $keyword_str);
            }

            $feed_status = in_array($id, $paused_feeds) ? 'pause' : 'active';

            array_push($feeds, array(
                'id' => $id,
                'url' => $_POST[$id . '-url'],
                'name' => $_POST[$id . '-name'],
                'max_posts' => intval($_POST[$id . '-max_posts']),
                // different author ids depending on valid API keys
                'author_id' => 1,
                'category_id' => (isset($_POST[$id . '-category_id'])) ? $_POST[$id . '-category_id'] : '',
                'tags_id' => (isset($_POST[$id . '-tags_id'])) ? $_POST[$id . '-tags_id'] : '',
                'keywords' => array_map('trim',$keywords),
                'strip_html' => (isset($_POST[$id . '-strip_html'])) ? $_POST[$id . '-strip_html'] : '',
                'nofollow_outbound' => (isset($_POST[$id . '-nofollow_outbound'])) ? $_POST[$id . '-nofollow_outbound'] : '',
                'automatic_import_categories' => (isset($_POST[$id . '-automatic_import_categories'])) ? $_POST[$id . '-automatic_import_categories'] : '',
                'automatic_import_author' => (isset($_POST[$id . '-automatic_import_author'])) ? $_POST[$id . '-automatic_import_author'] : '',
                'canonical_urls' => (isset($_POST[$id . '-canonical_urls'])) ? $_POST[$id . '-canonical_urls'] : '',
                'feed_status' => $feed_status
            ));
        }

        return $feeds;
    }

    /**
     * Update options and reload global options
     *
     * @global type $rss_post_importer
     * @param array $settings
     * @param array $feeds
     */
    private function save_reload_options($settings, $feeds) {
        global $rss_post_importer;

        // existing options
        $options = $rss_post_importer->options;

        // new data
        $new_options = array(
            'feeds' => $feeds,
            'settings' => $settings,
            'latest_import' => $options['latest_import'],
            'imports' => $options['imports'],
            'upgraded' => $options['upgraded']
        );

        // update in db
        update_option('rss_pi_feeds', $new_options);

        // reload so that the new options are used henceforth
        $rss_post_importer->load_options();
    }

    /**
     * Filter settings for API key vs non-API key installs
     *
     * @param array $settings
     * @return array
     */
    private function filter($settings) {

        // if the key is not fine
        // set up keywords (otherwise don't)
        if (isset($_POST['keyword_filter']))

            // Strip Slases for RegEx
            $keyword_str = stripslashes($_POST['keyword_filter']);

        $keywords = array();

        if (!empty($keyword_str)) {
            $keywords = explode(',', $keyword_str);
        }

        $settings['keywords'] = array_map('trim',$keywords);

        // set up "import deleted posts" (otherwise don't)
        $settings['cache_deleted'] = isset($_POST['cache_deleted']) ? $_POST['cache_deleted'] : 'true';

        return $settings;
    }

}
