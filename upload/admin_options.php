<?php

/*---

	Copyright (C) 2008-2010 FluxBB.org
	based on code copyright (C) 2002-2005 Rickard Andersson
	License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

---*/

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);


if (isset($_POST['form_sent']))
{
	// Custom referrer check (so we can output a custom error message)
	if (!preg_match('#^'.preg_quote(str_replace('www.', '', $pun_config['o_base_url']).'/admin_options.php', '#').'#i', str_replace('www.', '', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''))))
		message('Bad HTTP_REFERER. If you have moved these forums from one location to another or switched domains, you need to update the Base URL manually in the database (look for o_base_url in the config table) and then clear the cache by deleting all .php files in the /cache directory.');

	$form = array_map('pun_trim', $_POST['form']);

	if ($form['board_title'] == '')
		message('You must enter a board title.');

	// Clean default_lang
	$form['default_lang'] = preg_replace('#[\.\\\/]#', '', $form['default_lang']);

	require PUN_ROOT.'include/email.php';

	$form['admin_email'] = strtolower($form['admin_email']);
	if (!is_valid_email($form['admin_email']))
		message('The admin email address you entered is invalid.');

	$form['webmaster_email'] = strtolower($form['webmaster_email']);
	if (!is_valid_email($form['webmaster_email']))
		message('The webmaster email address you entered is invalid.');

	if ($form['mailing_list'] != '')
		$form['mailing_list'] = strtolower(preg_replace('/[\s]/', '', $form['mailing_list']));

	// Make sure base_url doesn't end with a slash
	if (substr($form['base_url'], -1) == '/')
		$form['base_url'] = substr($form['base_url'], 0, -1);

	// Clean avatars_dir
	$form['avatars_dir'] = str_replace("\0", '', $form['avatars_dir']);

	// Make sure avatars_dir doesn't end with a slash
	if (substr($form['avatars_dir'], -1) == '/')
		$form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);

	if ($form['additional_navlinks'] != '')
		$form['additional_navlinks'] = trim(pun_linebreaks($form['additional_navlinks']));

	if ($form['announcement_message'] != '')
		$form['announcement_message'] = pun_linebreaks($form['announcement_message']);
	else
	{
		$form['announcement_message'] = 'Enter your announcement here.';

		if ($form['announcement'] == '1')
			$form['announcement'] = '0';
	}

	if ($form['rules_message'] != '')
		$form['rules_message'] = pun_linebreaks($form['rules_message']);
	else
	{
		$form['rules_message'] = 'Enter your rules here.';

		if ($form['rules'] == '1')
			$form['rules'] = '0';
	}

	if ($form['maintenance_message'] != '')
		$form['maintenance_message'] = pun_linebreaks($form['maintenance_message']);
	else
	{
		$form['maintenance_message'] = 'The forums are temporarily down for maintenance. Please try again in a few minutes.\n\n/Administrator';

		if ($form['maintenance'] == '1')
			$form['maintenance'] = '0';
	}

	$form['timeout_visit'] = intval($form['timeout_visit']);
	$form['timeout_online'] = intval($form['timeout_online']);
	$form['redirect_delay'] = intval($form['redirect_delay']);
	$form['topic_review'] = intval($form['topic_review']);
	$form['disp_topics_default'] = intval($form['disp_topics_default']);
	$form['disp_posts_default'] = intval($form['disp_posts_default']);
	$form['indent_num_spaces'] = intval($form['indent_num_spaces']);
	$form['quote_depth'] = intval($form['quote_depth']);
	$form['avatars_width'] = intval($form['avatars_width']);
	$form['avatars_height'] = intval($form['avatars_height']);
	$form['avatars_size'] = intval($form['avatars_size']);

	// Make sure the number of displayed topics and posts is between 3 and 75
	if ($form['disp_topics_default'] < 3) $form['disp_topics_default'] = 3;
	if ($form['disp_topics_default'] > 75) $form['disp_topics_default'] = 75;

	if ($form['disp_posts_default'] < 3) $form['disp_posts_default'] = 3;
	if ($form['disp_posts_default'] > 75) $form['disp_posts_default'] = 75;

	if ($form['timeout_online'] >= $form['timeout_visit'])
		message('The value of "Timeout online" must be smaller than the value of "Timeout visit".');

	foreach ($form as $key => $input)
	{
		// Only update values that have changed
		if (array_key_exists('o_'.$key, $pun_config) && $pun_config['o_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$db->escape($input).'\'';
			else
				$value = 'NULL';

			$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
		}
	}

	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_config_cache();

	redirect('admin_options.php', 'Options updated. Redirecting &hellip;');
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), 'Admin', 'Options');
define('FORUM_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('options');

?>
	<div class="blockform">
		<h2><span>Options</span></h2>
		<div class="box">
			<form method="post" action="admin_options.php?action=foo">
				<p class="submittop"><input type="submit" name="save" value="Save changes" /></p>
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend>Essentials</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Board title</th>
									<td>
										<input type="text" name="form[board_title]" size="50" maxlength="255" value="<?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?>" />
										<span>The title of this bulletin board (shown at the top of every page). This field may <strong>not</strong> contain HTML.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Board description</th>
									<td>
										<input type="text" name="form[board_desc]" size="50" maxlength="255" value="<?php echo pun_htmlspecialchars($pun_config['o_board_desc']) ?>" />
										<span>A short description of this bulletin board (shown at the top of every page). This field may contain HTML.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Base URL</th>
									<td>
										<input type="text" name="form[base_url]" size="50" maxlength="100" value="<?php echo $pun_config['o_base_url'] ?>" />
										<span>The complete URL of the board without trailing slash (i.e. http://www.mydomain.com/forums). This <strong>must</strong> be correct in order for all admin and moderator features to work. If you get "Bad referer" errors, it's probably incorrect.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Default time zone</th>
									<td>
										<select name="form[default_timezone]">
											<option value="-12"<?php if ($pun_config['o_default_timezone'] == -12) echo ' selected="selected"' ?>>(UTC-12:00) International Date Line West</option>
											<option value="-11"<?php if ($pun_config['o_default_timezone'] == -11) echo ' selected="selected"' ?>>(UTC-11:00) Niue, Samoa</option>
											<option value="-10"<?php if ($pun_config['o_default_timezone'] == -10) echo ' selected="selected"' ?>>(UTC-10:00) Hawaii-Aleutian, Cook Island</option>
											<option value="-9.5"<?php if ($pun_config['o_default_timezone'] == -9.5) echo ' selected="selected"' ?>>(UTC-09:30) Marquesas Islands</option>
											<option value="-9"<?php if ($pun_config['o_default_timezone'] == -9) echo ' selected="selected"' ?>>(UTC-09:00) Alaska, Gambier Island</option>
											<option value="-8.5"<?php if ($pun_config['o_default_timezone'] == -8.5) echo ' selected="selected"' ?>>(UTC-08:30) Pitcairn Islands</option>
											<option value="-8"<?php if ($pun_config['o_default_timezone'] == -8) echo ' selected="selected"' ?>>(UTC-08:00) Pacific</option>
											<option value="-7"<?php if ($pun_config['o_default_timezone'] == -7) echo ' selected="selected"' ?>>(UTC-07:00) Mountain</option>
											<option value="-6"<?php if ($pun_config['o_default_timezone'] == -6) echo ' selected="selected"' ?>>(UTC-06:00) Central</option>
											<option value="-5"<?php if ($pun_config['o_default_timezone'] == -5) echo ' selected="selected"' ?>>(UTC-05:00) Eastern</option>
											<option value="-4"<?php if ($pun_config['o_default_timezone'] == -4) echo ' selected="selected"' ?>>(UTC-04:00) Atlantic</option>
											<option value="-3.5"<?php if ($pun_config['o_default_timezone'] == -3.5) echo ' selected="selected"' ?>>(UTC-03:30) Newfoundland</option>
											<option value="-3"<?php if ($pun_config['o_default_timezone'] == -3) echo ' selected="selected"' ?>>(UTC-03:00) Amazon, Central Greenland</option>
											<option value="-2"<?php if ($pun_config['o_default_timezone'] == -2) echo ' selected="selected"' ?>>(UTC-02:00) Mid-Atlantic</option>
											<option value="-1"<?php if ($pun_config['o_default_timezone'] == -1) echo ' selected="selected"' ?>>(UTC-01:00) Azores, Cape Verde, Eastern Greenland</option>
											<option value="0"<?php if ($pun_config['o_default_timezone'] == 0) echo ' selected="selected"' ?>>(UTC) Western European, Greenwich</option>
											<option value="1"<?php if ($pun_config['o_default_timezone'] == 1) echo ' selected="selected"' ?>>(UTC+01:00) Central European, West African</option>
											<option value="2"<?php if ($pun_config['o_default_timezone'] == 2) echo ' selected="selected"' ?>>(UTC+02:00) Eastern European, Central African</option>
											<option value="3"<?php if ($pun_config['o_default_timezone'] == 3) echo ' selected="selected"' ?>>(UTC+03:00) Moscow, Eastern African</option>
											<option value="3.5"<?php if ($pun_config['o_default_timezone'] == 3.5) echo ' selected="selected"' ?>>(UTC+03:30) Iran</option>
											<option value="4"<?php if ($pun_config['o_default_timezone'] == 4) echo ' selected="selected"' ?>>(UTC+04:00) Gulf, Samara</option>
											<option value="4.5"<?php if ($pun_config['o_default_timezone'] == 4.5) echo ' selected="selected"' ?>>(UTC+04:30) Afghanistan</option>
											<option value="5"<?php if ($pun_config['o_default_timezone'] == 5) echo ' selected="selected"' ?>>(UTC+05:00) Pakistan, Yekaterinburg</option>
											<option value="5.5"<?php if ($pun_config['o_default_timezone'] == 5.5) echo ' selected="selected"' ?>>(UTC+05:30) India, Sri Lanka</option>
											<option value="5.75"<?php if ($pun_config['o_default_timezone'] == 5.75) echo ' selected="selected"' ?>>(UTC+05:45) Nepal</option>
											<option value="6"<?php if ($pun_config['o_default_timezone'] == 6) echo ' selected="selected"' ?>>(UTC+06:00) Bangladesh, Bhutan, Novosibirsk</option>
											<option value="6.5"<?php if ($pun_config['o_default_timezone'] == 6.5) echo ' selected="selected"' ?>>(UTC+06:30) Cocos Islands, Myanmar</option>
											<option value="7"<?php if ($pun_config['o_default_timezone'] == 7) echo ' selected="selected"' ?>>(UTC+07:00) Indochina, Krasnoyarsk</option>
											<option value="8"<?php if ($pun_config['o_default_timezone'] == 8) echo ' selected="selected"' ?>>(UTC+08:00) Greater China, Australian Western, Irkutsk</option>
											<option value="8.75"<?php if ($pun_config['o_default_timezone'] == 8.75) echo ' selected="selected"' ?>>(UTC+08:45) Southeastern Western Australia</option>
											<option value="9"<?php if ($pun_config['o_default_timezone'] == 9) echo ' selected="selected"' ?>>(UTC+09:00) Japan, Korea, Chita</option>
											<option value="9.5"<?php if ($pun_config['o_default_timezone'] == 9.5) echo ' selected="selected"' ?>>(UTC+09:30) Australian Central</option>
											<option value="10"<?php if ($pun_config['o_default_timezone'] == 10) echo ' selected="selected"' ?>>(UTC+10:00) Australian Eastern, Vladivostok</option>
											<option value="10.5"<?php if ($pun_config['o_default_timezone'] == 10.5) echo ' selected="selected"' ?>>(UTC+10:30) Lord Howe</option>
											<option value="11"<?php if ($pun_config['o_default_timezone'] == 11) echo ' selected="selected"' ?>>(UTC+11:00) Solomon Island, Magadan</option>
											<option value="11.5"<?php if ($pun_config['o_default_timezone'] == 11.5) echo ' selected="selected"' ?>>(UTC+11:30) Norfolk Island</option>
											<option value="12"<?php if ($pun_config['o_default_timezone'] == 12) echo ' selected="selected"' ?>>(UTC+12:00) New Zealand, Fiji, Kamchatka</option>
											<option value="12.75"<?php if ($pun_config['o_default_timezone'] == 12.75) echo ' selected="selected"' ?>>(UTC+12:45) Chatham Islands</option>
											<option value="13"<?php if ($pun_config['o_default_timezone'] == 13) echo ' selected="selected"' ?>>(UTC+13:00) Tonga, Phoenix Islands</option>
											<option value="14"<?php if ($pun_config['o_default_timezone'] == 14) echo ' selected="selected"' ?>>(UTC+14:00) Line Islands</option>
										</select>
										<span>The default time zone for guests and users attempting to register for the board.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Adjust for DST</th>
									<td>
										<input type="radio" name="form[default_dst]" value="1"<?php if ($pun_config['o_default_dst'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[default_dst]" value="0"<?php if ($pun_config['o_default_dst'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Check if daylight savings is in effect (advances times by 1 hour).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Default language</th>
									<td>
										<select name="form[default_lang]">
<?php

		$languages = array();
		$d = dir(PUN_ROOT.'lang');
		while (($entry = $d->read()) !== false)
		{
			if ($entry{0} != '.' && is_dir(PUN_ROOT.'lang/'.$entry) && file_exists(PUN_ROOT.'lang/'.$entry.'/common.php'))
				$languages[] = $entry;
		}
		$d->close();

		@natsort($languages);

		foreach ($languages as $temp)
		{
			if ($pun_config['o_default_lang'] == $temp)
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
		}

?>
										</select>
										<span>This is the default language used for guests and users who haven't changed from the default in their profile. If you remove a language pack, this must be updated.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Default style</th>
									<td>
										<select name="form[default_style]">
<?php

		$styles = array();
		$d = dir(PUN_ROOT.'style');
		while (($entry = $d->read()) !== false)
		{
			if (substr($entry, strlen($entry)-4) == '.css')
				$styles[] = substr($entry, 0, strlen($entry)-4);
		}
		$d->close();

		@natsort($styles);

		foreach ($styles as $temp)
		{
			if ($pun_config['o_default_style'] == $temp)
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}

?>
										</select>
										<span>This is the default style used for guests and users who haven't changed from the default in their profile.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Time and timeouts</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Time format</th>
									<td>
										<input type="text" name="form[time_format]" size="25" maxlength="25" value="<?php echo pun_htmlspecialchars($pun_config['o_time_format']) ?>" />
										<span>[Current format: <?php echo date($pun_config['o_time_format']) ?>]&nbsp;See <a href="http://www.php.net/manual/en/function.date.php">here</a> for formatting options.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Date format</th>
									<td>
										<input type="text" name="form[date_format]" size="25" maxlength="25" value="<?php echo pun_htmlspecialchars($pun_config['o_date_format']) ?>" />
										<span>[Current format: <?php echo date($pun_config['o_date_format']) ?>]&nbsp;See <a href="http://www.php.net/manual/en/function.date.php">here</a> for formatting options.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Visit timeout</th>
									<td>
										<input type="text" name="form[timeout_visit]" size="5" maxlength="5" value="<?php echo $pun_config['o_timeout_visit'] ?>" />
										<span>Number of seconds a user must be idle before his/hers last visit data is updated (primarily affects new message indicators).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Online timeout</th>
									<td>
										<input type="text" name="form[timeout_online]" size="5" maxlength="5" value="<?php echo $pun_config['o_timeout_online'] ?>" />
										<span>Number of seconds a user must be idle before being removed from the online users list.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Redirect time</th>
									<td>
										<input type="text" name="form[redirect_delay]" size="3" maxlength="3" value="<?php echo $pun_config['o_redirect_delay'] ?>" />
										<span>Number of seconds to wait when redirecting. If set to 0, no redirect page will be displayed (not recommended).</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Display</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Version number</th>
									<td>
										<input type="radio" name="form[show_version]" value="1"<?php if ($pun_config['o_show_version'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_version]" value="0"<?php if ($pun_config['o_show_version'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Show version number in footer.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">User info in posts</th>
									<td>
										<input type="radio" name="form[show_user_info]" value="1"<?php if ($pun_config['o_show_user_info'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_user_info]" value="0"<?php if ($pun_config['o_show_user_info'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Show information about the poster under the username in topic view. The information affected is location, register date, post count and the contact links (email and URL).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">User post count</th>
									<td>
										<input type="radio" name="form[show_post_count]" value="1"<?php if ($pun_config['o_show_post_count'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_post_count]" value="0"<?php if ($pun_config['o_show_post_count'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Show the number of posts a user has made (affects topic view, profile and user list).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Smilies</th>
									<td>
										<input type="radio" name="form[smilies]" value="1"<?php if ($pun_config['o_smilies'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[smilies]" value="0"<?php if ($pun_config['o_smilies'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Convert smilies to small icons.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Smilies in signatures</th>
									<td>
										<input type="radio" name="form[smilies_sig]" value="1"<?php if ($pun_config['o_smilies_sig'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[smilies_sig]" value="0"<?php if ($pun_config['o_smilies_sig'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Convert smilies to small icons in user signatures.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Make clickable links</th>
									<td>
										<input type="radio" name="form[make_links]" value="1"<?php if ($pun_config['o_make_links'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[make_links]" value="0"<?php if ($pun_config['o_make_links'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, FluxBB will automatically detect any URLs in posts and make them clickable hyperlinks.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Topic review</th>
									<td>
										<input type="text" name="form[topic_review]" size="3" maxlength="3" value="<?php echo $pun_config['o_topic_review'] ?>" />
										<span>Maximum number of posts to display when posting (newest first). Set to 0 to disable.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Topics per page default</th>
									<td>
										<input type="text" name="form[disp_topics_default]" size="3" maxlength="3" value="<?php echo $pun_config['o_disp_topics_default'] ?>" />
										<span>The default number of topics to display per page in a forum. Users can personalize this setting.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Posts per page default</th>
									<td>
										<input type="text" name="form[disp_posts_default]" size="3" maxlength="3" value="<?php echo $pun_config['o_disp_posts_default'] ?>" />
										<span>The default number of posts to display per page in a topic. Users can personalize this setting.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Indent size</th>
									<td>
										<input type="text" name="form[indent_num_spaces]" size="3" maxlength="3" value="<?php echo $pun_config['o_indent_num_spaces'] ?>" />
										<span>If set to 8, a regular tab will be used when displaying text within the [code][/code] tag. Otherwise this many spaces will be used to indent the text.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Maximum [quote] depth</th>
									<td>
										<input type="text" name="form[quote_depth]" size="3" maxlength="3" value="<?php echo $pun_config['o_quote_depth'] ?>" />
										<span>The maximum times a [quote] tag can go inside other [quote] tags, any tags deeper than this will be discarded.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Features</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Quick post</th>
									<td>
										<input type="radio" name="form[quickpost]" value="1"<?php if ($pun_config['o_quickpost'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[quickpost]" value="0"<?php if ($pun_config['o_quickpost'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, FluxBB will add a quick post form at the bottom of topics. This way users can post directly from the topic view.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Users online</th>
									<td>
										<input type="radio" name="form[users_online]" value="1"<?php if ($pun_config['o_users_online'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[users_online]" value="0"<?php if ($pun_config['o_users_online'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Display info on the index page about guests and registered users currently browsing the board.</span>
									</td>
								</tr>
								<tr>
									<th scope="row"><a name="censoring">Censor words</a></th>
									<td>
										<input type="radio" name="form[censoring]" value="1"<?php if ($pun_config['o_censoring'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[censoring]" value="0"<?php if ($pun_config['o_censoring'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Enable this to censor specific words in the board. See <a href="admin_censoring.php">Censoring</a> for more info.</span>
									</td>
								</tr>
								<tr>
									<th scope="row"><a name="signatures">Signatures</a></th>
									<td>
										<input type="radio" name="form[signatures]" value="1"<?php if ($pun_config['o_signatures'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[signatures]" value="0"<?php if ($pun_config['o_signatures'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Allow users to attach a signature to their posts.</span>
									</td>
								</tr>
								<tr>
									<th scope="row"><a name="ranks">User ranks</a></th>
									<td>
										<input type="radio" name="form[ranks]" value="1"<?php if ($pun_config['o_ranks'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[ranks]" value="0"<?php if ($pun_config['o_ranks'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Enable this to use user ranks. See <a href="admin_ranks.php">Ranks</a> for more info.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">User has posted earlier</th>
									<td>
										<input type="radio" name="form[show_dot]" value="1"<?php if ($pun_config['o_show_dot'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[show_dot]" value="0"<?php if ($pun_config['o_show_dot'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>This feature displays a dot in front of topics in viewforum.php in case the currently logged in user has posted in that topic earlier. Disable if you are experiencing high server load.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Topic views</th>
									<td>
										<input type="radio" name="form[topic_views]" value="1"<?php if ($pun_config['o_topic_views'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[topic_views]" value="0"<?php if ($pun_config['o_topic_views'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Keep track of the number of views a topic has. Disable if you are experiencing high server load in a busy forum.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Quick jump</th>
									<td>
										<input type="radio" name="form[quickjump]" value="1"<?php if ($pun_config['o_quickjump'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[quickjump]" value="0"<?php if ($pun_config['o_quickjump'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Enable the quick jump (jump to forum) drop list.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">GZip output</th>
									<td>
										<input type="radio" name="form[gzip]" value="1"<?php if ($pun_config['o_gzip'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[gzip]" value="0"<?php if ($pun_config['o_gzip'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>If enabled, FluxBB will gzip the output sent to browsers. This will reduce bandwidth usage, but use a little more CPU. This feature requires that PHP is configured with zlib (--with-zlib). Note: If you already have one of the Apache modules mod_gzip or mod_deflate set up to compress PHP scripts, you should disable this feature.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Search all forums</th>
									<td>
										<input type="radio" name="form[search_all_forums]" value="1"<?php if ($pun_config['o_search_all_forums'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[search_all_forums]" value="0"<?php if ($pun_config['o_search_all_forums'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When disabled, searches will only be allowed in one forum at a time. Disable if server load is high due to excessive searching.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Additional menu items</th>
									<td>
										<textarea name="form[additional_navlinks]" rows="3" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_additional_navlinks']) ?></textarea>
										<span>By entering HTML hyperlinks into this textbox, any number of items can be added to the navigation menu at the top of all pages. The format for adding new links is X = &lt;a href="URL"&gt;LINK&lt;/a&gt; where X is the position at which the link should be inserted (e.g. 0 to insert at the beginning and 2 to insert after "User list"). Separate entries with a linebreak.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Default feed type</th>
									<td>
										<input type="radio" name="form[feed_type]" value="0"<?php if ($pun_config['o_feed_type'] == '0') echo ' checked="checked"' ?> />&nbsp;None&nbsp;&nbsp;&nbsp;<input type="radio" name="form[feed_type]" value="1"<?php if ($pun_config['o_feed_type'] == '1') echo ' checked="checked"' ?> />&nbsp;RSS&nbsp;&nbsp;&nbsp;<input type="radio" name="form[feed_type]" value="2"<?php if ($pun_config['o_feed_type'] == '2') echo ' checked="checked"' ?> />&nbsp;Atom
										<span>Select the type of syndication feed to display. Note: Choosing none will not disable feeds, only hide them by default.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Reports</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Report method</th>
									<td>
										<input type="radio" name="form[report_method]" value="0"<?php if ($pun_config['o_report_method'] == '0') echo ' checked="checked"' ?> />&nbsp;Internal&nbsp;&nbsp;&nbsp;<input type="radio" name="form[report_method]" value="1"<?php if ($pun_config['o_report_method'] == '1') echo ' checked="checked"' ?> />&nbsp;Email&nbsp;&nbsp;&nbsp;<input type="radio" name="form[report_method]" value="2"<?php if ($pun_config['o_report_method'] == '2') echo ' checked="checked"' ?> />&nbsp;Both
										<span>Select the method for handling topic/post reports. You can choose whether topic/post reports should be handled by the internal report system, emailed to the addresses on the mailing list (see below) or both.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Mailing list</th>
									<td>
										<textarea name="form[mailing_list]" rows="5" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_mailing_list']) ?></textarea>
										<span>A comma separated list of subscribers. The people on this list are the recipients of reports.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Avatars</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Use avatars</th>
									<td>
										<input type="radio" name="form[avatars]" value="1"<?php if ($pun_config['o_avatars'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[avatars]" value="0"<?php if ($pun_config['o_avatars'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, users will be able to upload an avatar which will be displayed under their title/rank.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Upload directory</th>
									<td>
										<input type="text" name="form[avatars_dir]" size="35" maxlength="50" value="<?php echo pun_htmlspecialchars($pun_config['o_avatars_dir']) ?>" />
										<span>The upload directory for avatars (relative to the FluxBB root directory). PHP must have write permissions to this directory.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Max width</th>
									<td>
										<input type="text" name="form[avatars_width]" size="5" maxlength="5" value="<?php echo $pun_config['o_avatars_width'] ?>" />
										<span>The maximum allowed width of avatars in pixels (60 is recommended).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Max height</th>
									<td>
										<input type="text" name="form[avatars_height]" size="5" maxlength="5" value="<?php echo $pun_config['o_avatars_height'] ?>" />
										<span>The maximum allowed height of avatars in pixels (60 is recommended).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Max size</th>
									<td>
										<input type="text" name="form[avatars_size]" size="6" maxlength="6" value="<?php echo $pun_config['o_avatars_size'] ?>" />
										<span>The maximum allowed size of avatars in bytes (10240 is recommended).</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Email</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Admin email</th>
									<td>
										<input type="text" name="form[admin_email]" size="50" maxlength="80" value="<?php echo $pun_config['o_admin_email'] ?>" />
										<span>The email address of the board administrator.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Webmaster email</th>
									<td>
										<input type="text" name="form[webmaster_email]" size="50" maxlength="80" value="<?php echo $pun_config['o_webmaster_email'] ?>" />
										<span>This is the address that all emails sent by the board will be addressed from.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Subscriptions</th>
									<td>
										<input type="radio" name="form[subscriptions]" value="1"<?php if ($pun_config['o_subscriptions'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[subscriptions]" value="0"<?php if ($pun_config['o_subscriptions'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Enable users to subscribe to topics (receive email when someone replies).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">SMTP server address</th>
									<td>
										<input type="text" name="form[smtp_host]" size="30" maxlength="100" value="<?php echo pun_htmlspecialchars($pun_config['o_smtp_host']) ?>" />
										<span>The address of an external SMTP server to send emails with. You can specify a custom port number if the SMTP server doesn't run on the default port 25 (example: mail.myhost.com:3580). Leave blank to use the local mail program.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">SMTP username</th>
									<td>
										<input type="text" name="form[smtp_user]" size="25" maxlength="50" value="<?php echo pun_htmlspecialchars($pun_config['o_smtp_user']) ?>" />
										<span>Username for SMTP server. Only enter a username if it is required by the SMTP server (most servers <strong>do not</strong> require authentication).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">SMTP password</th>
									<td>
										<input type="text" name="form[smtp_pass]" size="25" maxlength="50" value="<?php echo pun_htmlspecialchars($pun_config['o_smtp_pass']) ?>" />
										<span>Password for SMTP server. Only enter a password if it is required by the SMTP server (most servers <strong>do not</strong> require authentication).</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Encrypt SMTP using SSL</th>
									<td>
										<input type="radio" name="form[smtp_ssl]" value="1"<?php if ($pun_config['o_smtp_ssl'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[smtp_ssl]" value="0"<?php if ($pun_config['o_smtp_ssl'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Encrypts the connection to the SMTP server using SSL. Should only be used if your SMTP server requires it and your version of PHP supports SSL.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Registration</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Allow new registrations</th>
									<td>
										<input type="radio" name="form[regs_allow]" value="1"<?php if ($pun_config['o_regs_allow'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[regs_allow]" value="0"<?php if ($pun_config['o_regs_allow'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Controls whether this board accepts new registrations. Disable only under special circumstances.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Verify registrations</th>
									<td>
										<input type="radio" name="form[regs_verify]" value="1"<?php if ($pun_config['o_regs_verify'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[regs_verify]" value="0"<?php if ($pun_config['o_regs_verify'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, users are emailed a random password when they register. They can then log in and change the password in their profile if they see fit. This feature also requires users to verify new email addresses if they choose to change from the one they registered with. This is an effective way of avoiding registration abuse and making sure that all users have "correct" email addresses in their profiles.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Report new registrations</th>
									<td>
										<input type="radio" name="form[regs_report]" value="1"<?php if ($pun_config['o_regs_report'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[regs_report]" value="0"<?php if ($pun_config['o_regs_report'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>If enabled, FluxBB will notify users on the mailing list (see above) when a new user registers in the board.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Use forum rules</th>
									<td>
										<input type="radio" name="form[rules]" value="1"<?php if ($pun_config['o_rules'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[rules]" value="0"<?php if ($pun_config['o_rules'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, users must agree to a set of rules when registering (enter text below). The rules will always be available through a link in the navigation table at the top of every page.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Rules</th>
									<td>
										<textarea name="form[rules_message]" rows="10" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_rules_message']) ?></textarea>
										<span>Here you can enter any rules or other information that the user must review and accept when registering. If you enabled rules above you have to enter something here, otherwise it will be disabled. This text will not be parsed like regular posts and thus may contain HTML.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Default email setting</th>
									<td>
										<span>Choose the default privacy setting for new user registrations.</span>
										<input type="radio" name="form[default_email_setting]" value="0"<?php if ($pun_config['o_default_email_setting'] == '0') echo ' checked="checked"' ?> />&nbsp;Display email address to other users.<br />
										<input type="radio" name="form[default_email_setting]" value="1"<?php if ($pun_config['o_default_email_setting'] == '1') echo ' checked="checked"' ?> />&nbsp;Hide email address but allow form email.<br />
										<input type="radio" name="form[default_email_setting]" value="2"<?php if ($pun_config['o_default_email_setting'] == '2') echo ' checked="checked"' ?> />&nbsp;Hide email address and disallow form email.<br />
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Announcement</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Display announcement</th>
									<td>
										<input type="radio" name="form[announcement]" value="1"<?php if ($pun_config['o_announcement'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[announcement]" value="0"<?php if ($pun_config['o_announcement'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>Enable this to display the below message in the board.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Announcement message</th>
									<td>
										<textarea name="form[announcement_message]" rows="5" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_announcement_message']) ?></textarea>
										<span>This text will not be parsed like regular posts and thus may contain HTML.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend>Maintenance</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><a name="maintenance">Maintenance mode</a></th>
									<td>
										<input type="radio" name="form[maintenance]" value="1"<?php if ($pun_config['o_maintenance'] == '1') echo ' checked="checked"' ?> />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="form[maintenance]" value="0"<?php if ($pun_config['o_maintenance'] == '0') echo ' checked="checked"' ?> />&nbsp;<strong>No</strong>
										<span>When enabled, the board will only be available to administrators. This should be used if the board needs to be taken down temporarily for maintenance. WARNING! Do not log out when the board is in maintenance mode. You will not be able to login again.</span>
									</td>
								</tr>
								<tr>
									<th scope="row">Maintenance message</th>
									<td>
										<textarea name="form[maintenance_message]" rows="5" cols="55"><?php echo pun_htmlspecialchars($pun_config['o_maintenance_message']) ?></textarea>
										<span>The message that will be displayed to users when the board is in maintenance mode. If left blank, a default message will be used. This text will not be parsed like regular posts and thus may contain HTML.</span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="save" value="Save changes" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

require PUN_ROOT.'footer.php';
