<?php
/*
Plugin Name: Automagic Twitter Profile URI
Plugin uri: http://wordpress.org/extend/plugins/automagic-twitter-profile-uri/
Description: Automagically adds a Twitter profile link to your commenters: If they have a Twitter profile, a link to it is “magically” added to their name in the comments. Whoo!
Author: Benjamin Wittorf
Version: 1.2.2
Author uri: http://benjamin.wittorf.me/
*/ 

/*
	defining some functions a.k.a. core stuff
*/

// initialization
function atpu_init() {
	global $atpu_settings;
	
	// load language settings
	$language_locale = get_locale(); // language
	$language_mofile = dirname(__FILE__) . "/languages/atpu-" . $language_locale . ".mo";
	load_textdomain("atpu", $language_mofile);

	// default options
	$_atpu['auto']			= true; // automatically integrate Twitter link
	$_atpu['cache_name']		= 'atpu_cache'; // and the wp cache is called by what name
	$_atpu['caching_age']		= 7; // // how long shall an email address be cached, in days
	$_atpu['css']			= 'atpu'; // css class name
	$_atpu['css_clear']		= true; // css class name
	$_atpu['css_wrap']		= false; // wrap css class
	$_atpu['custom']		= false; // custom output string
	$_atpu['custom_string']		= '%BREAK%%IMG% %TITLE%%SEPARATOR% <a href="%URI%/%USERNAME%" title="' . __('Follow', 'atpu') . ' %COMMENTER% ' . __('at', 'atpu') . ' %TITLE%">&#0064;%USERNAME%</a>'; // a custom string example
	$_atpu['icon_display']		= true; // prepend the Twitter icon
	$_atpu['line_break']		= '<br />'; // default line break
	$_atpu['line_break_add']	= false; // append line breaks
	$_atpu['link_name']		= 'Twitter'; // what name for the link do we display?
	$_atpu['twitter_icon']		= WP_CONTENT_URL . '/plugins/' . dirname(plugin_basename(__FILE__)) . '/images/twitter.gif'; // where is the fancy Twitter icon
	$_atpu['notice_display']	= false; // display a notice below the comments form about this plugin
	$_atpu['notice_text']		= sprintf(__('If you have a <img src="%s" alt="Twitter" /> <a href="https://twitter.com" title="Twitter">Twitter</a> account using the same email address, a link to your Twitter profile will be <a href="http://immersion.io/publikationen/code/wordpress/automagic-twitter-profile-uri/" title="Automagic Twitter Profile URI › Immersion I/O">automagically</a> added to your comments.', 'atpu'), $_atpu['twitter_icon']);
	$_atpu['option_display']	= 'all'; // display an option below the comments to disable functionality
	$_atpu['option_default']	= true; // default checkbox status is enabled
	$_atpu['option_text']		= sprintf(__('<a href="http://immersion.io/publikationen/code/wordpress/automagic-twitter-profile-uri/" title="Automagic Twitter Profile URI › Immersion I/O">Automagically</a> add a link to my <img src="%s" alt="Twitter" /> <a href="https://twitter.com" title="Twitter">Twitter</a> profile to my comments', 'atpu'), $_atpu['twitter_icon']);
	$_atpu['separator']		= ': '; // default separator
	$_atpu['short_name']		= dirname(plugin_basename(__FILE__)); // plugin shortname
	$_atpu['ssl']			= 'uri'; // where to use ssl?
	$_atpu['text_app']		= ')'; // default closing text
	$_atpu['text_pre']		= '('; // default opening text
	$_atpu['text_show']		= true; // wrap with text?
	$_atpu['theme']			= false; // do we have a special theme, like Thesis?
	$_atpu['twitter_api']		= 'twitter.com/users/show.xml?email='; // Twitter API
	$_atpu['twitter_title']		= "Twitter"; // what is Twitter called (duh)
	$_atpu['twitter_uri']		= 'twitter.com'; // Twitter base uri
	$_atpu['version']		= "1.2.2"; // the current version

	$changed = false; // so far, nothing has changed
	// normally this shouldn't be here, but this update is one time only - i hope
	$version = get_option("automagic_twitter_profile_version"); // old options
	if ($version) {
	 	if ($version < '0.2.4') { // update legacy plugin
			atpu_install(true);
		}
		delete_option("automagic_twitter_profile_version"); // remove old options
		
		// respect old settings
		$_atpu['auto']		= false;
		$_atpu['custom'] 	= true;
	}

	// create options if there are none, if there are but not set, update
	$atpu_settings = get_option('automagic_twitter_profile_uri_options');
	if (!is_array($atpu_settings)) { // no saved settings so far

		// different themes variable preparation
		$_atpu['theme'] = atpu_check_theme(); // check for special themes, like Thesis
		if ($_atpu['theme']) {
			switch ($_atpu['theme']) {

				// what do we do for Thesis?
				case 'Thesis':
					$_atpu['line_break_add']	= 'before';
					$_atpu['link_name']		= 'both';
					$_atpu['text_show']		= false;
					break;

				default:
					break;
			}
		}		

		$atpu_settings = $_atpu;
		$changed = true;
    } else { // we already have settings, so...
 		foreach ($_atpu as $key => $value) { // let's go through all settings

			if (!isset($atpu_settings[$key])) { // if something isn't set, set the default value
				$atpu_settings[$key] = $value;
				$changed = true; // something has changed, important for later
			}

		}

		// legacy updater before 1.0.0 RC3
		if ($atpu_settings['version'] < '0.5.2') {

			// removes host prefix so ssl can be enabled
			$atpu_settings['twitter_api'] = str_replace("http://", "", $atpu_settings['twitter_api']); 
			$atpu_settings['twitter_uri'] = str_replace("http://", "", $atpu_settings['twitter_uri']);
		}

		if ($atpu_settings['version'] != $_atpu['version']) { // if the version differs, update
			$atpu_settings['version'] = $_atpu['version'];
			$changed = true; // something has changed, again
		}

		$_atpu['theme'] = atpu_check_theme(); // check for special themes, like Thesis
		if ($atpu_settings['theme'] != $_atpu['theme']) { // if the version differs, update
			$atpu_settings['theme'] = $_atpu['theme'];
			$changed = true; // something has changed, again
		}

    }

	// commit changes to the options
	if ($changed) { // we only need to update options if something has changed
		update_option('automagic_twitter_profile_uri_options', $atpu_settings);
	}

	// real actions to do on different themes
	if ($atpu_settings['theme']) {
		switch ($atpu_settings['theme']) {

			// what do we do for Thesis?
			case 'Thesis':
			
				// filtering the commenter link
				remove_filter('get_comment_author_link', 'add_atpu', 50);
				add_action('thesis_hook_after_comment_meta', 'add_atpu');
				
				// adding an action to the comment form
				remove_action('comment_form', 'atpu_notice');
				remove_action('comment_form', 'atpu_option');
				add_action('thesis_hook_comment_form', 'atpu_notice_thesis');
				add_action('thesis_hook_comment_form', 'atpu_option_thesis');
				break;

			default:
				break;
		}
	}
}

