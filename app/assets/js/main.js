(function($) {

$('document').ready(function(){
	// Edit-buttons
	$('body').on('click', 'a.toggle-edit', function () {
        var target = $(this).attr('data-target');

        var modified = $('#modified_feeds').val();

        if (modified) {
            modified = modified.split(',');
        } else {
            modified = [target];
        }

        var index = modified.indexOf(target);
        if ($('#display_' + target).hasClass('show')) {
            $('#edit_' + target).remove();
            $('#display_' + target).toggleClass('show');

            if (index > -1) {
                modified.splice(index, 1);
            }

            return false;
        } else {
            if (index == -1) {
                modified.push(target);
            }
        }

        $('#modified_feeds').val(modified.join(','));

		$.ajax({
			type: 'POST',
			url: rss_pi.ajaxurl,
			data: ({
				action: 'rss_pi_edit_row',
                feed_id: $(this).attr('data-target')
			}),
			success: function (data) {
                $(data).insertAfter($('#display_' + target));
                $('#edit_' + target).toggleClass('show');
                $('#display_' + target).toggleClass('show');
			}
		});

		return false;
	});

	// Delete-buttons
	$('body').on('click', 'a.delete-row', function () {
        var target = $(this).attr('data-target');

        var deleted = $('#deleted_feeds').val();
        var modified = $('#modified_feeds').val();
        var new_feeds = $('#new_feeds').val();

        if (deleted) {
            deleted = deleted.split(',');
            deleted.push(target);
        } else {
            deleted = [target];
        }

        if (modified) {
            modified = modified.split(',');
            var index = modified.indexOf(target);
            if (index > -1) {
                modified.splice(index, 1);
            }
            $('#modified_feeds').val(modified.join(','));
        }

        if (new_feeds) {
            new_feeds = new_feeds.split(',');
            var index = new_feeds.indexOf(target);
            if (index > -1) {
                new_feeds.splice(index, 1);
            }
            $('#new_feeds').val(new_feeds.join(','));
        }


        $('#deleted_feeds').val(deleted.join(','));

		$('#edit_' + target).remove();
		$('#display_' + target).remove();

		return false;
	});


	// status-buttons
	$('body').on('click', 'a.status-row', function () {
		var action = $(this).attr('data-action');
		var target = $(this).attr('data-target');

		if (action == 'pause') {
		   $(this).attr('data-action', 'enable');
		   $(this).html('Enable Feed');
		} else {
		   $(this).attr('data-action', 'pause');
		   $(this).html('Pause');
	    }

		var paused_feeds = $('#paused_feeds').val();
        if (paused_feeds) {
            paused_feeds = paused_feeds.split(',');
            paused_feeds.push(target);
        } else {
            paused_feeds = [target];
        }

		$('#paused_feeds').val(paused_feeds.join(','));

		return false;
	});

	if ( $("#rss_pi-feed-table").length ) {

		$("#rss_pi-feed-table").on("rss-pi-changed", "tr", function () {
			var $tr = $(this),
				id = $tr.attr("id").replace("display_","").replace("edit_",""),
				$tr_data = $("#display_"+id),
				$tr_edit = $("#edit_"+id),
				fields = $tr_data.data("fields").split(",");
			$.each(fields,function(i){
				var field = ".field-"+fields[i];
				$tr_data.find(field).text($tr_edit.find(field).val());
			});
			$tr_data.addClass("rss-pi-unsaved");
		});

		var do_save = false;
		$(window).bind('beforeunload', function() {
			if( ! do_save && $("#rss_pi-feed-table .rss-pi-unsaved").length ){
				return rss_pi.l18n.unsaved;
			}
		});
		$("#rss_pi-settings-form").on("submit",function(){
			do_save = true;
		});
		// Monitor dynamic inputs
		$("#rss_pi-feed-table").on('change', ':input', function(){ //triggers change in all input fields including text type
			$(this).parents("tr.edit-row").trigger("rss-pi-changed");
		});

	}

	$('a.add-row').on('click', function (e) {
		e.preventDefault();
		var target = uniqid();

		$.ajax({
			type: 'POST',
			url: rss_pi.ajaxurl,
			data: ({
				action: 'rss_pi_add_row',
                feed_id: target
			}),
			success: function (data) {
                $('.rss-rows').append(data);
                $("#" + target + "-name").focus().select();
			}
		});

        var new_feeds = $('#new_feeds').val();

        if (new_feeds) {
            new_feeds = new_feeds.split(',');
            new_feeds.push(target);
        } else {
            new_feeds = [target];
        }

        $('#new_feeds').val(new_feeds.join(','));
	});

	$('#save_and_import').on('click', function () {
		$('#save_to_db').val('true');
	});

	if ( Modernizr !== undefined && Modernizr.input.min && Modernizr.input.max )
	$("#rss_pi-settings-form [type='submit']").on("click",function(e){
		$("[name$='-max_posts']").each(function(){
			var max_posts = {
				val: parseInt($(this).val()),
				min: parseInt($(this).attr("min")),
				max: parseInt($(this).attr("max")),
				id: $(this).attr("id").replace("-max_posts","")
			}
			if ( max_posts.val < max_posts.min || max_posts.val > max_posts.max ) {
				$("#edit_"+max_posts.id).addClass("show");
				$("#display_"+max_posts.id).addClass("show");
			}
		});
	});

	$('a.load-log').on('click', function () {
		$('#main_ui').hide();
		$('.ajax_content').html('<img src="/wp-admin/images/wpspin_light.gif" alt="" class="loader" />');
		$.ajax({
			type: 'POST',
			url: rss_pi.ajaxurl,
			data: ({
				action: 'rss_pi_load_log'
			}),
			success: function (data) {
				$('.ajax_content').html(data);
			}
		});
		return false;
	});

	$('body').delegate('a.show-main-ui', 'click', function () {
		$('#main_ui').show();
		$('.ajax_content').html('');
		return false;
	});

	$('body').delegate('a.clear-log', 'click', function () {
		$.ajax({
			type: 'POST',
			url: rss_pi.ajaxurl,
			data: ({
				action: 'rss_pi_clear_log'
			}),
			success: function (data) {
				$('.log').html(data);
			}
		});
		return false;
	});

	$("#from_date").datepicker();
	$("#till_date").datepicker();

	if ( $("#rss_pi-stats-placeholder").length ) {
		rss_filter_stats = function(form) {
			var data = {
					action: "rss_pi_stats",
					rss_from_date: $("#from_date").val() || "",
					rss_till_date: $("#till_date").val() || ""
				},
				$loading = false;
			if (form && $("#submit-rss_filter_stats").length) {
				data.rss_filter_stats = $("#submit-rss_filter_stats").val();
			} else {
				$loading = $('<div class="rss_pi_overlay"><img class="rss_pi_loading" src="'+rss_pi.pluginurl+'app/assets/img/loading.gif" /><p>Stats are loading. Please wait...</p></div>').appendTo("#rss_pi-stats-placeholder");
			}
			$.ajax({
				type: "POST",
				url: rss_pi.ajaxurl,
				data: data,
				success: function (data) {
					if ($loading) { $loading.remove(); $loading = false; }
					$("#rss_pi-stats-placeholder").empty().append(data);
					drawChart();
					$("#from_date").datepicker();
					$("#till_date").datepicker();
					$("#submit-rss_filter_stats").on("click",function(e){
						e.preventDefault();
						$loading = $('<div class="rss_pi_overlay"><img class="rss_pi_loading" src="'+rss_pi.pluginurl+'app/assets/img/loading.gif" /><p>Stats are loading. Please wait...</p></div>').appendTo("#rss_pi-stats-placeholder");
						rss_filter_stats(true);
					});
				}
			});
		};
		rss_filter_stats();
	}

	if ( $("#rss_pi_progressbar").length && feeds !== undefined && feeds.count ) {
		var import_feed = function(id) {

			$.ajax({
				type: 'POST',
				url: rss_pi.ajaxurl,
				data: {
					action: 'rss_pi_import',
					feed: id
				},
				success: function (data) {
					var data = data.data || {};
					$("#rss_pi_progressbar").progressbar({
						value: feeds.processed()
					});
					$("#rss_pi_progressbar_label .processed").text(feeds.processed());
					if ( data.count !== undefined ) feeds.imported(data.count);
					if (feeds.left()) {
						$("#rss_pi_progressbar_label .count").text(feeds.imported());
						import_feed(feeds.get());
					} else {
						$("#rss_pi_progressbar_label").html("Import completed. Imported posts: " + feeds.imported());
					}
				}
			});
		}
		$("#rss_pi_progressbar").progressbar({
			value: 0,
			max: feeds.total()
		});
		$("#rss_pi_progressbar_label").html("Import in progres. Processed feeds: <span class='processed'>0</span> of <span class='max'>"+feeds.total()+"</span>. Imported posts so far: <span class='count'>0</span>");
		import_feed(feeds.get());
	}


/*This is for custom frequency*/
  $("#frequency").change(function(){

	  if($(this).val()=="custom_frequency")
	  {
		  $("#rss_custom_frequency").show();
		  $("#rss_custom_frequency").focus();
	  }else{
		 $("#rss_custom_frequency").val('');
		 $("#rss_custom_frequency").hide();
	  }


	  })

        var url = location.href;

        var myParam1 = location.search.split('version=')[1];


		if (typeof(myParam1) == 'undefined')
		{
		 var api_add = "premium";
		 window.location.assign(window.location.href+='&version=1.0&type='+api_add);
		}

		else{}

		console.log(myParam1);


});

})(jQuery);

function update_ids() {
	var feed_ids = jQuery("#rss_pi-feed-table > tbody input[name='id']").map(function () {
		return jQuery(this).val();
	}).get().join();

	jQuery('#feed_ids').val(feed_ids);
}

var feeds = {
	ids: feeds || [],
	count: feeds && feeds.length ? feeds.length : 0,
	imported_posts: 0,
	set: function(ids){
		this.ids = ids;
		this.count = ids.length;
	},
	get: function(){
		return this.ids.splice(0,1)[0];
	},
	left: function(){
		return this.ids.length;
	},
	processed: function(){
		return this.count - this.ids.length;
	},
	total: function(){
		return this.count;
	},
	imported: function(num){
		if ( num !== undefined && !isNaN(parseInt(num)) ) this.imported_posts += parseInt(num);
		return this.imported_posts;
	}
};
