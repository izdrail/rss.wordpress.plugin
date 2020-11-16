<div class="postbox">
	<div class="inside">
		<div class="misc-pub-section">
			<h3 class="version">V. <?php echo RSS_PI_VERSION; ?></h3>
			<ul>
				<li>
					<i class="icon-calendar"></i> <?php _e("Latest import:", 'rss-post-importer'); ?> <strong><?php echo $this->options['latest_import'] ? $this->options['latest_import'] : 'never' ; ?></strong>
				</li>
				<li><i class="icon-eye-open"></i> <a href="#" class="load-log"><?php _e("View the log", 'rss-post-importer'); ?></a></li>
			</ul>
		</div>
		<div id="major-publishing-actions">
			<input class="button button-primary button-large right" type="submit" name="info_update" value="<?php _e('Save', 'rss-post-importer'); ?>" />
			<input class="button button-large" type="submit" name="info_update" value="<?php _e('Save and import', "rss-post-importer"); ?>" id="save_and_import" />
		</div>
	</div>
</div>
