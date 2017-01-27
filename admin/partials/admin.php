<?php
if ( basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__) )
	exit( 'Please do not load this page directly' );

// Set active tab
if ( isset( $_GET['tab'] ) )
	$current = $_GET['tab'];
else
	$current = 'stats';

// Update options on form submission
if ( isset($_POST['section']) ) {

	if ( "stats" == $_POST['section'] ) {

		$current = 'stats';

		if ( isset( $_POST['wpp-admin-token'] ) && wp_verify_nonce( $_POST['wpp-admin-token'], 'wpp-update-stats-options' ) ) {

			$this->options['stats']['order_by'] = $_POST['stats_order'];
			$this->options['stats']['limit'] = ( WPP_Helper::is_number( $_POST['stats_limit'] ) && $_POST['stats_limit'] > 0 ) ? $_POST['stats_limit'] : 10;
			$this->options['stats']['post_type'] = empty( $_POST['stats_type'] ) ? "post,page" : $_POST['stats_type'];
			$this->options['stats']['freshness'] = empty( $_POST['stats_freshness'] ) ? false : $_POST['stats_freshness'];

			update_site_option( 'wpp_settings_config', $this->options );
			echo "<div class=\"updated\"><p><strong>" . __( 'Settings saved.', 'wordpress-popular-posts' ) . "</strong></p></div>";

		}

	}
	elseif ( "misc" == $_POST['section'] ) {

		$current = 'tools';

		if ( isset( $_POST['wpp-admin-token'] ) && wp_verify_nonce( $_POST['wpp-admin-token'], 'wpp-update-misc-options' ) ) {

			$this->options['tools']['link']['target'] = $_POST['link_target'];
			$this->options['tools']['css'] = $_POST['css'];

			update_site_option( 'wpp_settings_config', $this->options );
			echo "<div class=\"updated\"><p><strong>" . __( 'Settings saved.', 'wordpress-popular-posts' ) . "</strong></p></div>";

		}
	}
	elseif ( "thumb" == $_POST['section'] ) {

		$current = 'tools';

		if ( isset( $_POST['wpp-admin-token'] ) && wp_verify_nonce( $_POST['wpp-admin-token'], 'wpp-update-thumbnail-options' ) ) {

			if (
				$_POST['thumb_source'] == "custom_field"
				&& ( !isset( $_POST['thumb_field'] ) || empty( $_POST['thumb_field'] ) )
			) {
				echo '<div id="wpp-message" class="error fade"><p>'.__( 'Please provide the name of your custom field.', 'wordpress-popular-posts' ).'</p></div>';
			} else {

				$this->options['tools']['thumbnail']['source'] = $_POST['thumb_source'];
				$this->options['tools']['thumbnail']['field'] = ( !empty( $_POST['thumb_field']) ) ? $_POST['thumb_field'] : "wpp_thumbnail";
				$this->options['tools']['thumbnail']['default'] = ( !empty( $_POST['upload_thumb_src']) ) ? $_POST['upload_thumb_src'] : "";
				$this->options['tools']['thumbnail']['resize'] = $_POST['thumb_field_resize'];
				$this->options['tools']['thumbnail']['responsive'] = $_POST['thumb_responsive'];

				update_site_option( 'wpp_settings_config', $this->options );
				echo "<div class=\"updated\"><p><strong>" . __( 'Settings saved.', 'wordpress-popular-posts' ) . "</strong></p></div>";

			}

		}

	}
	elseif ( "data" == $_POST['section'] ) {

		$current = 'tools';

		if ( isset( $_POST['wpp-admin-token'] ) && wp_verify_nonce( $_POST['wpp-admin-token'], 'wpp-update-data-options' ) ) {

			$this->options['tools']['log']['level'] = $_POST['log_option'];
			$this->options['tools']['log']['limit'] = $_POST['log_limit'];
			$this->options['tools']['log']['expires_after'] = ( WPP_Helper::is_number( $_POST['log_expire_time'] ) && $_POST['log_expire_time'] > 0 )
			  ? $_POST['log_expire_time']
			  : $this->default_user_settings['tools']['log']['expires_after'];
			$this->options['tools']['ajax'] = $_POST['ajax'];

			// if any of the caching settings was updated, destroy all transients created by the plugin
			if (
				$this->options['tools']['cache']['active'] != $_POST['cache']
				|| $this->options['tools']['cache']['interval']['time'] != $_POST['cache_interval_time']
				|| $this->options['tools']['cache']['interval']['value'] != $_POST['cache_interval_value']
			) {
				$this->flush_transients();
			}

			$this->options['tools']['cache']['active'] = $_POST['cache'];
			$this->options['tools']['cache']['interval']['time'] = $_POST['cache_interval_time'];
			$this->options['tools']['cache']['interval']['value'] = ( isset( $_POST['cache_interval_value'] ) && WPP_Helper::is_number( $_POST['cache_interval_value'] ) && $_POST['cache_interval_value'] > 0 )
			  ? $_POST['cache_interval_value']
			  : 1;

			$this->options['tools']['sampling']['active'] = $_POST['sampling'];
			$this->options['tools']['sampling']['rate'] = ( isset( $_POST['sample_rate'] ) && WPP_Helper::is_number( $_POST['sample_rate'] ) && $_POST['sample_rate'] > 0 )
			  ? $_POST['sample_rate']
			  : 100;

			update_site_option( 'wpp_settings_config', $this->options );
			echo "<div class=\"updated\"><p><strong>" . __( 'Settings saved.', 'wordpress-popular-posts' ) . "</strong></p></div>";

		}
	}

}