// first tries to get result from cache, then from database && updates
function atpu_retrieve($email, $cache=false) {
	$twitter = $twitter_nick = false; // default answer
	$hashed_email = atpu_hashme($email);

	/*
	
	PLUGIN SHOULD NOT BE ALLOWED TO WORK AT THE MOMENT
	
	*/

	if ($cache) { // this call comes from prefetch
		global $atpu_settings;

		// do we have a cache (like from “WP Super Cache”)?
		$twitter_nick = wp_cache_get($atpu_settings['cache_name'], $hashed_email);

		if (!$twitter_nick) { // no cache! no cache! no...

			// variables!
			$new_time = time();
			if ($atpu_settings['ssl'] == 'both' || $atpu_settings['ssl'] == 'api') { $url = 'https://'; }
				else { $url = 'http://'; }
			$query = $url . $atpu_settings['twitter_api'] . $email;

			// check database
			$twitter = atpu_db_retrieve($email);
        	
			// is there an entry?
			if ($twitter) {

				// only display if the commenter wants to or that is disabled
				if ($twitter['display']) {
					
					$twitter_nick = $twitter['nick'];
        	
					// now, there is an entry - we may need to refresh data
					$old_time = $twitter['time'];
					$difference = $new_time - $old_time;
        	
					// older than caching age settings? get an update!
					$cache = $atpu_settings['caching_age'] * 24 * 60 * 60;
					if ($difference > $cache) {
						$new_twitter_nick = atpu_query_twitter($query);
        	
						// no Twitter error? update!
						if ($new_twitter_nick != "@") {
								$twitter_nick = $new_twitter_nick;
								atpu_store($email, $twitter_nick, $new_time, true, 1);
							}
        	
					}
        	
				} else { $twitter_nick = false; }
        	
			} else { // still nothing?
				$twitter_nick = false;
				atpu_db_store($email, false, 0, false, 0);
			}

		}

	} else { // calling from elsewhere, not from "prefetch"
		global $nicks;

		// as $nicks is prefetched, we should be able to rely on it by now
		$twitter_nick = $nicks[$hashed_email];
	}
	
	/*
	
	NO REALLY, IT MUST NOT WORK
	
	*/
	
	// that's why we fake some stuff here
	$twitter_nick = false;

	return $twitter_nick; // now return what we have
}

// the actual Twitter query
function atpu_query_twitter($query) {
	$twitter_response = wp_remote_get($query); // uses a WP 2.7 function, thanks Otto

	if (!is_wp_error($twitter_response) && $twitter_response != null) {
		
		if ($twitter_response['response']['code'] == '200') { // does Twitter respond successfully?
			$twitter_response = $twitter_response['body'];

			// now let's extract the screenname
			$pattern = "/<screen_name>(.*?)<\/screen_name>/"; // what we are looking for
			preg_match($pattern, $twitter_response, $matches);

			if ($matches[1]) { $twitter_nick = $matches[1]; }
				else { $twitter_nick = false; } // there is no username at Twitter with that email address

		} else {

			if (
				$twitter_response['response']['code'] == '400' || // limit exceeded
				$twitter_response['response']['code'] == '500' || // internal server error
				$twitter_response['response']['code'] == '502' || // bad gateway
				$twitter_response['response']['code'] == '503'    // service unavailable
				) { $twitter_nick = "@"; } // bogus data
				else { $twitter_nick = false; } // some other error that's not caught yet

		}
		
	} else $twitter_nick = false; // yikes, wp_remote_get isn't returning something, bork bork bork!

	return $twitter_nick; // return what we have
}

// this function fetches all used email addresses of an entry in advance - premonagic
function atpu_prefetch($content) {
	
	// do only stuff when on a single page
	if (is_single() || is_page()) {
		global $wpdb, $wp_query, $nicks;

		// variables!
		$table = $wpdb->prefix . 'comments';
		$nicks = array();

		// what is the id of the current entry?
		$thepostid = $wp_query->post->ID;

		// gather all (different) email addreses from that entry
		$emails = $wpdb->get_results("SELECT DISTINCT comment_author_email FROM $table WHERE comment_post_id='$thepostid'", ARRAY_N);

		// no reason to do things when there are no email addresses
		if ($emails) {

			foreach ($emails as $email) {
				if ($email[0]) { $nicks[atpu_hashme($email[0])] = atpu_retrieve($email[0], true); }
			}

		}

	}

	return $content; // we need to the return content anyway
}

// function to retrieve from the database 
function atpu_db_retrieve($email) {
	global $wpdb;

	$email = atpu_hashme($email);
	$table = $wpdb->prefix . 'atpu';
	$result = $wpdb->get_results("SELECT twitter_username, last_updated, display FROM $table WHERE email='$email'", ARRAY_A);

	if ($result) {

		$twitter = array();
		$twitter['nick']	= $result[0]['twitter_username'];
		$twitter['time']	= $result[0]['last_updated'];
		$twitter['display']	= $result[0]['display'];

		return $twitter;

	} else {
		return FALSE;
	}
}

 // stores to cache and delegates to databse
function atpu_store ($email, $nick, $time, $update=false, $display=0) {
	global $atpu_settings;
	
	$cache = $atpu_settings['caching_age'] * 24 * 60 * 60;
	$hashed_email = atpu_hashme($email);
	wp_cache_set($atpu_settings['cache_name'], $nick, $hashed_email, $cache);
	atpu_db_store($email, $nick, $time, $update, $display);
}

// function to store to the database
function atpu_db_store($email, $nick, $time, $update, $display) {
	global $wpdb;

	$email = atpu_hashme($email);
	$table = $wpdb->prefix . 'atpu';
	if ($update) { // do we need to update
		$sql = "UPDATE $table SET twitter_username='$nick', last_updated='$time', display='$display' WHERE email='$email'";
	} else { // or is this a new entry
		$sql = "INSERT INTO $table (email, twitter_username, last_updated, display) VALUES ('$email', '$nick', '$time', '$display')";
	}
	$wpdb->query($sql);
}

// heavily obscures, at least tries to
function atpu_hashme($string) {
	if (function_exists('sha1')) { $output = sha1(strtolower($string)); } // let's try sha1 first
		else {
			if (function_exists('md5')) { $output = md5(strtolower($string)); } // then md5
				else { $output = strtolower($string); } // then... nothing
		}

	return $output;
}

// primitive debugger function - I know WordPress has its own, but I need that
function atpu_debug($message, $break=true, $location=false) {
	print "\n\n<pre id='atpu_debug' style='font-weight: bold; font-size: 15px; padding: 10px; color: #000; border: 2px solid #ff0000'>\n";
	if ($location) {
		print "<span style='font-size: 20px; letter-spacing: 2px'>" . $location . "</span><br /><br />";
	}
	print_r($message);
	print "</pre>\n\n";
	if ($break) break;
}


/*
	settings, setup and options page
*/


