<table class="widefat rss_pi-table" id="rss_pi-feed-table">
	<thead>
		<tr>
			<th><?php _e("Feed name", 'rss-post-importer'); ?></th>
			<th><?php _e("Feed url", 'rss-post-importer'); ?></th>
			<th><?php _e("Max posts / import", 'rss-post-importer'); ?></th>
			<!--<th><?php _e("Category", 'rss-post-importer'); ?></th>-->
		</tr>
	</thead>
	<tbody class="rss-rows">
		<?php
        $feed_ids = array();
        $paused_feeds = array();

		if (is_array($this->options['feeds']) && count($this->options['feeds']) > 0) :
			foreach ($this->options['feeds'] as $f) :
                if ($f['feed_status'] == 'pause') array_push($paused_feeds, $f['id']);

				$category = get_the_category($f['category_id']);
				//array_push($feed_ids, $f['id']);
				include(RSS_PI_PATH . 'app/templates/feed-table-row.php');
			endforeach;
		else :
			?>
			<tr>
				<td colspan="4" class="empty_table">
					<?php _e('You haven\'t specified any feeds to import yet, why don\'t you <a href="#" class="add-row">add one now</a>?', "rss-post-importer"); ?>
				</td>
			</tr>
		<?php
		endif
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<a href="#" class="button button-large button-primary add-row">
					<?php _e('Add new feed', "rss-post-importer"); ?>
				</a>
				<!--<input type="hidden" name="feed_ids" id="feed_ids" value="<?php echo(join($feed_ids, ',')); ?>" />-->
				<input type="hidden" name="deleted_feeds" id="deleted_feeds" value="" />
				<input type="hidden" name="modified_feeds" id="modified_feeds" value="" />
				<input type="hidden" name="new_feeds" id="new_feeds" value="" />
                <input type="hidden" id="paused_feeds" name="paused_feeds" value="<?php echo(join($paused_feeds, ',')); ?>" />
			</td>
		</tr>
<?php
		// preload an empty (and hidden by css) "new feed" row
		unset($f);
		include(RSS_PI_PATH . 'app/templates/feed-table-row.php');
?>
	</tfoot>
</table>
<style>
.rss_pi-table tfoot tr.data-row,.rss_pi-table tfoot tr.edit-row{display:none;}
</style>