if ( $this->options['tools']['css'] && !file_exists( get_stylesheet_directory() . '/wpp.css' ) ) {
	echo '<div id="wpp-message" class="update-nag">'. __( 'Any changes made to WPP\'s default stylesheet will be lost after every plugin update. In order to prevent this from happening, please copy the wpp.css file (located at wp-content/plugins/wordpress-popular-posts/style) into your theme\'s directory', 'wordpress-popular-posts' ) .'.</div>';
}

$rand = md5( uniqid(rand(), true) );

if ( !$wpp_rand = get_site_option("wpp_rand") ) {
	add_site_option( "wpp_rand", $rand );
} else {
	update_site_option( "wpp_rand", $rand );
}

?>
<script type="text/javascript">
	// TOOLS
	function confirm_reset_cache() {
		if ( confirm("<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( "This operation will delete all entries from WordPress Popular Posts' cache table and cannot be undone.", 'wordpress-popular-posts'); ?> \n\n" + "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( "Do you want to continue?", 'wordpress-popular-posts' ); ?>") ) {
			jQuery.post(
				ajaxurl,
				{
					action: 'wpp_clear_data',
					token: '<?php echo get_site_option("wpp_rand"); ?>',
					clear: 'cache'
				}, function(data){
					var response = "";

					switch( data ) {
						case "1":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Success! The cache table has been cleared!', 'wordpress-popular-posts' ); ?>";
							break;

						case "2":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Error: cache table does not exist.', 'wordpress-popular-posts' ); ?>";
							break;

						case "3":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;

						case "4":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Sorry, you do not have enough permissions to do this. Please contact the site administrator for support.', 'wordpress-popular-posts' ); ?>";
							break;

						default:
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;
					}

					alert( response );
				}
			);
		}
	}

	function confirm_reset_all() {
		if ( confirm("<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( "This operation will delete all stored info from WordPress Popular Posts' data tables and cannot be undone.", 'wordpress-popular-posts'); ?> \n\n" + "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e("Do you want to continue?", 'wordpress-popular-posts'); ?>")) {
			jQuery.post(
				ajaxurl,
				{
					action: 'wpp_clear_data',
					token: '<?php echo get_site_option("wpp_rand"); ?>',
					clear: 'all'
				}, function(data){
					var response = "";

					switch( data ) {
						case "1":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Success! All data have been cleared!', 'wordpress-popular-posts' ); ?>";
							break;

						case "2":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Error: one or both data tables are missing.', 'wordpress-popular-posts' ); ?>";
							break;

						case "3":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;

						case "4":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Sorry, you do not have enough permissions to do this. Please contact the site administrator for support.', 'wordpress-popular-posts' ); ?>";
							break;

						default:
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;
					}

					alert( response );
				}
			);
		}
	}

	function confirm_clear_image_cache() {
		if ( confirm("<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e("This operation will delete all cached thumbnails and cannot be undone.", 'wordpress-popular-posts'); ?> \n\n" + "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( "Do you want to continue?", 'wordpress-popular-posts' ); ?>") ) {
			jQuery.post(
				ajaxurl,
				{
					action: 'wpp_clear_thumbnail',
					token: '<?php echo get_site_option("wpp_rand"); ?>'
				}, function(data){
					var response = "";

					switch( data ) {
						case "1":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Success! All files have been deleted!', 'wordpress-popular-posts' ); ?>";
							break;

						case "2":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'The thumbnail cache is already empty!', 'wordpress-popular-posts' ); ?>";
							break;

						case "3":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;

						case "4":
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Sorry, you do not have enough permissions to do this. Please contact the site administrator for support.', 'wordpress-popular-posts' ); ?>";
							break;

						default:
							response = "<?php /*translators: Special characters (such as accents) must be replaced with Javascript Octal codes (eg. \341 is the Octal code for small a with acute accent) */ _e( 'Invalid action.', 'wordpress-popular-posts' ); ?>";
							break;
					}

					alert( response );
				}
			);
		}
	}

	jQuery(document).ready(function($){
		<?php if ( "params" != $current ) : ?>
		$('.wpp_boxes:visible').css({
			display: 'inline',
			float: 'left'
		}).width( $('.wpp_boxes:visible').parent().width() - $('.wpp_box').outerWidth() - 15 );

		$(window).on('resize', function(){
			$('.wpp_boxes:visible').css({
				display: 'inline',
				float: 'left'
			}).width( $('.wpp_boxes:visible').parent().width() - $('.wpp_box').outerWidth() - 15 );
		});
		<?php else: ?>
		$('.wpp_box').hide();
		<?php endif; ?>
	});
</script>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2>WordPress Popular Posts</h2>

    <h2 class="nav-tab-wrapper">
    <?php
    // build tabs
    $tabs = array(
        'stats' => __( 'Stats', 'wordpress-popular-posts' ),
		'tools' => __( 'Tools', 'wordpress-popular-posts' ),
		'params' => __( 'Parameters', 'wordpress-popular-posts' ),
		'about' => __( 'About', 'wordpress-popular-posts' )
    );
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=wordpress-popular-posts&tab=$tab'>$name</a>";
    }
    ?>
    </h2>

    <!-- Start stats -->
    <div id="wpp_stats" class="wpp_boxes"<?php if ( "stats" == $current ) {?> style="display:block;"<?php } ?>>
    	<p><?php _e( "Click on each tab to see what are the most popular entries on your blog in the last 24 hours, this week, last 30 days or all time since WordPress Popular Posts was installed.", 'wordpress-popular-posts' ); ?></p>

        <div class="tablenav top">
            <div class="alignleft actions">
                <form action="" method="post" id="wpp_stats_options" name="wpp_stats_options">
                    <select name="stats_order">
                        <option <?php if ($this->options['stats']['order_by'] == "comments") {?>selected="selected"<?php } ?> value="comments"><?php _e("Order by comments", 'wordpress-popular-posts'); ?></option>
                        <option <?php if ($this->options['stats']['order_by'] == "views") {?>selected="selected"<?php } ?> value="views"><?php _e("Order by views", 'wordpress-popular-posts'); ?></option>
                        <option <?php if ($this->options['stats']['order_by'] == "avg") {?>selected="selected"<?php } ?> value="avg"><?php _e("Order by avg. daily views", 'wordpress-popular-posts'); ?></option>
                    </select>
                    <label for="stats_type"><?php _e("Post type", 'wordpress-popular-posts'); ?>:</label> <input type="text" name="stats_type" value="<?php echo esc_attr( $this->options['stats']['post_type'] ); ?>" size="15" />
                    <label for="stats_limits"><?php _e("Limit", 'wordpress-popular-posts'); ?>:</label> <input type="text" name="stats_limit" value="<?php echo $this->options['stats']['limit']; ?>" size="5" />
                    <input type="hidden" name="section" value="stats" />
                    <input type="submit" class="button-secondary action" value="<?php _e("Apply", 'wordpress-popular-posts'); ?>" name="" />

                    <div class="clear"></div>
                    <label for="stats_freshness"><input type="checkbox" class="checkbox" <?php echo ($this->options['stats']['freshness']) ? 'checked="checked"' : ''; ?> id="stats_freshness" name="stats_freshness" /> <?php _e('Display only posts published within the selected Time Range', 'wordpress-popular-posts'); ?></label>

                    <?php wp_nonce_field( 'wpp-update-stats-options', 'wpp-admin-token' ); ?>
                </form>
            </div>
        </div>
        <div class="clear"></div>
        <br />
        <div id="wpp-stats-tabs">
            <a href="#" class="button-primary" rel="wpp-daily"><?php _e("Last 24 hours", 'wordpress-popular-posts'); ?></a>
            <a href="#" class="button-secondary" rel="wpp-weekly"><?php _e("Last 7 days", 'wordpress-popular-posts'); ?></a>
            <a href="#" class="button-secondary" rel="wpp-monthly"><?php _e("Last 30 days", 'wordpress-popular-posts'); ?></a>
            <a href="#" class="button-secondary" rel="wpp-all"><?php _e("All-time", 'wordpress-popular-posts'); ?></a>
        </div>
        <div id="wpp-stats-canvas">
            <div class="wpp-stats wpp-stats-active" id="wpp-daily">
                <?php echo do_shortcode("[wpp range='daily' post_type='".$this->options['stats']['post_type']."' stats_comments=1 stats_views=1 order_by='".$this->options['stats']['order_by']."' wpp_start='<ol>' wpp_end='</ol>' post_html='<li><a href=\"{url}\" target=\"_blank\" class=\"wpp-post-title\">{text_title}</a> <span class=\"post-stats\">{stats}</span></li>' limit=".$this->options['stats']['limit']." freshness=" . $this->options['stats']['freshness'] . "]"); ?>
            </div>
            <div class="wpp-stats" id="wpp-weekly">
                <?php echo do_shortcode("[wpp range='weekly' post_type='".$this->options['stats']['post_type']."' stats_comments=1 stats_views=1 order_by='".$this->options['stats']['order_by']."' wpp_start='<ol>' wpp_end='</ol>' post_html='<li><a href=\"{url}\" target=\"_blank\" class=\"wpp-post-title\">{text_title}</a> <span class=\"post-stats\">{stats}</span></li>' limit=".$this->options['stats']['limit']." freshness=" . $this->options['stats']['freshness'] . "]"); ?>
            </div>
            <div class="wpp-stats" id="wpp-monthly">
                <?php echo do_shortcode("[wpp range='monthly' post_type='".$this->options['stats']['post_type']."' stats_comments=1 stats_views=1 order_by='".$this->options['stats']['order_by']."' wpp_start='<ol>' wpp_end='</ol>' post_html='<li><a href=\"{url}\" target=\"_blank\" class=\"wpp-post-title\">{text_title}</a> <span class=\"post-stats\">{stats}</span></li>' limit=".$this->options['stats']['limit']." freshness=" . $this->options['stats']['freshness'] . "]"); ?>
            </div>
            <div class="wpp-stats" id="wpp-all">
                <?php echo do_shortcode("[wpp range='all' post_type='".$this->options['stats']['post_type']."' stats_comments=1 stats_views=1 order_by='".$this->options['stats']['order_by']."' wpp_start='<ol>' wpp_end='</ol>' post_html='<li><a href=\"{url}\" target=\"_blank\" class=\"wpp-post-title\">{text_title}</a> <span class=\"post-stats\">{stats}</span></li>' limit=".$this->options['stats']['limit']." freshness=" . $this->options['stats']['freshness'] . "]"); ?>
            </div>
        </div>
    </div>
    <!-- End stats -->

    <!-- Start tools -->
    <div id="wpp_tools" class="wpp_boxes"<?php if ( "tools" == $current ) {?> style="display:block;"<?php } ?>>

        <h3 class="wmpp-subtitle"><?php _e("Thumbnails", 'wordpress-popular-posts'); ?></h3>
        <form action="" method="post" id="wpp_thumbnail_options" name="wpp_thumbnail_options">
            <table class="form-table">
                <tbody>
                	<tr valign="top">
                        <th scope="row"><label for="thumb_default"><?php _e("Default thumbnail", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <div id="thumb-review">
                                <img src="<?php echo ( $this->options['tools']['thumbnail']['default'] ) ? $this->options['tools']['thumbnail']['default'] : plugins_url() . '/wordpress-popular-posts/public/images/no_thumb.jpg'; ?>" alt="" border="0" />
                            </div>
                            <input id="upload_thumb_button" type="button" class="button" value="<?php _e( "Upload thumbnail", 'wordpress-popular-posts' ); ?>" />
                            <input type="hidden" id="upload_thumb_src" name="upload_thumb_src" value="" />
                            <p class="description"><?php _e("How-to: upload (or select) an image, set Size to Full and click on Upload. After it's done, hit on Apply to save changes", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="thumb_source"><?php _e("Pick image from", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="thumb_source" id="thumb_source">
                                <option <?php if ($this->options['tools']['thumbnail']['source'] == "featured") {?>selected="selected"<?php } ?> value="featured"><?php _e("Featured image", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['thumbnail']['source'] == "first_image") {?>selected="selected"<?php } ?> value="first_image"><?php _e("First image on post", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['thumbnail']['source'] == "first_attachment") {?>selected="selected"<?php } ?> value="first_attachment"><?php _e("First attachment", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['thumbnail']['source'] == "custom_field") {?>selected="selected"<?php } ?> value="custom_field"><?php _e("Custom field", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                            <p class="description"><?php _e("Tell WordPress Popular Posts where it should get thumbnails from", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top" <?php if ($this->options['tools']['thumbnail']['source'] != "custom_field") {?>style="display:none;"<?php } ?> id="row_custom_field">
                        <th scope="row"><label for="thumb_field"><?php _e("Custom field name", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <input type="text" id="thumb_field" name="thumb_field" value="<?php echo esc_attr( $this->options['tools']['thumbnail']['field'] ); ?>" size="10" <?php if ($this->options['tools']['thumbnail']['source'] != "custom_field") {?>style="display:none;"<?php } ?> />
                        </td>
                    </tr>
                    <tr valign="top" <?php if ($this->options['tools']['thumbnail']['source'] != "custom_field") {?>style="display:none;"<?php } ?> id="row_custom_field_resize">
                        <th scope="row"><label for="thumb_field_resize"><?php _e("Resize image from Custom field?", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="thumb_field_resize" id="thumb_field_resize">
                                <option <?php if ( !$this->options['tools']['thumbnail']['resize'] ) {?>selected="selected"<?php } ?> value="0"><?php _e("No, I will upload my own thumbnail", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ( $this->options['tools']['thumbnail']['resize'] == 1 ) {?>selected="selected"<?php } ?> value="1"><?php _e("Yes", 'wordpress-popular-posts'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="thumb_responsive"><?php _e("Responsive support", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="thumb_responsive" id="thumb_responsive">
                                <option <?php if ($this->options['tools']['thumbnail']['responsive']) {?>selected="selected"<?php } ?> value="1"><?php _e("Enabled", 'wordpress-popular-posts'); ?></option>
                                <option <?php if (!$this->options['tools']['thumbnail']['responsive']) {?>selected="selected"<?php } ?> value="0"><?php _e("Disabled", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                            <p class="description"><?php _e("If enabled, WordPress Popular Posts will strip height and width attributes out of thumbnails' image tags", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <?php
					$wp_upload_dir = wp_upload_dir();
					if ( is_dir( $wp_upload_dir['basedir'] . "/" . 'wordpress-popular-posts' ) ) :
					?>
                    <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <input type="button" name="wpp-reset-cache" id="wpp-reset-cache" class="button-secondary" value="<?php _e("Empty image cache", 'wordpress-popular-posts'); ?>" onclick="confirm_clear_image_cache()" />
                            <p class="description"><?php _e("Use this button to clear WPP's thumbnails cache", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <?php
					endif;
					?>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="hidden" name="section" value="thumb" />
                            <input type="submit" class="button-secondary action" id="btn_th_ops" value="<?php _e("Apply", 'wordpress-popular-posts'); ?>" name="" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php wp_nonce_field( 'wpp-update-thumbnail-options', 'wpp-admin-token' ); ?>
        </form>
        <br />
        <p style="display:block; float:none; clear:both">&nbsp;</p>

        <h3 class="wmpp-subtitle"><?php _e("Data", 'wordpress-popular-posts'); ?></h3>
        <form action="" method="post" id="wpp_ajax_options" name="wpp_ajax_options">
        	<table class="form-table">
                <tbody>
                	<tr valign="top">
                        <th scope="row"><label for="log_option"><?php _e("Log views from", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="log_option" id="log_option">
                                <option <?php if ($this->options['tools']['log']['level'] == 0) {?>selected="selected"<?php } ?> value="0"><?php _e("Visitors only", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['log']['level'] == 2) {?>selected="selected"<?php } ?> value="2"><?php _e("Logged-in users only", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['log']['level'] == 1) {?>selected="selected"<?php } ?> value="1"><?php _e("Everyone", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="log_limit"><?php _e("Log limit", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="log_limit" id="log_limit">
                                <option <?php if ($this->options['tools']['log']['limit'] == 0) {?>selected="selected"<?php } ?> value="0"><?php _e("Disabled", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['log']['limit'] == 1) {?>selected="selected"<?php } ?> value="1"><?php _e("Keep data for", 'wordpress-popular-posts'); ?></option>
                            </select>

                            <label for="log_expire_time"<?php echo ($this->options['tools']['log']['limit'] == 0) ? ' style="display:none;"' : ''; ?>><input type="text" id="log_expire_time" name="log_expire_time" value="<?php echo esc_attr( $this->options['tools']['log']['expires_after'] ); ?>" size="3" /> <?php _e("day(s)", 'wordpress-popular-posts'); ?></label>

                            <p class="description"<?php echo ($this->options['tools']['log']['limit'] == 0) ? ' style="display:none;"' : ''; ?>><?php _e("Data from entries that haven't been viewed within the specified time frame will be automatically discarded", 'wordpress-popular-posts'); ?>.</p>

                            <br<?php echo ($this->options['tools']['log']['limit'] == 1) ? ' style="display:none;"' : ''; ?> />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="ajax"><?php _e("Ajaxify widget", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="ajax" id="ajax">
                                <option <?php if (!$this->options['tools']['ajax']) {?>selected="selected"<?php } ?> value="0"><?php _e("Disabled", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['ajax']) {?>selected="selected"<?php } ?> value="1"><?php _e("Enabled", 'wordpress-popular-posts'); ?></option>
                            </select>

                            <br />
                            <p class="description"><?php _e("If you are using a caching plugin such as WP Super Cache, enabling this feature will keep the popular list from being cached by it", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="cache"><?php _e("WPP Cache Expiry Policy", 'wordpress-popular-posts'); ?>:</label> <small>[<a href="https://github.com/cabrerahector/wordpress-popular-posts/wiki/7.-Performance#caching" target="_blank" title="<?php _e('What is this?', 'wordpress-popular-posts'); ?>">?</a>]</small></th>
                        <td>
                            <select name="cache" id="cache">
                                <option <?php if ( !$this->options['tools']['cache']['active'] ) { ?>selected="selected"<?php } ?> value="0"><?php _e("Never cache", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ( $this->options['tools']['cache']['active'] ) { ?>selected="selected"<?php } ?> value="1"><?php _e("Enable caching", 'wordpress-popular-posts'); ?></option>
                            </select>

                            <br />
                            <p class="description"><?php _e("Sets WPP's cache expiration time. WPP can cache the popular list for a specified amount of time. Recommended for large / high traffic sites", 'wordpress-popular-posts'); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top" <?php if ( !$this->options['tools']['cache']['active'] ) { ?>style="display:none;"<?php } ?> id="cache_refresh_interval">
                        <th scope="row"><label for="cache_interval_value"><?php _e("Refresh cache every", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                        	<input name="cache_interval_value" type="text" id="cache_interval_value" value="<?php echo ( isset($this->options['tools']['cache']['interval']['value']) ) ? (int) $this->options['tools']['cache']['interval']['value'] : 1; ?>" class="small-text">
                            <select name="cache_interval_time" id="cache_interval_time">
                            	<option <?php if ($this->options['tools']['cache']['interval']['time'] == "minute") {?>selected="selected"<?php } ?> value="minute"><?php _e("Minute(s)", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['cache']['interval']['time'] == "hour") {?>selected="selected"<?php } ?> value="hour"><?php _e("Hour(s)", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['cache']['interval']['time'] == "day") {?>selected="selected"<?php } ?> value="day"><?php _e("Day(s)", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['cache']['interval']['time'] == "week") {?>selected="selected"<?php } ?> value="week"><?php _e("Week(s)", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['cache']['interval']['time'] == "month") {?>selected="selected"<?php } ?> value="month"><?php _e("Month(s)", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ($this->options['tools']['cache']['interval']['time'] == "year") {?>selected="selected"<?php } ?> value="month"><?php _e("Year(s)", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                            <p class="description" style="display:none;" id="cache_too_long"><?php _e("Really? That long?", 'wordpress-popular-posts'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="sampling"><?php _e("Data Sampling", 'wordpress-popular-posts'); ?>:</label> <small>[<a href="https://github.com/cabrerahector/wordpress-popular-posts/wiki/7.-Performance#data-sampling" target="_blank" title="<?php _e('What is this?', 'wordpress-popular-posts'); ?>">?</a>]</small></th>
                        <td>
                            <select name="sampling" id="sampling">
                                <option <?php if ( !$this->options['tools']['sampling']['active'] ) { ?>selected="selected"<?php } ?> value="0"><?php _e("Disabled", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ( $this->options['tools']['sampling']['active'] ) { ?>selected="selected"<?php } ?> value="1"><?php _e("Enabled", 'wordpress-popular-posts'); ?></option>
                            </select>

                            <br />
                            <p class="description"><?php echo sprintf( __('By default, WordPress Popular Posts stores in database every single visit your site receives. For small / medium sites this is generally OK, but on large / high traffic sites the constant writing to the database may have an impact on performance. With <a href="%1$s" target="_blank">data sampling</a>, WordPress Popular Posts will store only a subset of your traffic and report on the tendencies detected in that sample set (for more, <a href="%2$s" target="_blank">please read here</a>)', 'wordpress-popular-posts'), 'http://en.wikipedia.org/wiki/Sample_%28statistics%29', 'https://github.com/cabrerahector/wordpress-popular-posts/wiki/7.-Performance#data-sampling' ); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top" <?php if ( !$this->options['tools']['sampling']['active'] ) { ?>style="display:none;"<?php } ?>>
                        <th scope="row"><label for="sample_rate"><?php _e("Sample Rate", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                        	<input name="sample_rate" type="text" id="sample_rate" value="<?php echo ( isset($this->options['tools']['sampling']['rate']) ) ? (int) $this->options['tools']['sampling']['rate'] : 100; ?>" class="small-text">
                            <br />
                            <?php $defaults = WPP_Helper::get_default_options( 'admin_options' ); ?>
                            <p class="description"><?php echo sprintf( __("A sampling rate of %d is recommended for large / high traffic sites. For lower traffic sites, you should lower the value", 'wordpress-popular-posts'), $defaults['tools']['sampling']['rate'] ); ?>.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="hidden" name="section" value="data" />
                    		<input type="submit" class="button-secondary action" id="btn_ajax_ops" value="<?php _e("Apply", 'wordpress-popular-posts'); ?>" name="" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php wp_nonce_field( 'wpp-update-data-options', 'wpp-admin-token' ); ?>
        </form>
        <br />
        <p style="display:block; float:none; clear:both">&nbsp;</p>

        <h3 class="wmpp-subtitle"><?php _e("Miscellaneous", 'wordpress-popular-posts'); ?></h3>
        <form action="" method="post" id="wpp_link_options" name="wpp_link_options">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="link_target"><?php _e("Open links in", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="link_target" id="link_target">
                                <option <?php if ( $this->options['tools']['link']['target'] == '_self' ) {?>selected="selected"<?php } ?> value="_self"><?php _e("Current window", 'wordpress-popular-posts'); ?></option>
                                <option <?php if ( $this->options['tools']['link']['target'] == '_blank' ) {?>selected="selected"<?php } ?> value="_blank"><?php _e("New tab/window", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="css"><?php _e("Use plugin's stylesheet", 'wordpress-popular-posts'); ?>:</label></th>
                        <td>
                            <select name="css" id="css">
                                <option <?php if ($this->options['tools']['css']) {?>selected="selected"<?php } ?> value="1"><?php _e("Enabled", 'wordpress-popular-posts'); ?></option>
                                <option <?php if (!$this->options['tools']['css']) {?>selected="selected"<?php } ?> value="0"><?php _e("Disabled", 'wordpress-popular-posts'); ?></option>
                            </select>
                            <br />
                            <p class="description"><?php _e("By default, the plugin includes a stylesheet called wpp.css which you can use to style your popular posts listing. If you wish to use your own stylesheet or do not want it to have it included in the header section of your site, use this.", 'wordpress-popular-posts'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="hidden" name="section" value="misc" />
                            <input type="submit" class="button-secondary action" value="<?php _e("Apply", 'wordpress-popular-posts'); ?>" name="" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php wp_nonce_field( 'wpp-update-misc-options', 'wpp-admin-token' ); ?>
        </form>
        <br />
        <p style="display:block; float:none; clear:both">&nbsp;</p>

        <br /><br />

        <p><?php _e('WordPress Popular Posts maintains data in two separate tables: one for storing the most popular entries on a daily basis (from now on, "cache"), and another one to keep the All-time data (from now on, "historical data" or just "data"). If for some reason you need to clear the cache table, or even both historical and cache tables, please use the buttons below to do so.', 'wordpress-popular-posts') ?></p>
        <p><input type="button" name="wpp-reset-cache" id="wpp-reset-cache" class="button-secondary" value="<?php _e("Empty cache", 'wordpress-popular-posts'); ?>" onclick="confirm_reset_cache()" /> <label for="wpp-reset-cache"><small><?php _e('Use this button to manually clear entries from WPP cache only', 'wordpress-popular-posts'); ?></small></label></p>
        <p><input type="button" name="wpp-reset-all" id="wpp-reset-all" class="button-secondary" value="<?php _e("Clear all data", 'wordpress-popular-posts'); ?>" onclick="confirm_reset_all()" /> <label for="wpp-reset-all"><small><?php _e('Use this button to manually clear entries from all WPP data tables', 'wordpress-popular-posts'); ?></small></label></p>
    </div>
    <!-- End tools -->

    <!-- Start params -->
    <div id="wpp_params" class="wpp_boxes"<?php if ( "params" == $current ) {?> style="display:block;"<?php } ?>>
        <div>
            <p><?php printf( __('With the following parameters you can customize the popular posts list when using either the <a href="%1$s">wpp_get_mostpopular() template tag</a> or the <a href="%2$s">[wpp] shortcode</a>.', 'wordpress-popular-posts'),
				admin_url('options-general.php?page=wordpress-popular-posts&tab=faq#template-tags'),
				admin_url('options-general.php?page=wordpress-popular-posts&tab=faq#shortcode')
			); ?></p>
            <br />
            <table cellspacing="0" class="wp-list-table widefat fixed posts">
                <thead>
                    <tr>
                        <th class="manage-column column-title"><?php _e('Parameter', 'wordpress-popular-posts'); ?></th>
                        <th class="manage-column column-title"><?php _e('What it does ', 'wordpress-popular-posts'); ?></th>
                        <th class="manage-column column-title"><?php _e('Possible values', 'wordpress-popular-posts'); ?></th>
                        <th class="manage-column column-title"><?php _e('Defaults to', 'wordpress-popular-posts'); ?></th>
                        <th class="manage-column column-title"><?php _e('Example', 'wordpress-popular-posts'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>header</strong></td>
                        <td><?php _e('Sets a heading for the list', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('None', 'wordpress-popular-posts'); ?></td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'header' => 'Popular Posts'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp header='Popular Posts']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>header_start</strong></td>
                        <td><?php _e('Set the opening tag for the heading of the list', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>&lt;h2&gt;</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'header' => 'Popular Posts', <br />&nbsp;&nbsp;&nbsp;&nbsp;'header_start' => '&lt;h3 class="title"&gt;',<br />&nbsp;&nbsp;&nbsp;&nbsp;'header_end' => '&lt;/h3&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp header='Popular Posts' header_start='&lt;h3 class="title"&gt;' header_end='&lt;/h3&gt;']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>header_end</strong></td>
                        <td><?php _e('Set the closing tag for the heading of the list', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>&lt;/h2&gt;</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'header' => 'Popular Posts', <br />&nbsp;&nbsp;&nbsp;&nbsp;'header_start' => '&lt;h3 class="title"&gt;',<br />&nbsp;&nbsp;&nbsp;&nbsp;'header_end' => '&lt;/h3&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp header='Popular Posts' header_start='&lt;h3 class="title"&gt;' header_end='&lt;/h3&gt;']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>limit</strong></td>
                        <td><?php _e('Sets the maximum number of popular posts to be shown on the listing', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Positive integer', 'wordpress-popular-posts'); ?></td>
                        <td>10</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'limit' => 5<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp limit=5]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>range</strong></td>
                        <td><?php _e('Tells WordPress Popular Posts to retrieve the most popular entries within the time range specified by you', 'wordpress-popular-posts'); ?></td>
                        <td>"last24hours", "last7days", "last30days", "all"</td>
                        <td>last24hours</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'range' => 'last7days'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp range='last7days']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>freshness</strong></td>
                        <td><?php _e('Tells WordPress Popular Posts to retrieve the most popular entries published within the time range specified by you', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), 0 (false)</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'range' => 'weekly',<br />&nbsp;&nbsp;&nbsp;&nbsp;'freshness' => 1<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp range='last7days' freshness=1]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>order_by</strong></td>
                        <td><?php _e('Sets the sorting option of the popular posts', 'wordpress-popular-posts'); ?></td>
                        <td>"comments", "views", "avg" <?php _e('(for average views per day)', 'wordpress-popular-posts'); ?></td>
                        <td>views</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'order_by' => 'comments'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp order_by='comments']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>post_type</strong></td>
                        <td><?php _e('Defines the type of posts to show on the listing', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>post,page</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_type' => 'post,page,your-custom-post-type'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp post_type='post,page,your-custom-post-type']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>pid</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will exclude the specified post(s) ID(s) form the listing.', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('None', 'wordpress-popular-posts'); ?></td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'pid' => '60,25,31'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp pid='60,25,31']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>cat</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will retrieve all entries that belong to the specified category(ies) ID(s). If a minus sign is used, the category(ies) will be excluded instead.', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('None', 'wordpress-popular-posts'); ?></td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'cat' => '1,55,-74'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp cat='1,55,-74']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>author</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will retrieve all entries created by specified author(s) ID(s).', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('None', 'wordpress-popular-posts'); ?></td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'author' => '75,8,120'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp author='75,8,120']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>title_length</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will shorten each post title to "n" characters whenever possible', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Positive integer', 'wordpress-popular-posts'); ?></td>
                        <td>25</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'title_length' => 25<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp title_length=25]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>title_by_words</strong></td>
                        <td><?php _e('If set to 1, WordPress Popular Posts will shorten each post title to "n" words instead of characters', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'title_by_words' => 1,<br />&nbsp;&nbsp;&nbsp;&nbsp;'title_length' => 25<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp title_by_words=1 title_length=25]<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>excerpt_length</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will build and include an excerpt of "n" characters long from the content of each post listed as popular', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Positive integer', 'wordpress-popular-posts'); ?></td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'excerpt_length' => 55,<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_html' => '&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp excerpt_length=25 post_html='&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>excerpt_format</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will maintaing all styling tags (strong, italic, etc) and hyperlinks found in the excerpt', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'excerpt_format' => 1,<br />&nbsp;&nbsp;&nbsp;&nbsp;'excerpt_length' => 55,<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_html' => '&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp excerpt_format=1 excerpt_length=25 post_html='&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>excerpt_by_words</strong></td>
                        <td><?php _e('If set to 1, WordPress Popular Posts will shorten the excerpt to "n" words instead of characters', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'excerpt_by_words' => 1,<br />&nbsp;&nbsp;&nbsp;&nbsp;'excerpt_length' => 55,<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_html' => '&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp excerpt_by_words=1 excerpt_length=55 post_html='&lt;li&gt;{thumb} {title} &lt;span class="wpp-excerpt"&gt;{summary}&lt;/span&gt;&lt;/li&gt;']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>thumbnail_width</strong></td>
                        <td><?php _e('If set, and if your current server configuration allows it, you will be able to display thumbnails of your posts. This attribute sets the width for thumbnails', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Positive integer', 'wordpress-popular-posts'); ?></td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'thumbnail_width' => 30,<br />&nbsp;&nbsp;&nbsp;&nbsp;'thumbnail_height' => 30<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp thumbnail_width=30 thumbnail_height=30]<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>thumbnail_height</strong></td>
                        <td><?php _e('If set, and if your current server configuration allows it, you will be able to display thumbnails of your posts. This attribute sets the height for thumbnails', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Positive integer', 'wordpress-popular-posts'); ?></td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'thumbnail_width' => 30,<br />&nbsp;&nbsp;&nbsp;&nbsp;'thumbnail_height' => 30<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp thumbnail_width=30 thumbnail_height=30]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>rating</strong></td>
                        <td><?php _e('If set, and if the WP-PostRatings plugin is installed and enabled on your blog, WordPress Popular Posts will show how your visitors are rating your entries', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'rating' => 1,<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_html' => '&lt;li&gt;{thumb} {title} {rating}&lt;/li&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp rating=1 post_html='&lt;li&gt;{thumb} {title} {rating}&lt;/li&gt;']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>stats_comments</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will show how many comments each popular post has got until now', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), 0 (false)</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_comments' => 1<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_comments=1]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>stats_views</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will show how many views each popular post has got since it was installed', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>1</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_views' => 0<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_views=0]<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>stats_author</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will show who published each popular post on the list', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_author' => 1<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_author=1]<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>stats_date</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will display the date when each popular post on the list was published', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_date' => 1<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_date=1]<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>stats_date_format</strong></td>
                        <td><?php _e('Sets the date format', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_date' => 1,<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_date_format' => 'F j, Y'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_date=1 stats_date_format='F j, Y']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>stats_category</strong></td>
                        <td><?php _e('If set, WordPress Popular Posts will display the category', 'wordpress-popular-posts'); ?></td>
                        <td>1 (true), (0) false</td>
                        <td>0</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'stats_category' => 1<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp stats_category=1]<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>wpp_start</strong></td>
                        <td><?php _e('Sets the opening tag for the listing', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>&lt;ul&gt;</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'wpp_start' => '&lt;ol&gt;',<br />&nbsp;&nbsp;&nbsp;&nbsp;'wpp_end' => '&lt;/ol&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp wpp_start='&lt;ol&gt;' wpp_end='&lt;/ol&gt;']<br /><br /></td>
                    </tr>
                    <tr>
                        <td><strong>wpp_end</strong></td>
                        <td><?php _e('Sets the closing tag for the listing', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string', 'wordpress-popular-posts'); ?></td>
                        <td>&lt;/ul&gt;</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'wpp_start' => '&lt;ol&gt;',<br />&nbsp;&nbsp;&nbsp;&nbsp;'wpp_end' => '&lt;/ol&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp wpp_start='&lt;ol&gt;' wpp_end='&lt;/ol&gt;']<br /><br /></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong>post_html</strong></td>
                        <td><?php _e('Sets the HTML structure of each post', 'wordpress-popular-posts'); ?></td>
                        <td><?php _e('Text string, custom HTML', 'wordpress-popular-posts'); ?>.<br /><br /><strong><?php _e('Available Content Tags', 'wordpress-popular-posts'); ?>:</strong> <br /><br /><em>{thumb}</em> (<?php _e('displays thumbnail linked to post/page, requires thumbnail_width & thumbnail_height', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{thumb_img}</em> (<?php _e('displays thumbnail image without linking to post/page, requires thumbnail_width & thumbnail_height', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{thumb_url}</em> (<?php _e('displays thumbnail url, requires thumbnail_width & thumbnail_height', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{title}</em> (<?php _e('displays linked post/page title', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{summary}</em> (<?php _e('displays post/page excerpt, and requires excerpt_length to be greater than 0', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{stats}</em> (<?php _e('displays the default stats tags', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{rating}</em> (<?php _e('displays post/page current rating, requires WP-PostRatings installed and enabled', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{score}</em> (<?php _e('displays post/page current rating as an integer, requires WP-PostRatings installed and enabled', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{url}</em> (<?php _e('outputs the URL of the post/page', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{text_title}</em> (<?php _e('displays post/page title, no link', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{author}</em> (<?php _e('displays linked author name, requires stats_author=1', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{category}</em> (<?php _e('displays linked category name, requires stats_category=1', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{views}</em> (<?php _e('displays views count only, no text', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{comments}</em> (<?php _e('displays comments count only, no text, requires stats_comments=1', 'wordpress-popular-posts'); ?>)<br /><br /> <em>{date}</em> (<?php _e('displays post/page date, requires stats_date=1', 'wordpress-popular-posts'); ?>)</td>
                        <td>&lt;li&gt;{thumb} {title} &lt;span class="wpp-meta post-stats"&gt;{stats}&lt;/span&gt;&lt;/li&gt;</td>
                        <td><strong><?php _e('With wpp_get_mostpopular():', 'wordpress-popular-posts'); ?></strong><br /><br />&lt;?php<br />$args = array(<br />&nbsp;&nbsp;&nbsp;&nbsp;'post_html' => '&lt;li&gt;{thumb} &lt;a href="{url}"&gt;{text_title}&lt;/a&gt;&lt;/li&gt;'<br />);<br /><br />wpp_get_mostpopular( $args );<br />?&gt;<br /><br /><hr /><br /><strong><?php _e('With the [wpp] shortcode:', 'wordpress-popular-posts'); ?></strong><br /><br />[wpp post_html='&lt;li&gt;{thumb} &lt;a href="{url}"&gt;{text_title}&lt;/a&gt;&lt;/li&gt;']<br /><br /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End params -->

    <!-- Start about -->
    <div id="wpp_faq" class="wpp_boxes"<?php if ( "about" == $current ) {?> style="display:block;"<?php } ?>>

        <h3><?php echo sprintf( __('About WordPress Popular Posts %s', 'wordpress-popular-posts'), $this->version); ?></h3>
        <p><?php _e( 'This version includes the following changes', 'wordpress-popular-posts' ); ?>:</p>

        <ul>
            <li>Fixes potential XSS exploit in WPP's admin dashboard.</li>
            <li>Adds filter to set which post types should be tracked by WPP (details).</li>
			<li>Adds ability to select first attached image as thumbnail source (thanks, <a href="https://github.com/serglopatin">@serglopatin</a>!)</li>
        </ul>

    </div>
    <!-- End about -->

    <div id="wpp_donate" class="wpp_box" style="">
        <h3 style="margin-top:0; text-align:center;"><?php _e('Do you like this plugin?', 'wordpress-popular-posts'); ?></h3>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="RP9SK8KVQHRKS">
            <input type="image" src="//www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" style="display:block; margin:0 auto;">
            <img alt="" border="0" src="//www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>
        <p><?php _e( 'Each donation motivates me to keep releasing free stuff for the WordPress community!', 'wordpress-popular-posts' ); ?></p>
        <p><?php echo sprintf( __('You can <a href="%s" target="_blank">leave a review</a>, too!', 'wordpress-popular-posts'), 'https://wordpress.org/support/view/plugin-reviews/wordpress-popular-posts?rate=5#postform' ); ?></p>
    </div>

    <div id="wpp_advertisement" class="wpp_box" style=""></div>

    <div id="wpp_support" class="wpp_box" style="">
        <h3 style="margin-top:0; text-align:center;"><?php _e('Need help?', 'wordpress-popular-posts'); ?></h3>
        <p><?php echo sprintf( __('Visit <a href="%s" target="_blank">the forum</a> for support, questions and feedback.', 'wordpress-popular-posts'), 'https://wordpress.org/support/plugin/wordpress-popular-posts' ); ?></p>
        <p><?php _e('Let\'s make this plugin even better!', 'wordpress-popular-posts'); ?></p>
    </div>

</div>