// installing and setting up, if necessary
function atpu_install($update=false, $override=false) {
	global $wpdb;

	$table = $wpdb->prefix . 'atpu';

	if ($update) {

		// we have some early users here. respect them! update! optimize!
		$sql = "ALTER TABLE `$table`
			MODIFY `id` MEDIUMINT UNSIGNED ,
			MODIFY `email` VARCHAR(64) ,
			MODIFY `last_updated` INT(10) ,
			CHANGE `twitter_nick` `twitter_username` VARCHAR(64) ,
			ADD `display` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'
		";
		$wpdb->query($sql);

	} else {

		// check if something is installed (shouldn't!) and if not, install the database
		$installed = get_option("automagic_twitter_profile_uri_options");
		if (!$installed || $override) {

			// we need to create a database table
			$sql = "CREATE TABLE `$table` (
				`id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ,
				`email` VARCHAR(64) NOT NULL ,
				`last_updated` INT(10) UNSIGNED NOT NULL ,
				`twitter_username` VARCHAR(64) ,
				`display` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' ,
				PRIMARY KEY (`id`) ,
				INDEX (`email`)
			)";
			$wpdb->query($sql);

		}

	}

}

// deletes the plugin
function atpu_remove() {
	global $wpdb;

	// delete WordPress options
	delete_option("automagic_twitter_profile_uri_options");

	// delete database table
	$table = $wpdb->prefix . 'atpu';
	$sql = "DROP TABLE IF EXISTS $table";
	$wpdb->query($sql);
}

// calling for action, or the options page
function atpu_plugin_menu() {
	global $atpu_settings;

	// are we being called from the options page?
	if ($_GET['page'] == $atpu_settings['short_name']) {
		$file  = $atpu_settings['short_name'];
    	if ('save' == $_REQUEST['automagic-twitter-profile-uri-update-options']) { // what to do on save?

			foreach ($atpu_settings as $key => $value) {
				if (isset($_REQUEST[$key])) {
					$new_value = stripslashes($_REQUEST[$key]);
					if ($new_value == "false") $new_value = false;
					if ($new_value == "true") $new_value = true;
					$atpu_settings[$key] = $new_value;
				}
			}
			
			global $wpdb;

			// when switching adjust all commenters
			if (($atpu_settings['option_display']) && ($_REQUEST['option_display'] == "false")) {
				$table = $wpdb->prefix . 'atpu';
				$sql = "UPDATE $table SET display='1'";
				$result = $wpdb->query($sql);
				wp_cache_delete($atpu_settings['cache_name']);
			}
			if ((!$atpu_settings['option_display']) && ($_REQUEST['option_display'] != "false")) {
				$table = $wpdb->prefix . 'atpu';
				$sql = "UPDATE $table SET display='0'";
				$result = $wpdb->query($sql);
				wp_cache_delete($atpu_settings['cache_name']);
			}

			update_option('automagic_twitter_profile_uri_options', $atpu_settings);
			header("Location: options-general.php?page=" . $file . "&saved=true");
			die;

		} else if ('reset' == $_REQUEST['automagic-twitter-profile-uri-update-options']) { // what to do on reset?
			delete_option('automagic_twitter_profile_uri_options');
			header("Location: options-general.php?page=" . $file . "&reset=true");
			die;

		} else if ('flush' == $_REQUEST['automagic-twitter-profile-uri-update-options']) { // what to do on flush?
			global $wpdb;

			$table = $wpdb->prefix . 'atpu';
			$sql = "UPDATE $table SET last_updated='0'";
			$result = $wpdb->query($sql);
			wp_cache_delete($atpu_settings['cache_name']);
			header("Location: options-general.php?page=" . $file . "&flush=true");
			die;

		} else if ('drop' == $_REQUEST['automagic-twitter-profile-uri-update-options']) {
			global $wpdb;

			$table = $wpdb->prefix . 'atpu';
			$sql = "DROP TABLE IF EXISTS $table";
			$result = $wpdb->query($sql);
			wp_cache_delete($atpu_settings['cache_name']);
			atpu_install(false, true);
			header("Location: options-general.php?page=" . $file . "&drop=true");
			die;
		}
	}
	
	add_options_page('Automagic Twitter Profile URI Options', 'Automagic Twitter Profile URI', 10, $atpu_settings['short_name'], 'atpu_plugin_options');
}

// the actual options page
function atpu_plugin_options() {
	$atpu_settings = get_option("automagic_twitter_profile_uri_options");

	if ($_REQUEST['saved']) echo '<div id="message" class="updated fade"><p><strong>' . __("Settings saved.", "atpu") . '</strong></p></div>'; // we have come from saving
	if ($_REQUEST['reset']) echo '<div id="message" class="updated fade"><p><strong>' . __("Settings reset.", "atpu") . '</strong></p></div>'; // we have come from reset
	if ($_REQUEST['flush']) echo '<div id="message" class="updated fade"><p><strong>' . __("Cache flushed.", "atpu") . '</strong></p></div>'; // we have come from reset
	if ($_REQUEST['drop']) echo '<div id="message" class="updated fade"><p><strong>' . __("Database table dropped.", "atpu") . '</strong></p></div>'; // we have come from reset

?>

<div class="wrap">
	<h2>Automagic Twitter Profile URI</h2>
	
	<p><?php wp_nonce_field('automagic-twitter-profile-uri-update-options'); ?></p>

	<div id="poststuff" class="dlm">
		<div class="postbox closed">
			<h3><?php _e('About this plugin', 'atpu') ?></h3>
			<div class="inside">
				
				<p class="submit"><?php _e("This plugin automagically adds a Twitter profile link to your commenters.<br />Please have a <a href='http://immersion.io/publikationen/code/wordpress/automagic-twitter-profile-uri/' title='Automagic Twitter Profile URI › Immersion I/O'>look at the author's plugin page</a> for more information, comments and feedback.", 'atpu') ?><br />
					<?php _e('You are using version', 'atpu'); echo ' ' . $atpu_settings['version'] . '.'; ?>
				</p>

			</div>
		</div>
	</div>
	
	<div id="poststuff" class="dlm">
		<div class="postbox">
			<h3><?php _e('Your current plugin output', 'atpu') ?></h3>
			<div class="inside">

				<p class="submit">
				<?php if (!$atpu_settings['auto']) { ?>
					<?php _e('<strong>Attention</strong>: you have the automagic integration disabled.', 'atpu') ?><br />

					<?php if ($atpu_settings['custom']) {
						_e("When calling <code>atpu()</code> or <code>get_atpu('custom_string)</code>, your <em>custom</em> output will look like this:", 'atpu');
					} else {
						_e("When calling <code>atpu()</code>, your output will look like this:", 'atpu');
					} ?>
				<?php } else { ?>
					<?php if ($atpu_settings['custom']) {
						_e("Currently, your <em>custom</em> output (the header of each comment) will look something like this:", 'atpu');
					} else {
						_e("Currently, your output (the header of each comment) will look something like this:", 'atpu');
					} ?>
				<?php } ?>
				</p>	

				<?php if ($atpu_settings['auto']) { ?>

				<?php if (!$atpu_settings['theme']) {?>
				<blockquote>
					<strong>Benjamin Wittorf<?php atpu(true, true); ?></strong> <?php _e('said', 'atpu') ?>:<br /><small><?php echo ' ' . date('r'); ?></small>
				</blockquote>
				<?php } else
					if ($atpu_settings['theme'] == 'Thesis') { // for Thesis ?>
				<blockquote>
					<strong>Benjamin Wittorf</strong> <small><?php echo ' ' . date('r'); ?></small><?php atpu(true, true); ?>
				</blockquote>
				<?php } ?>		

				<?php } else { ?>

				<blockquote>
					<?php atpu(true, true); ?>
				</blockquote>

				<?php } ?>

				<p class="submit">
					<?php _e("You can modify the output in the settings below.", 'atpu') ?>
				</p>

			</div>
		</div>
	</div>
	
	<div id="poststuff" class="dlm">
		<div class="postbox">
			<h3><?php _e('Basic settings', 'atpu') ?></h3>
			<div class="inside">

				<p class="submit"><?php _e('Here you can setup the basic plugin output settings.', 'atpu') ?></p>
				
				<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">

					<table summary="config" class="widefat">

						<tr<?php if (!$atpu_settings['auto']) echo ' class="form-invalid"'; ?>>
							<th width="62%">
								<label for="auto"><?php _e('Enable automagic integration', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="auto">
									<option <?php if ($atpu_settings['auto']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['auto']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="icon_display"><?php _e('Show Twitter icon', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="icon_display">
									<option <?php if ($atpu_settings['icon_display']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['icon_display']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="link_name"><?php printf(__('Show "%s" or "@username"', 'atpu'), get_atpu('title', true)) ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="link_name">
									<option <?php if ($atpu_settings['link_name'] == 'Twitter') echo 'selected'; ?> value="Twitter"><?php echo get_atpu('title', true) ?></option>
									<option <?php if ($atpu_settings['link_name'] == 'username') echo 'selected'; ?> value="username">@username</option>
									<option <?php if ($atpu_settings['link_name'] == 'both') echo 'selected'; ?> value="both"><?php _e('both', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

					</table>

					<p class="submit">
						<input name="save" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
						<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="save" />
					</p>

				</form>

			</div>
		</div>
	</div>

	<div id="poststuff" class="dlm">
		<div class="postbox">
			<h3><?php _e('Advanced settings', 'atpu') ?></h3>
			<div class="inside">

				<p class="submit"><?php _e('Time for some advanced settings!<br />Here you can fine tune the appearance of the link and other settings.', 'atpu') ?></p>
				
				<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">

					<table summary="config" class="widefat">
						
						<tr>
							<th colspan="2"><br /><strong style="text-transform: uppercase"><?php _e('Link appearance', 'atpu') ?></strong></th>
						</tr>

						<tr>
							<th width="62%">
								<label for="text_show"><?php _e('Wrap in brackets', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="text_show">
									<option <?php if ($atpu_settings['text_show']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['text_show']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="line_break_add"><?php _e('Append a line break', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="line_break_add">
									<option <?php if (!$atpu_settings['line_break_add']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
									<option <?php if ($atpu_settings['line_break_add'] == 'before') echo 'selected'; ?> value="before"><?php _e('before', 'atpu') ?></option>
									<option <?php if ($atpu_settings['line_break_add'] == 'after') echo 'selected'; ?> value="after"><?php _e('after', 'atpu') ?></option>
									<option <?php if ($atpu_settings['line_break_add'] == 'both') echo 'selected'; ?> value="both"><?php _e('before and after', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="css_wrap"><?php _e('Wrap in a CSS class', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="css_wrap">
									<option <?php if ($atpu_settings['css_wrap']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['css_wrap']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>

						</tr>

						<tr>
							<th colspan="2"><br /><strong style="text-transform: uppercase"><?php _e('Comment form options', 'atpu') ?></strong></th>
						</tr>

						<tr>
							<th width="62%">
								<label for="notice_display"><?php _e("Show a notice about the magic under the comment form", 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="notice_display">
									<option <?php if ($atpu_settings['notice_display'] == 'all') echo 'selected'; ?> value="all"><?php _e('to all', 'atpu') ?></option>
									<option <?php if ($atpu_settings['notice_display'] == 'visitors') echo 'selected'; ?> value="visitors"><?php _e('to visitors', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['notice_display']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="option_display"><?php _e('Let the commenter decide to publicize his/her username', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="option_display">
									<option <?php if ($atpu_settings['option_display'] == 'all') echo 'selected'; ?> value="all"><?php _e('all commenters', 'atpu') ?></option>
									<option <?php if ($atpu_settings['option_display'] == 'visitors') echo 'selected'; ?> value="visitors"><?php _e('just visitors', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['option_display']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>
						
						<tr>
							<th width="62%">
								<label for="option_default"><?php _e('If the commenter is allowed to decide, the default setting is', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="option_default">
									<option <?php if ($atpu_settings['option_default']) echo 'selected'; ?> value="true"><?php _e('magic enabled', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['option_default']) echo 'selected'; ?> value="false"><?php _e('magic disabled', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="css_clear"><?php _e('Apply a CSS "clear: both" to the notice/option about the magic', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="css_clear">
									<option <?php if ($atpu_settings['css_clear']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['css_clear']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

						<tr>
							<th colspan="2"><br /><strong style="text-transform: uppercase;"><?php _e('Secure communication options', 'atpu') ?></strong></th>
						</tr>

						<tr>
							<th width="62%">
								<label for="ssl"><?php _e('Use Twitter\'s <a href="http://en.wikipedia.org/wiki/Transport_Layer_Security">SSL</a> capabilities', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="ssl">
									<option <?php if ($atpu_settings['ssl'] == 'both') echo 'selected'; ?> value="both"><?php _e('for both querying from and linking to Twitter', 'atpu') ?></option>
									<option <?php if ($atpu_settings['ssl'] == 'uri') echo 'selected'; ?> value="uri"><?php _e('only for links to Twitter', 'atpu') ?></option>
									<option <?php if ($atpu_settings['ssl'] == 'api') echo 'selected'; ?> value="api"><?php _e('only for querying from Twitter', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['ssl']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

					</table>

					<p class="submit">
						<input name="save" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
						<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="save" />
					</p>

				</form>

			</div>
		</div>
	</div>

	<div id="poststuff" class="dlm">
		<div class="postbox closed">
			<h3><?php _e('Customize strings', 'atpu') ?></h3>
			<div class="inside">
				
				<p class="submit"><?php _e('Here you can fully customize the output of the plugin.', 'atpu') ?></p>
				
				<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">

					<table summary="config" class="widefat">

						<tr>
							<th colspan="2"><br /><strong style="text-transform: uppercase"><?php _e('Text strings', 'atpu') ?></strong></th>
						</tr>

						<tr>
							<th width="62%">
								<label for="css"><?php _e('CSS class name', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" style="width: 100%" size="40" name="css" value="<?php echo $atpu_settings['css'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="twitter_icon"><?php _e('Twitter icon URI', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" size="40" name="twitter_icon" value="<?php echo $atpu_settings['twitter_icon'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="text_pre"><?php _e('Text to prepend (like a bracket)', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" size="40" name="text_pre" value="<?php echo $atpu_settings['text_pre'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="text_app"><?php _e('Text to append (like a bracket)', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" size="40" name="text_app" value="<?php echo $atpu_settings['text_app'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="line_break"><?php _e('Line break HTML code', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" size="40" name="line_break" value="<?php echo $atpu_settings['line_break'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="separator"><?php _e('Text for separator', 'atpu') ?></label>
							</th>
							<td width="38%">
								<input type="text" style="width: 100%" size="40" name="separator" value="<?php echo $atpu_settings['separator'] ?>" />
						    </td>
						</tr>

						<tr>
							<th colspan="2"><br /><strong style="text-transform: uppercase;"><?php _e('Comment form texts', 'atpu') ?></strong></th>
						</tr>

						<tr>
							<td colspan="2">
								<strong><label for="notice_text"><?php _e('The commenter notice text', 'atpu') ?></label></strong><br />
								<textarea style="width: 100%" name="notice_text" cols="40" rows="3"><?php echo $atpu_settings['notice_text']; ?></textarea>
								
								<br />

								<strong><label for="option_text"><?php _e('The commenter publicize option text', 'atpu') ?></label></strong><br />
								<textarea style="width: 100%" name="option_text" cols="40" rows="3"><?php echo $atpu_settings['option_text']; ?></textarea>
							</td>
						</tr>

						<tr>
							<th colspan="2">
								<br /><strong style="text-transform: uppercase"><?php _e('Custom link string', 'atpu') ?></strong><br />
								<span style="font-weight: normal;"><?php _e("This makes especially sense if you are using the <code>atpu()</code> or <code>get_atpu('custom_string')</code> function in your template instead of the automagic integration.<br />Please have a <a href='http://immersion.io/publikationen/code/wordpress/automagic-twitter-profile-uri/' title='Automagic Twitter Profile URI › Immersion I/O'>look at the documentation</a> for more information. Remember, if you enable the custom output string, the basic and advanced settings will be ignored!&sup1;<br /><small>&sup1; (Except for automagic integration setting and comment form options.)</small>", 'atpu') ?></span>
							</th>
						</tr>

						<tr>
							<td colspan="2">
								<strong><label for="custom_string"><?php _e('Your custom output string&sup2;', 'atpu') ?></label></strong><br />
								<textarea style="width: 100%" name="custom_string" cols="40" rows="3"><?php echo $atpu_settings['custom_string']; ?></textarea><br />
								<?php _e('&sup2; You can use the following custom tags (move the mouse over them to get a brief description):', 'atpu') ?>
								<br />

								 <em><abbr title="<?php _e("Returns the commenter name, like: 'Benjamin Wittorf'", 'atpu') ?>">%COMMENTER%</abbr></em> &nbsp;
								 <em><abbr title="<?php _e("Returns the Twitter username, like: 'bwittorf'", 'atpu') ?>">%USERNAME%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the line break: '%s'", 'atpu'), htmlentities(get_atpu('line_break', true))) ?>">%BREAK%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the separator: '%s'", 'atpu'), htmlentities(get_atpu('separator', true))) ?>">%SEPARATOR%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the prepending text: '%s'", 'atpu'), htmlentities(get_atpu('text_pre', true))) ?>">%APP%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the appending text: '%s'", 'atpu'), htmlentities(get_atpu('text_app', true))) ?>">%PRE%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns Twitter's base URI: '%s'", 'atpu'), htmlentities(get_atpu('uri', true))) ?>">%URI%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__('Returns the Twitter icon: \'<img src=\'%s\' title=\'%s\' />\'', 'atpu'), htmlentities(get_atpu('icon', true)), htmlentities(get_atpu('title', true))) ?>">%IMG%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the title of Twitter: '%s'", 'atpu'), htmlentities(get_atpu('title', true))) ?>">%TITLE%</abbr></em> &nbsp;
								 <em><abbr title="<?php printf(__("Returns the CSS class name: '%s'", 'atpu'), htmlentities(get_atpu('css', true))) ?>">%CSS%</abbr></em>
									
							</td>
						</tr>

						<tr<?php if ($atpu_settings['custom']) echo ' class="form-invalid"'; ?>>
							<th width="62%">
								<label for="custom"><?php _e('Custom output string enabled', 'atpu') ?></label>
							</th>
							<td width="38%">
								<select style="width: 100%" name="custom">
									<option <?php if ($atpu_settings['custom']) echo 'selected'; ?> value="true"><?php _e('yes', 'atpu') ?></option>
									<option <?php if (!$atpu_settings['custom']) echo 'selected'; ?> value="false"><?php _e('no', 'atpu') ?></option>
								</select>
						    </td>
						</tr>

					</table>

					<p class="submit">
						<input name="save" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
						<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="save" />
					</p>

				</form>

			</div>
		</div>
	</div>

	<div id="poststuff" class="dlm">
		<div class="postbox closed">
			<h3><?php _e('Core settings', 'atpu')?></h3>
			<div class="inside">

				<p class="submit"><?php _e('Here you can find the plugin core settings. Change with caution.', 'atpu') ?></p>

				<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">

					<table summary="config" class="widefat">

						<tr>
							<th width="62%">
								<label for="caching_age"><?php _e('Days to cache Twitter queries', 'atpu') ?></label>
							</th>
							<td colspan="2">
								<select style="width: 100%" name="caching_age">
									<option <?php if ($atpu_settings['caching_age'] == 1) echo 'selected'; ?> value="1">1</option>
									<option <?php if ($atpu_settings['caching_age'] == 7) echo 'selected'; ?> value="7">7</option>
									<option <?php if ($atpu_settings['caching_age'] == 14) echo 'selected'; ?> value="14">14</option>
									<option <?php if ($atpu_settings['caching_age'] == 28) echo 'selected'; ?> value="28">28</option>
								</select>
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="twitter_title"><?php _e('Name of service', 'atpu') ?></label>
							</th>
							<td colspan="2">
								<input type="text" style="width: 100%" size="40" name="twitter_title" value="<?php echo $atpu_settings['twitter_title'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="twitter_api"><?php _e('Twitter API URI', 'atpu') ?></label>
							</th>
							<td width="auto" style="vertical-align: middle;">
								<?php if (($atpu_settings['ssl'] == 'both') || ($atpu_settings['ssl'] == 'api')) { echo "&nbsp;&nbsp;https://"; } else { echo "&nbsp;&nbsp;http://"; } ?>
							</td>
							<td width="*">
								<input type="text" style="width: 100%;" size="35" name="twitter_api" value="<?php echo $atpu_settings['twitter_api'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="twitter_uri"><?php _e('Twitter base URI', 'atpu') ?></label>
							</th>
							<td width="auto" style="vertical-align: middle;">
								<?php if (($atpu_settings['ssl'] == 'both') || ($atpu_settings['ssl'] == 'uri')) { echo "&nbsp;&nbsp;https://"; } else { echo "&nbsp;&nbsp;http://"; } ?>
							</td>
							<td width="*">
								<input type="text" style="width: 100%;" size="35" name="twitter_uri" value="<?php echo $atpu_settings['twitter_uri'] ?>" />
						    </td>
						</tr>

						<tr>
							<th width="62%">
								<label for="cache_name"><?php _e('Plugin cache name', 'atpu') ?></label>
							</th>
							<td colspan="2">
								<input type="text" style="width: 100%" size="40" name="cache_name" value="<?php echo $atpu_settings['cache_name'] ?>" />
						    </td>
						</tr>

					</table>

					<p class="submit">
						<input name="save" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
						<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="save" />
					</p>

				</form>

			</div>
		</div>
	</div>

	<div id="poststuff" class="dlm">
		<div class="postbox closed">
			<h3><?php _e('The panic box', 'atpu') ?></h3>
			<div class="inside">

				<table summary="config" class="widefat">

					<tr>
						<th colspan="3">
							<strong><?php _e('In case something went wrong, I suggest panic or:', 'atpu') ?></strong>
						</th>
					</tr>

					<tr class="form-invalid">
						<td align="center">
							<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">
								<?php wp_nonce_field('automagic-twitter-profile-uri-update-options'); ?>
								<input name="reset" type="submit" class="button-secondary" value="<?php _e('Reset Options', 'atpu') ?>" />
								<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="reset" />
							</form>
						</td>
						<td align="center">
							<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">
								<?php wp_nonce_field('automagic-twitter-profile-uri-update-options'); ?>
								<input name="reset" type="submit" class="button-secondary" value="<?php _e('Flush Cache', 'atpu') ?>" />
								<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="flush" />
							</form>
						</td>
						<td align="center">
							<form action="<?php echo str_replace('%7E', '/', $_SERVER['REQUEST_URI']); ?>" method="post">
								<?php wp_nonce_field('automagic-twitter-profile-uri-update-options'); ?>
								<input name="reset" type="submit" class="button-secondary" value="<?php _e('Drop Database Table', 'atpu') ?>" />
								<input type="hidden" name="automagic-twitter-profile-uri-update-options" value="drop" />
							</form>
					    </td>
					</tr>

				</table>

			</div>
		</div>
	</div>
	
	<script type="text/javascript">
		<!--
			jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
			jQuery('.postbox h3').click( function() {
				jQuery(jQuery(this).parent().get(0)).toggleClass('closed');
			});
			jQuery('.postbox.close-me').each(function() {
				jQuery(this).addClass("closed");
			});
		//-->
	</script>
	
</div>

<?php }

// add a link to the options page right from the plugins overview
function atpu_plugin_options_link($links, $file) {
	global $atpu_settings;
	static $this_plugin;
	
	if (!$this_plugin) { $this_plugin = plugin_basename(__FILE__); }

	if ($file == $this_plugin) {
		$atpu_settings_link = '<a href="options-general.php?page=' . $atpu_settings['short_name'] . '">' . __('Settings') . '</a>';
		$links = array_merge(array($atpu_settings_link), $links);
	}

	return $links;
}

// check for some themes, so this plugin will integrate more nicely
function atpu_check_theme($theme=false) {
	
	// uses functions that are hopefully unique to those themes
	if (function_exists('thesis_hook_after_comment_meta')) { $theme = 'Thesis'; } // Thesis

	return $theme;
}


/*
	comments form stuff
*/

// display the notice under the comment form
function atpu_notice($action=false) {
	global $user_ID, $atpu_settings;
	
	if (
			(($atpu_settings['notice_display'] == 'visitors') && !$user_ID) ||
			($atpu_settings['notice_display'] == 'all') ||
			($action == false)
		) { // enabled, right user? ?>
			<p class="<?php echo get_atpu('css', true) ?>"<?php if ($atpu_settings['css_clear']) echo ' style="clear: both;"'; ?>>
				<?php echo get_atpu('notice_text', true); ?>
			</p>
	<?php }
}

// display the option under the comment form
function atpu_option($action=false) {
	global $user_ID, $user_email, $atpu_settings;
	
	if (
			(($atpu_settings['option_display'] == 'visitors') && !$user_ID) ||
			($atpu_settings['option_display'] == 'all') ||
			($action == false)
		) { // enabled, right user?
			
			// get the cookie
			if (isset($_COOKIE['atpu_magic_' . COOKIEHASH])) {
				$checked = $_COOKIE['atpu_magic_' . COOKIEHASH];
			} else {
				if ($atpu_settings['option_default']) { $checked = 1; }
					else { $checked = 0; }
			} ?>
			<p class="<?php echo get_atpu('css', true) ?>"<?php if ($atpu_settings['css_clear']) echo ' style="clear: both;"'; ?>>
				<input type="checkbox" name="atpu_magic" id="atpu_magic" value="true" style="width: auto;" <?php if ($checked == 1) echo 'checked="checked" '; ?>/>
				<label for="atpu_magic"><?php echo get_atpu('option_text', true); ?></label>
			</p>

		<?php } else { ?>
			<input type="hidden" name="atpu_magic" id="atpu_magic" value="true" />
		<?php }
        
		if ($user_ID) { ?>
			<input type="hidden" name="email" id="email" value="<?php echo $user_email; ?>" />
		<?php }
}

// thesis helper functions; sucks, can't add a parameter to thesis_comment_hook
function atpu_notice_thesis() { atpu_notice(true); }
function atpu_option_thesis() { atpu_option(true); }

// comments form processing
function atpu_form_action() {
	global $user_ID, $atpu_settings;
	
	// check if we need to apply magic
	if ($_REQUEST['atpu_magic'] == "true") { $magic = 1; } // 1 means yes
		else { $magic = 0; } // 0 means no
    
	// things are a bit different for logged in users...
	if ($user_ID && $atpu_settings['option_display'] == 'visitors') { $magic = 1; } // those WANT magic
    
	// who are we receiving a comment from anyway?
	$email = stripslashes($_REQUEST['email']);
    
	$twitter = atpu_db_retrieve($email); // we are outside the loop
	
	if ($twitter) { // do we have an entry?
    
		if (
			($twitter['display'] && ($magic == 0)) || // was displaying, doesn't want anymore
			(!$twitter['display'] && ($magic == 1)) // wasn't displaying, wants now
		) { 
			atpu_store($email, false, 0, true, $magic);
		}
    
	} else { // we still need to store the new record, even if it's nothing
		atpu_db_store($email, false, 0, false, $magic);
	}
    
	// store cookies!
	setcookie('atpu_magic_'. COOKIEHASH, $magic, time() + 30000000, COOKIEPATH);

}


/*
	the dashboard
*/

// content of the dashboard
function atpu_dashboard() {
	global $atpu_settings, $atpu_stats;
	
	echo "<p><strong>";
	
	// the title
	if ($atpu_stats['count'] > 1) { // more than one commenter?
		printf (__('<em>%s</em> commenters have been "enchanted" so far&sup1;', 'atpu'), $atpu_stats['count']);
	} else if ($atpu_stats['count'] == 1) { // more than one commenter?
		_e('<em>One</em> commenter has been "enchanted" so far&sup1;', 'atpu');
	} else if ($atpu_stats['count'] == 0) { // ugh, only one commenter
		_e('<em>No</em> commenter has been "enchanted" so far&sup1;', 'atpu');
	}
	
	echo "</strong></p>\n";
	echo "<p>\n";

	// introduction
	if ($atpu_stats['count'] > 1) { // more than one commenter?
		printf (__('Of your <strong>%s</strong> enchanted commenters', 'atpu'), $atpu_stats['count']);
	} else if ($atpu_stats['count'] == 1) { // only one commenter?
		_e('This <strong>one</strong> enchanted commenter', 'atpu');
	}
	
	// that one commenter, is he using Twitter?
	if (
		($atpu_stats['count'] == 1) &&
		($atpu_stats['have_twitter'] == 1)
		) { // exactly one
		echo "\n";
		_e('has a Twitter username.', 'atpu'); }
	else if ($atpu_stats['count'] == 1 && $atpu_stats['have_twitter'] != 1) { // well, not one
		echo "\n";
		_e("doesn't seem to have a Twitter username.", 'atpu');
	}
	
	echo "\n";
	
	// not one, cool! how many of them are actually using Twitter?
	if ( // as many Twitter usernames as commenters
		($atpu_stats['count'] > 1) &&
		($atpu_stats['have_twitter'] == $atpu_stats['count'])
		) {
		_e('<strong>all</strong> have a Twitter username.', 'atpu');
	} else if ( // not as many Twitter usernames as commenters
		($atpu_stats['count'] > 1) &&
			(
				($atpu_stats['have_twitter'] > 1) &&
				($atpu_stats['have_twitter'] < $atpu_stats['count'])
			)
		) {
			printf (__('at least <strong>%s</strong> have a Twitter username.&sup2;', 'atpu'), $atpu_stats['have_twitter']);
	} else if ( // commenters but only one Twitter username
		($atpu_stats['count'] > 1) &&
		($atpu_stats['have_twitter'] == 1)
		) {
			_e('at least <strong>one</strong> has a Twitter username.&sup2;', 'atpu');
	} else if ( // commenters and no Twitter username
		($atpu_stats['count'] > 1) &&
		($atpu_stats['have_twitter'] == 0)
		) {
			_e('<strong>none</strong> has a Twitter username.&sup2;', 'atpu');
	}
	
	echo "\n";
	
	if ($atpu_stats['ratio']) { // only display "advanced" stats if there's more than one commenter
	
		echo "\n";
		printf (__('That means <strong>%s percent</strong> of these commenters are likely to be active Twitter users.', 'atpu'), $atpu_stats['ratio']);
		echo "\n";
		
		if ($atpu_stats['ratio'] < 25) { // very low Twitter usage
			echo "\n";
			_e("You <strong>really</strong> should engage your commenters to use Twitter so you can expand your network.", 'atpu');
		} else if (
			($atpu_stats['ratio'] >= 25) &&
			($atpu_stats['ratio'] < 50)
			) { // below 50% Twitter usage
				echo "\n";
				_e("Not so many! You should engage more of your commenters to use Twitter and to network with you.", 'atpu');
		} else if (
			($atpu_stats['ratio'] >= 50) &&
			($atpu_stats['ratio'] < 75)
			) { // pretty high Twitter usage
				echo "\n";
				_e("What a number! You are networking with them, aren't you?", 'atpu');
		} else if (
			$atpu_stats['ratio'] >= 75
			) { // very high Twitter usage
				echo "\n";
				_e("Whoah, what a whopping number! You can potentially reach lots of people through your commenters.", 'atpu');
		}
	
	}
	
	echo "\n</p>";

	// some description how this is calculated
	echo "\n<p><small>" . __('&sup1; Number of commenters who have left an email address and whose comments have been displayed to visitors since activating this plugin.', 'atpu');
	
	// only display if necessary
	if (
		($atpu_stats['count'] > 0) &&
		($atpu_stats['have_twitter'] < $atpu_stats['count'])
		) {
			echo "\n";
			echo __('&sup2; The email address used to comment here may not be the same as for their Twitter account', 'atpu');
			if ($atpu_settings['option_display']) {
				echo ' ' . __('or they do not want their Twitter username to be displayed', 'atpu');
			}
			echo ".";
	}

	echo "</small></p>";

}
 
// add the dashboard widget
function atpu_dashboard_setup() {
	global $atpu_stats, $wpdb;
	
	$table = $wpdb->prefix . 'atpu';
	$atpu_stats = array();
	
	$sql = "SELECT * FROM `$table`";
	$atpu_stats['count'] = $wpdb->query($sql);
	
	$sql = "SELECT * FROM `$table` WHERE `twitter_username`='' OR `display`='0'";
	$atpu_stats['no_twitter'] = $wpdb->query($sql);
	
	$atpu_stats['have_twitter'] = $atpu_stats['count'] - $atpu_stats['no_twitter'];
	
	if ($atpu_stats['count'] > 0) { $atpu_stats['ratio'] = round((($atpu_stats['have_twitter'] / $atpu_stats['count']) * 100), 0); }
	
	wp_add_dashboard_widget('atpu_dashboard', "Automagic Twitter Profile URI", 'atpu_dashboard');
}


/*
	the actual plugin functions
*/

// adds the Twitter link to the commenter
function add_atpu($content) {
	global $atpu_settings;
	
	if ($atpu_settings['auto']) { // do only automagic stuff when allowed
		$new_content = atpu(false);
		if ($new_content) $content .= $new_content;
	}

	if ($atpu_settings['theme']) { echo $content; } // has a value when calling as a theme action
	else { return $content; } // false if we need to filter

}

// returns any queried string as string, not echoed
function get_atpu($parameter, $debug=false) {
	$email = get_comment_author_email();
	if ($email || $debug) { // checking if are we in the comments or if it's a debug call
		global $atpu_settings;

		switch ($parameter) {
			
			// returns the commenter name
			case 'commenter': // %COMMENTER%
				if (!$debug) { $commenter = htmlentities(get_comment_author()); }
				else { $commenter = 'Benjamin Wittorf'; }
				return $commenter;
			
			// returns just the nick at Twitter
			case 'nick': // %USERNAME%
				if (!$debug) { $nick = atpu_retrieve($email); }
				else { $nick = 'bwittorf'; }
				if ($nick) {
					return $nick;
				} else {
					echo "<!-- ";
					echo "This user doesn't have a Twitter account.";
					echo " -->";
					return FALSE;
				}
				break;

			// returns the caching age
			case 'caching_age':
				return $atpu_settings['caching_age'];
				break;

			// returns the css name
			case 'css': // %CSS%
				return $atpu_settings['css'];
				break;

			// returns the comments notice text
			case 'notice_text':
				return $atpu_settings['notice_text'];
				break;

			// returns the comments option text
			case 'option_text':
				return $atpu_settings['option_text'];
				break;

			// returns the line break
			case 'line_break': // %BREAK%
				return $atpu_settings['line_break'];
				break;

			// returns the link name
			case 'link_name': // %BREAK%
				return $atpu_settings['link_name'];
				break;

			// returns the separator
			case 'separator': // %SEPARATOR%
				return $atpu_settings['separator'];
				break;

			// returns the string appender
			case 'text_app': // %APP%
				return $atpu_settings['text_app'];
				break;

			// returns the string prepender
			case 'text_pre': // %PRE%
				return $atpu_settings['text_pre'];
				break;

			// returns the theme name, if known
			case 'theme':
				return $atpu_settings['theme'];
				break;

			// returns just the Twitter uri
			case 'uri': // %URI%
				if ($atpu_settings['ssl'] == 'both' || $atpu_settings['ssl'] == 'uri') { $url = 'https://'; }
				else { $url = 'http://'; }
				$url = $url . $atpu_settings['twitter_uri'];
				return $url;
				break;

			// returns the uri to the Twitter icon
			case 'icon': // %IMG% without <img> tag
				return $atpu_settings['twitter_icon'];
				break;

			// returns the title of Twitter
			case 'title': // %TITLE%
				return $atpu_settings['twitter_title'];
				break;

			// returns the plugin version number
			case 'version':
				return $atpu_settings['version'];
				break;
				
			// returns the custom string
			case 'custom_string':
				$output = $atpu_settings['custom_string'];
				// lots of replacing now...
				$output = str_replace("%COMMENTER%", get_atpu('commenter', $debug), $output); // Commenter name
				$output = str_replace("%USERNAME%", get_atpu('nick', $debug), $output); // Twitter username
				$output = str_replace("%CSS%", get_atpu('css', $debug), $output); // css class name string
				$output = str_replace("%BREAK%", get_atpu('line_break', $debug), $output); // line break
				$output = str_replace("%SEPARATOR%", get_atpu('separator', $debug), $output); // separator
				$output = str_replace("%APP%", get_atpu('text_app', $debug), $output); // string appender
				$output = str_replace("%PRE%", get_atpu('text_pre', $debug), $output); // string prepender
				$output = str_replace("%URI%", get_atpu('uri', $debug), $output); // Twitter uri
				$output = str_replace("%IMG%", '<img src="' . get_atpu('icon', $debug) . '" title="%TITLE%" />', $output); // Twitter icon <img>
				$output = str_replace("%TITLE%", get_atpu('title', $debug), $output); // Twitter title
				return $output;
				break;

			// default, duh
			default:
				return FALSE;
		}

	} else {
		if (get_comment_author()) { // are we in the comment loop anyway?
			echo "<!-- ";
			echo "This user hasn't left an email address.";
			echo " -->";
		} else {
			echo "<!-- ";
			echo "You should be seeing an output of “Automagic Twitter Profile URI” here, but you don't - that means that you're trying to call the plugin function 'atpu()' outside the comments section.";
			echo " -->";
		}
		return FALSE;
	}
}

// the "easiest" include for the templates, returns a well formatted string
function atpu($echo=true, $debug=false) {
	global $atpu_settings;

	if (!$debug) { $nick = get_atpu('nick'); }
		else { $nick = 'bwittorf'; }

	if ($nick) { // only display the Twitter profile uri if there's an account

		if (!$atpu_settings['custom']) {

			$output = ' '; // we need do start with a trailing space

			if ($atpu_settings['line_break_add'] == 'before' || $atpu_settings['line_break_add'] == 'both') $break_pre = get_atpu('line_break', $debug);
			if ($atpu_settings['text_show']) $text_pre = get_atpu('text_pre', $debug);
			if ($atpu_settings['css_wrap']) $span_pre = '<span class="' . get_atpu('css', $debug) . '">';
			if ($atpu_settings['icon_display']) $icon = '<img src="' . get_atpu('icon', $debug) . '" title="' . get_atpu('title', $debug) . '" /> ';

			if ($atpu_settings['link_name'] == 'Twitter') { $link_name = get_atpu('title', $debug); }
			else { $link_name = '&#0064;' . get_atpu('nick', $debug); }
			
			$link_name = '<a href="' . get_atpu('uri', $debug) . '/' . get_atpu('nick', $debug) . '" title="' . __('Follow', 'atpu') . ' ' . get_atpu('commenter', $debug) . ' ' . __('at', 'atpu') . ' ' . get_atpu('title', $debug) . '">' . $link_name . '</a>';
			if ($atpu_settings['link_name'] == 'both') $link_name = get_atpu('title', $debug) . get_atpu('separator', $debug) . $link_name;
			
			if ($atpu_settings['text_show']) $text_app = get_atpu('text_app', $debug);
			if ($atpu_settings['css_wrap']) $span_app = '</span>';
			if ($atpu_settings['line_break_add'] == 'after' || $atpu_settings['line_break_add'] == 'both') $break_app = get_atpu('line_break', $debug);
			
			$output .= $break_pre . $span_pre . $text_pre . $icon . $link_name . $text_app . $span_app . $break_app;

		} else { $output = get_atpu('custom_string', $debug); }

	} else { $output = FALSE; }

	if ($echo && $nick) { echo $output; } // classic "aptu" call, output as echo
		else { return $output; } // call from automagic add, only return

}


/*
	adding actions & filter
*/

// upon activation of the plugin
/*
	WE CURRENTLY MUST MAKE THIS FAIL
*/
// register_activation_hook(__FILE__, 'atpu_install');
function atpu_activation_fail(){
	deactivate_plugins(__FILE__); // Deactivate ourself
	wp_die('As of now, <em><strong>Automagic Twitter Profile URI</strong> currently cannot work</em> anymore.<br />Please see <a href="http://code.google.com/p/twitter-api/issues/detail?id=353">Twitter Issue 353</a> and second my request to bring back the required functionality by either “starring” the topic or contributing to it.<br />Thank you very much for your help.');
}
register_activation_hook(__FILE__, 'atpu_activation_fail');
// END OF FAILWHALE

// upon removal of the plugin
register_uninstall_hook(__FILE__, 'atpu_remove');

// adding an options page
add_action('admin_menu', 'atpu_plugin_menu');
add_filter('plugin_action_links', 'atpu_plugin_options_link', 10, 2);

// add to the dashboard
add_action('wp_dashboard_setup', 'atpu_dashboard_setup');

// when does this plugin prefetch?
add_action('the_content', 'atpu_prefetch');

// automatically add the Twitter link to the commenter
add_filter('get_comment_author_link', 'add_atpu', 50); // pretty low priority so it won't interfer with any other get_comment_author_link filtering plugins - this one only needs to add, not to alter, so it should fire pretty late

// and where can we apply our functions?
add_filter('posts', 'atpu');
add_filter('posts', 'get_atpu');

// add notice & option below the comment form
add_action('comment_form', 'atpu_notice');
add_action('comment_form', 'atpu_option');

// add comment form processing
add_action('comment_post', 'atpu_form_action', 50); // very low priority so spam measures take place first

// upon initialization of the plugin
add_action('init', 'atpu_init');


/*
	Copyright
*/

/*  Copyright 2009  Benjamin Wittorf  (email : ben@immersion.io)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

?>
