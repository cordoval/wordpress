<?php

$curpath = dirname(__FILE__).'/';

require($curpath . 'functions-formatting.php');

if (!function_exists('_')) {
	function _($string) {
		return $string;
	}
}

if (!function_exists('floatval')) {
	function floatval($string) {
		return ((float) $string);
	}
}

function popuplinks($text) {
	// Comment text in popup windows should be filtered through this.
	// Right now it's a moderately dumb function, ideally it would detect whether
	// a target or rel attribute was already there and adjust its actions accordingly.
	$text = preg_replace('/<a (.+?)>/i', "<a $1 target='_blank' rel='external'>", $text);
	return $text;
}

function mysql2date($dateformatstring, $mysqlstring, $use_b2configmonthsdays = 1) {
	global $month, $weekday;
	$m = $mysqlstring;
	if (empty($m)) {
		return false;
	}
	$i = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)); 
	if (!empty($month) && !empty($weekday) && $use_b2configmonthsdays) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	if (!$j) {
	// for debug purposes
	//	echo $i." ".$mysqlstring;
	}
	return $j;
}

function current_time($type) {
	$time_difference = get_settings('time_difference');
	switch ($type) {
		case 'mysql':
			return date('Y-m-d H:i:s', (time() + ($time_difference * 3600) ) );
			break;
		case 'timestamp':
			return (time() + ($time_difference * 3600) );
			break;
	}
}

function date_i18n($dateformatstring, $unixtimestamp) {
	global $month, $weekday;
	$i = $unixtimestamp; 
	if ((!empty($month)) && (!empty($weekday))) {
		$datemonth = $month[date('m', $i)];
		$dateweekday = $weekday[date('w', $i)];
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit(substr($dateweekday, 0, 3)), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit(substr($datemonth, 0, 3)), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	return $j;
	}

function get_weekstartend($mysqlstring, $start_of_week) {
	$my = substr($mysqlstring,0,4);
	$mm = substr($mysqlstring,8,2);
	$md = substr($mysqlstring,5,2);
	$day = mktime(0,0,0, $md, $mm, $my);
	$weekday = date('w',$day);
	$i = 86400;
	while ($weekday > $start_of_week) {
		$weekday = date('w',$day);
		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	$week['end']   = $day + 691199;
	return $week;
}

function get_lastpostdate() {
	global $tableposts, $cache_lastpostdate, $use_cache, $time_difference, $pagenow, $wpdb;
	if ((!isset($cache_lastpostdate)) OR (!$use_cache)) {
		$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));

		$lastpostdate = $wpdb->get_var("SELECT post_date FROM $tableposts WHERE post_date <= '$now' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1");
		$cache_lastpostdate = $lastpostdate;
	} else {
		$lastpostdate = $cache_lastpostdate;
	}
	return $lastpostdate;
}

function user_pass_ok($user_login,$user_pass) {
	global $cache_userdata,$use_cache;
	if ((empty($cache_userdata[$user_login])) OR (!$use_cache)) {
		$userdata = get_userdatabylogin($user_login);
	} else {
		$userdata = $cache_userdata[$user_login];
	}
	return ($user_pass == $userdata->user_pass);
}

function get_currentuserinfo() { // a bit like get_userdata(), on steroids
	global $HTTP_COOKIE_VARS, $user_login, $userdata, $user_level, $user_ID, $user_nickname, $user_email, $user_url, $user_pass_md5, $cookiehash;
	// *** retrieving user's data from cookies and db - no spoofing
	$user_login = $HTTP_COOKIE_VARS['wordpressuser_'.$cookiehash];
	$userdata = get_userdatabylogin($user_login);
	$user_level = $userdata->user_level;
	$user_ID = $userdata->ID;
	$user_nickname = $userdata->user_nickname;
	$user_email = $userdata->user_email;
	$user_url = $userdata->user_url;
	$user_pass_md5 = md5($userdata->user_pass);
}

function get_userdata($userid) {
	global $wpdb, $cache_userdata, $use_cache, $tableusers;
	if ((empty($cache_userdata[$userid])) || (!$use_cache)) {
		$user = $wpdb->get_row("SELECT * FROM $tableusers WHERE ID = '$userid'");
        $user->user_nickname = stripslashes($user->user_nickname);
        $user->user_firstname = stripslashes($user->user_firstname);
        $user->user_lastname = stripslashes($user->user_lastname);
        $user->user_firstname =  stripslashes($user->user_firstname);
        $user->user_lastname = stripslashes($user->user_lastname);
		$user->user_description = stripslashes($user->user_description);
		$cache_userdata[$userid] = $user;
	} else {
		$user = $cache_userdata[$userid];
	}
	return $user;
}

function get_userdata2($userid) { // for team-listing
	global $tableusers, $post;
	$user_data['ID'] = $userid;
	$user_data['user_login'] = $post->user_login;
	$user_data['user_firstname'] = $post->user_firstname;
	$user_data['user_lastname'] = $post->user_lastname;
	$user_data['user_nickname'] = $post->user_nickname;
	$user_data['user_level'] = $post->user_level;
	$user_data['user_email'] = $post->user_email;
	$user_data['user_url'] = $post->user_url;
	return $user_data;
}

function get_userdatabylogin($user_login) {
	global $tableusers, $cache_userdata, $use_cache, $wpdb;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
		$user = $wpdb->get_row("SELECT * FROM $tableusers WHERE user_login = '$user_login'");
		$cache_userdata["$user_login"] = $user;
	} else {
		$user = $cache_userdata["$user_login"];
	}
	return $user;
}

function get_userid($user_login) {
	global $tableusers, $cache_userdata, $use_cache, $wpdb;
	if ((empty($cache_userdata["$user_login"])) OR (!$use_cache)) {
		$user_id = $wpdb->get_var("SELECT ID FROM $tableusers WHERE user_login = '$user_login'");

		$cache_userdata["$user_login"] = $user_id;
	} else {
		$user_id = $cache_userdata["$user_login"];
	}
	return $user_id;
}

function get_usernumposts($userid) {
	global $tableposts, $tablecomments, $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM $tableposts WHERE post_author = '$userid'");
}

// examine a url (supposedly from this blog) and try to
// determine the post ID it represents.
function url_to_postid($url = '') {
	global $wpdb, $tableposts, $siteurl;

	// Take a link like 'http://example.com/blog/something'
	// and extract just the '/something':
	$uri = preg_replace("#$siteurl#i", '', $url);

	// on failure, preg_replace just returns the subject string
	// so if $uri and $siteurl are the same, they didn't match:
	if ($uri == $siteurl) 
		return 0;
		
	// First, check to see if there is a 'p=N' to match against:
	preg_match('#[?&]p=(\d+)#', $uri, $values);
	$p = intval($values[1]);
	if ($p) return $p;
	
	// Match $uri against our permalink structure
	$permalink_structure = get_settings('permalink_structure');
	
	// Matt's tokenizer code
	$rewritecode = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%postname%',
		'%post_id%'
	);
	$rewritereplace = array(
		'([0-9]{4})?',
		'([0-9]{1,2})?',
		'([0-9]{1,2})?',
		'([0-9a-z-]+)?',
		'([0-9]+)?'
	);

	// Turn the structure into a regular expression
	$matchre = str_replace('/', '/?', $permalink_structure);
	$matchre = str_replace($rewritecode, $rewritereplace, $matchre);

	// Extract the key values from the uri:
	preg_match("#$matchre#",$uri,$values);

	// Extract the token names from the structure:
	preg_match_all("#%(.+?)%#", $permalink_structure, $tokens);

	for($i = 0; $i < count($tokens[1]); $i++) {
		$name = $tokens[1][$i];
		$value = $values[$i+1];

		// Create a variable named $year, $monthnum, $day, $postname, or $post_id:
		$$name = $value;
	}
	
	// If using %post_id%, we're done:
	if (intval($post_id)) return intval($post_id);

	// Otherwise, build a WHERE clause, making the values safe along the way:
	if ($year) $where .= " AND YEAR(post_date) = '" . intval($year) . "'";
	if ($monthnum) $where .= " AND MONTH(post_date) = '" . intval($monthnum) . "'";
	if ($day) $where .= " AND DAYOFMONTH(post_date) = '" . intval($day) . "'";
	if ($postname) $where .= " AND post_name = '" . $wpdb->escape($postname) . "' ";

	// Run the query to get the post ID:
	$id = intval($wpdb->get_var("SELECT ID FROM $tableposts WHERE 1 = 1 " . $where));

	return $id;
}


/* Options functions */

function get_settings($setting) {
  global $wpdb, $cache_settings, $use_cache;
	if (strstr($_SERVER['REQUEST_URI'], 'install.php')) {
		return false;
	}
	if ((empty($cache_settings)) OR (!$use_cache)) {
		$settings = get_alloptions();
		$cache_settings = $settings;
	} else {
		$settings = $cache_settings;
	}
    if (!isset($settings->$setting)) {
        return false;
    }
    else {
		return $settings->$setting;
	}
}

function get_alloptions() {
    global $tableoptions, $wpdb;
    $options = $wpdb->get_results("SELECT option_name, option_value FROM $tableoptions");
    if ($options) {
        foreach ($options as $option) {
            $all_options->{$option->option_name} = $option->option_value;
        }
    }
    return $all_options;
}

function update_option($option_name, $newvalue) {
	global $wpdb, $tableoptions;
	// No validation at the moment
	$wpdb->query("UPDATE $tableoptions SET option_value = '$newvalue' WHERE option_name = '$option_name'");
}

function add_option() {
	// Adds an option if it doesn't already exist
	global $wpdb, $tableoptions;
	// TODO
}

function get_postdata($postid) {
	global $post, $tableusers, $tablecategories, $tableposts, $tablecomments, $wpdb;

	$post = $wpdb->get_row("SELECT * FROM $tableposts WHERE ID = '$postid'");
	
	$postdata = array (
		'ID' => $post->ID, 
		'Author_ID' => $post->post_author, 
		'Date' => $post->post_date, 
		'Content' => $post->post_content, 
		'Excerpt' => $post->post_excerpt, 
		'Title' => $post->post_title, 
		'Category' => $post->post_category,
		'Lat' => $post->post_lat,
		'Lon' => $post->post_lon,
		'post_status' => $post->post_status,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'post_password' => $post->post_password,
		'to_ping' => $post->to_ping,
		'pinged' => $post->pinged,
		'post_name' => $post->post_name
	);
	return $postdata;
}

function get_postdata2($postid=0) { // less flexible, but saves DB queries
	global $post;
	$postdata = array (
		'ID' => $post->ID, 
		'Author_ID' => $post->post_author,
		'Date' => $post->post_date,
		'Content' => $post->post_content,
		'Excerpt' => $post->post_excerpt,
		'Title' => $post->post_title,
		'Category' => $post->post_category,
		'Lat' => $post->post_lat,
		'Lon' => $post->post_lon,
		'post_status' => $post->post_status,
		'comment_status' => $post->comment_status,
		'ping_status' => $post->ping_status,
		'post_password' => $post->post_password
		);
	return $postdata;
}

function get_commentdata($comment_ID,$no_cache=0,$include_unapproved=false) { // less flexible, but saves DB queries
	global $postc,$id,$commentdata,$tablecomments, $wpdb;
	if ($no_cache) {
		$query = "SELECT * FROM $tablecomments WHERE comment_ID = '$comment_ID'";
		if (false == $include_unapproved) {
		    $query .= " AND comment_approved = '1'";
		}
    		$myrow = $wpdb->get_row($query, ARRAY_A);
	} else {
		$myrow['comment_ID']=$postc->comment_ID;
		$myrow['comment_post_ID']=$postc->comment_post_ID;
		$myrow['comment_author']=$postc->comment_author;
		$myrow['comment_author_email']=$postc->comment_author_email;
		$myrow['comment_author_url']=$postc->comment_author_url;
		$myrow['comment_author_IP']=$postc->comment_author_IP;
		$myrow['comment_date']=$postc->comment_date;
		$myrow['comment_content']=$postc->comment_content;
		$myrow['comment_karma']=$postc->comment_karma;
		if (strstr($myrow['comment_content'], '<trackback />')) {
			$myrow['comment_type'] = 'trackback';
		} elseif (strstr($myrow['comment_content'], '<pingback />')) {
			$myrow['comment_type'] = 'pingback';
		} else {
			$myrow['comment_type'] = 'comment';
		}
	}
	return $myrow;
}

function get_catname($cat_ID) {
	global $tablecategories,$cache_catnames,$use_cache, $wpdb;
	if ((!$cache_catnames) || (!$use_cache)) {
        $results = $wpdb->get_results("SELECT * FROM $tablecategories") or die('Oops, couldn\'t query the db for categories.');
		foreach ($results as $post) {
			$cache_catnames[$post->cat_ID] = $post->cat_name;
		}
	}
	$cat_name = $cache_catnames[$cat_ID];
	return $cat_name;
}

function profile($user_login) {
	global $user_data;
	echo "<a href='profile.php?user=".$user_data->user_login."' onclick=\"javascript:window.open('profile.php?user=".$user_data->user_login."','Profile','toolbar=0,status=1,location=0,directories=0,menuBar=1,scrollbars=1,resizable=0,width=480,height=320,left=100,top=100'); return false;\">$user_login</a>";
}

function touch_time($edit = 1) {
	global $month, $postdata, $time_difference;
	// echo $postdata['Date'];
	if ('draft' == $postdata['post_status']) {
		$checked = 'checked="checked" ';
		$edit = false;
	} else {
		$checked = ' ';
	}

	echo '<p><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp" '.$checked.'/> <label for="timestamp">Edit timestamp</label> <a href="http://wordpress.org/docs/reference/post/#edit_timestamp" title="Help on changing the timestamp">?</a><br />';
	
	$time_adj = time() + ($time_difference * 3600);
	$jj = ($edit) ? mysql2date('d', $postdata['Date']) : date('d', $time_adj);
	$mm = ($edit) ? mysql2date('m', $postdata['Date']) : date('m', $time_adj);
	$aa = ($edit) ? mysql2date('Y', $postdata['Date']) : date('Y', $time_adj);
	$hh = ($edit) ? mysql2date('H', $postdata['Date']) : date('H', $time_adj);
	$mn = ($edit) ? mysql2date('i', $postdata['Date']) : date('i', $time_adj);
	$ss = ($edit) ? mysql2date('s', $postdata['Date']) : date('s', $time_adj);

	echo '<input type="text" name="jj" value="'.$jj.'" size="2" maxlength="2" />'."\n";
	echo "<select name=\"mm\">\n";
	for ($i=1; $i < 13; $i=$i+1) {
		echo "\t\t\t<option value=\"$i\"";
		if ($i == $mm)
		echo " selected='selected'";
		if ($i < 10) {
			$ii = "0".$i;
		} else {
			$ii = "$i";
		}
		echo ">".$month["$ii"]."</option>\n";
	} ?>
</select>
<input type="text" name="aa" value="<?php echo $aa ?>" size="4" maxlength="5" /> @ 
<input type="text" name="hh" value="<?php echo $hh ?>" size="2" maxlength="2" /> : 
<input type="text" name="mn" value="<?php echo $mn ?>" size="2" maxlength="2" /> : 
<input type="text" name="ss" value="<?php echo $ss ?>" size="2" maxlength="2" /> </p>
	<?php
}

function gzip_compression() {
	global $gzip_compressed;
		if (!$gzip_compressed) {
		$phpver = phpversion(); //start gzip compression
		if($phpver >= "4.0.4pl1") {
			if(extension_loaded("zlib")) { 
				ob_start("ob_gzhandler"); 
			}
		} else if($phpver > "4.0") {
			if(strstr($HTTP_SERVER_VARS['HTTP_ACCEPT_ENCODING'], 'gzip')) {
				if(extension_loaded("zlib")) { 
					$do_gzip_compress = TRUE; 
					ob_start(); 
					ob_implicit_flush(0); 
					header("Content-Encoding: gzip");
				}
			}
		} //end gzip compression - that piece of script courtesy of the phpBB dev team
		$gzip_compressed=1;
	}
}

function alert_error($msg) { // displays a warning box with an error message (original by KYank)
	global $$HTTP_SERVER_VARS;
	?>
	<html>
	<head>
	<script language="JavaScript">
	<!--
	alert("<?php echo $msg ?>");
	history.back();
	//-->
	</script>
	</head>
	<body>
	<!-- this is for non-JS browsers (actually we should never reach that code, but hey, just in case...) -->
	<?php echo $msg; ?><br />
	<a href="<?php echo $HTTP_SERVER_VARS["HTTP_REFERER"]; ?>">go back</a>
	</body>
	</html>
	<?php
	exit;
}

function alert_confirm($msg) { // asks a question - if the user clicks Cancel then it brings them back one page
	?>
	<script language="JavaScript">
	<!--
	if (!confirm("<?php echo $msg ?>")) {
	history.back();
	}
	//-->
	</script>
	<?php
}

function redirect_js($url,$title="...") {
	?>
	<script language="JavaScript">
	<!--
	function redirect() {
	window.location = "<?php echo $url; ?>";
	}
	setTimeout("redirect();", 100);
	//-->
	</script>
	<p>Redirecting you : <b><?php echo $title; ?></b><br />
	<br />
	If nothing happens, click <a href="<?php echo $url; ?>">here</a>.</p>
	<?php
	exit();
}

// functions to count the page generation time (from phpBB2)
// ( or just any time between timer_start() and timer_stop() )

function timer_start() {
    global $timestart;
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $timestart = $mtime;
    return true;
}

function timer_stop($display=0,$precision=3) { //if called like timer_stop(1), will echo $timetotal
    global $timestart,$timeend;
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $timeend = $mtime;
    $timetotal = $timeend-$timestart;
    if ($display)
        echo number_format($timetotal,$precision);
    return $timetotal;
}


// pings Weblogs.com
function pingWeblogs($blog_ID = 1) {
	// original function by Dries Buytaert for Drupal
	global $use_weblogsping, $blogname,$siteurl,$blogfilename;
	if ((!(($blogname=="my weblog") && ($siteurl=="http://example.com") && ($blogfilename=="wp.php"))) && (!preg_match("/localhost\//",$siteurl)) && ($use_weblogsping)) {
		$client = new xmlrpc_client("/RPC2", "rpc.weblogs.com", 80);
		$message = new xmlrpcmsg("weblogUpdates.ping", array(new xmlrpcval($blogname), new xmlrpcval($siteurl."/".$blogfilename)));
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return false;
		}
		return true;
	} else {
		return false;
	}
}

// pings Weblogs.com/rssUpdates
function pingWeblogsRss($blog_ID = 1, $rss_url) {
	global $use_weblogsrssping, $blogname, $rss_url;
	if ($blogname != 'my weblog' && $rss_url != 'http://example.com/b2rdf.php' && $use_weblogsrssping) {
		$client = new xmlrpc_client('/RPC2', 'rssrpc.weblogs.com', 80);
		$message = new xmlrpcmsg('rssUpdate', array(new xmlrpcval($blogname), new xmlrpcval($rss_url)));
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return false;
		}
		return true;
	} else {
		return false;
	}
}

// pings Caf�Log.com
function pingCafelog($cafelogID,$title='',$p='') {
	global $use_cafelogping, $blogname, $siteurl, $blogfilename;
	if ((!(($blogname=="my weblog") && ($siteurl=="http://example.com") && ($blogfilename=="wp.php"))) && (!preg_match("/localhost\//",$siteurl)) && ($use_cafelogping) && ($cafelogID != '')) {
		$client = new xmlrpc_client("/xmlrpc.php", "cafelog.tidakada.com", 80);
		$message = new xmlrpcmsg("b2.ping", array(new xmlrpcval($cafelogID), new xmlrpcval($title), new xmlrpcval($p)));
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return false;
		}
		return true;
	} else {
		return false;
	}
}

// pings Blo.gs
function pingBlogs($blog_ID="1") {
	global $use_blodotgsping, $blodotgsping_url, $use_rss, $blogname, $siteurl, $blogfilename;
	if ((!(($blogname=='my weblog') && ($siteurl=='http://example.com') && ($blogfilename=='wp.php'))) && (!preg_match('/localhost\//',$siteurl)) && ($use_blodotgsping)) {
		$url = ($blodotgsping_url == 'http://example.com') ? $siteurl.'/'.$blogfilename : $blodotgsping_url;
		$client = new xmlrpc_client('/', 'ping.blo.gs', 80);
		if ($use_rss) {
			$message = new xmlrpcmsg('weblogUpdates.extendedPing', array(new xmlrpcval($blogname), new xmlrpcval($url), new xmlrpcval($url), new xmlrpcval($siteurl.'/b2rss.xml')));
		} else {
			$message = new xmlrpcmsg('weblogUpdates.ping', array(new xmlrpcval($blogname), new xmlrpcval($url)));
		}
		$result = $client->send($message);
		if (!$result || $result->faultCode()) {
			return false;
		}
		return true;
	} else {
		return false;
	}
}


// Send a Trackback
function trackback($trackback_url, $title, $excerpt, $ID) {
	global $blogname, $wpdb, $tableposts;
	$title = urlencode(stripslashes($title));
	$excerpt = urlencode(stripslashes($excerpt));
	$blog_name = urlencode(stripslashes($blogname));
	$tb_url = $trackback_url;
	$url = urlencode(get_permalink($ID));
	$query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	$trackback_url = parse_url($trackback_url);
	$http_request  = 'POST '.$trackback_url['path']." HTTP/1.0\r\n";
	$http_request .= 'Host: '.$trackback_url['host']."\r\n";
	$http_request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
	$http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
	$http_request .= "\r\n";
	$http_request .= $query_string;
	$fs = @fsockopen($trackback_url['host'], 80);
	@fputs($fs, $http_request);
/*
	$debug_file = 'trackback.log';
	$fp = fopen($debug_file, 'a');
	fwrite($fp, "\n*****\nRequest:\n\n$http_request\n\nResponse:\n\n");
	while(!@feof($fs)) {
		fwrite($fp, @fgets($fs, 4096));
	}
	fwrite($fp, "\n\n");
	fclose($fp);
*/
	@fclose($fs);

	$wpdb->query("UPDATE $tableposts SET pinged = CONCAT(pinged, '\n', '$tb_url') WHERE ID = '$ID'");
	$wpdb->query("UPDATE $tableposts SET to_ping = REPLACE(to_ping, '$tb_url', '') WHERE ID = '$ID'");
	return $result;
}

// trackback - reply
function trackback_response($error = 0, $error_message = '') {
	if ($error) {
		echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
		echo "<response>\n";
		echo "<error>1</error>\n";
		echo "<message>$error_message</message>\n";
		echo "</response>";
	} else {
		echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
		echo "<response>\n";
		echo "<error>0</error>\n";
		echo "</response>";
	}
	die();
}

function make_url_footnote($content) {
	global $siteurl;
	preg_match_all('/<a(.+?)href=\"(.+?)\"(.*?)>(.+?)<\/a>/', $content, $matches);
	$j = 0;
	for ($i=0; $i<count($matches[0]); $i++) {
		$links_summary = (!$j) ? "\n" : $links_summary;
		$j++;
		$link_match = $matches[0][$i];
		$link_number = '['.($i+1).']';
		$link_url = $matches[2][$i];
		$link_text = $matches[4][$i];
		$content = str_replace($link_match, $link_text.' '.$link_number, $content);
		$link_url = (strtolower(substr($link_url,0,7)) != 'http://') ? $siteurl.$link_url : $link_url;
		$links_summary .= "\n".$link_number.' '.$link_url;
	}
	$content = strip_tags($content);
	$content .= $links_summary;
	return $content;
}


function xmlrpc_getposttitle($content) {
	global $post_default_title;
	if (preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle)) {
		$post_title = $matchtitle[0];
		$post_title = preg_replace('/<title>/si', '', $post_title);
		$post_title = preg_replace('/<\/title>/si', '', $post_title);
	} else {
		$post_title = $post_default_title;
	}
	return $post_title;
}
	
function xmlrpc_getpostcategory($content) {
	global $post_default_category;
	if (preg_match('/<category>(.+?)<\/category>/is', $content, $matchcat)) {
		$post_category = $matchcat[0];
		$post_category = preg_replace('/<category>/si', '', $post_category);
		$post_category = preg_replace('/<\/category>/si', '', $post_category);

	} else {
		$post_category = $post_default_category;
	}
	return $post_category;
}

function xmlrpc_removepostdata($content) {
	$content = preg_replace('/<title>(.+?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.+?)<\/category>/si', '', $content);
	$content = trim($content);
	return $content;
}

function debug_fopen($filename, $mode) {
	global $debug;
	if ($debug == 1) {
		$fp = fopen($filename, $mode);
		return $fp;
	} else {
		return false;
	}
}

function debug_fwrite($fp, $string) {
	global $debug;
	if ($debug == 1) {
		fwrite($fp, $string);
	}
}

function debug_fclose($fp) {
	global $debug;
	if ($debug == 1) {
		fclose($fp);
	}
}

function pingback($content, $post_ID) {
	// original code by Mort (http://mort.mine.nu:8080)
	global $siteurl, $blogfilename, $wp_version;
	$log = debug_fopen('./pingback.log', 'a');
	$post_links = array();
	debug_fwrite($log, 'BEGIN '.time()."\n");

	// Variables
	$ltrs = '\w';
	$gunk = '/#~:.?+=&%@!\-';
	$punc = '.:?\-';
	$any = $ltrs.$gunk.$punc;
	$pingback_str_dquote = 'rel="pingback"';
	$pingback_str_squote = 'rel=\'pingback\'';
	$x_pingback_str = 'x-pingback: ';
	$pingback_href_original_pos = 27;

	// Step 1
	// Parsing the post, external links (if any) are stored in the $post_links array
	// This regexp comes straight from phpfreaks.com
	// http://www.phpfreaks.com/quickcode/Extract_All_URLs_on_a_Page/15.php
	preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);

	// Debug
	debug_fwrite($log, 'Post contents:');
	debug_fwrite($log, $content."\n");
	
	// Step 2.
	// Walking thru the links array
	// first we get rid of links pointing to sites, not to specific files
	// Example:
	// http://dummy-weblog.org
	// http://dummy-weblog.org/
	// http://dummy-weblog.org/post.php
	// We don't wanna ping first and second types, even if they have a valid <link/>

	foreach($post_links_temp[0] as $link_test){
		$test = parse_url($link_test);
		if (isset($test['query'])) {
			$post_links[] = $link_test;
		} elseif(($test['path'] != '/') && ($test['path'] != '')) {
			$post_links[] = $link_test;
		}
	}

	foreach ($post_links as $pagelinkedto){
		debug_fwrite($log, 'Processing -- '.$pagelinkedto."\n\n");

		$bits = parse_url($pagelinkedto);
		if (!isset($bits['host'])) {
			debug_fwrite($log, 'Couldn\'t find a hostname for '.$pagelinkedto."\n\n");
			continue;
		}
		$host = $bits['host'];
		$path = isset($bits['path']) ? $bits['path'] : '';
		if (isset($bits['query'])) {
			$path .= '?'.$bits['query'];
		}
		if (!$path) {
			$path = '/';
		}
		$port = isset($bits['port']) ? $bits['port'] : 80;

		// Try to connect to the server at $host
		$fp = fsockopen($host, $port, $errno, $errstr, 30);
		if (!$fp) {
			debug_fwrite($log, 'Couldn\'t open a connection to '.$host."\n\n");
			continue;
		}

		// Send the GET request
		$request = "GET $path HTTP/1.1\r\nHost: $host\r\nUser-Agent: WordPress/$wp_version PHP/" . phpversion() . "\r\n\r\n";
		ob_end_flush();
		fputs($fp, $request);

		// Start receiving headers and content
		$contents = '';
		$headers = '';
		$gettingHeaders = true;
		$found_pingback_server = 0;
		while (!feof($fp)) {
			$line = fgets($fp, 4096);
			if (trim($line) == '') {
				$gettingHeaders = false;
			}
			if (!$gettingHeaders) {
				$contents .= trim($line)."\n";
				$pingback_link_offset_dquote = strpos($contents, $pingback_str_dquote);
				$pingback_link_offset_squote = strpos($contents, $pingback_str_squote);
			} else {
				$headers .= trim($line)."\n";
				$x_pingback_header_offset = strpos(strtolower($headers), $x_pingback_str);
			}
			if ($x_pingback_header_offset) {
				preg_match('#x-pingback: (.+)#is', $headers, $matches);
				$pingback_server_url = trim($matches[1]);
				debug_fwrite($log, "Pingback server found from X-Pingback header @ $pingback_server_url\n");
				$found_pingback_server = 1;
				break;
			}
			if ($pingback_link_offset_dquote || $pingback_link_offset_squote) {
				$quote = ($pingback_link_offset_dquote) ? '"' : '\'';
				$pingback_link_offset = ($quote=='"') ? $pingback_link_offset_dquote : $pingback_link_offset_squote;
				$pingback_href_pos = @strpos($contents, 'href=', $pingback_link_offset);
				$pingback_href_start = $pingback_href_pos+6;
				$pingback_href_end = @strpos($contents, $quote, $pingback_href_start);
				$pingback_server_url_len = $pingback_href_end-$pingback_href_start;
				$pingback_server_url = substr($contents, $pingback_href_start, $pingback_server_url_len);
				debug_fwrite($log, "Pingback server found from Pingback <link /> tag @ $pingback_server_url\n");
				$found_pingback_server = 1;
				break;
			}
		}

		if (!$found_pingback_server) {
			debug_fwrite($log, "Pingback server not found\n\n*************************\n\n");
			@fclose($fp);
		} else {
			debug_fwrite($log,"\n\nPingback server data\n");

			// Assuming there's a "http://" bit, let's get rid of it
			$host_clear = substr($pingback_server_url, 7);

			//  the trailing slash marks the end of the server name
			$host_end = strpos($host_clear, '/');

			// Another clear cut
			$host_len = $host_end-$host_start;
			$host = substr($host_clear, 0, $host_len);
			debug_fwrite($log, 'host: '.$host."\n");

			// If we got the server name right, the rest of the string is the server path
			$path = substr($host_clear,$host_end);
			debug_fwrite($log, 'path: '.$path."\n\n");

			 // Now, the RPC call
			$method = 'pingback.ping';
			debug_fwrite($log, 'Page Linked To: '.$pagelinkedto."\n");
			debug_fwrite($log, 'Page Linked From: ');
			$pagelinkedfrom = get_permalink($post_ID);
			debug_fwrite($log, $pagelinkedfrom."\n");

			$client = new xmlrpc_client($path, $host, 80);
			$message = new xmlrpcmsg($method, array(new xmlrpcval($pagelinkedfrom), new xmlrpcval($pagelinkedto)));
			$result = $client->send($message);
			if ($result){
				if (!$result->value()){
					debug_fwrite($log, $result->faultCode().' -- '.$result->faultString());
				} else {
					$value = xmlrpc_decode($result->value());
					if (is_array($value)) {
						$value_arr = '';
						foreach($value as $blah) {
							$value_arr .= $blah.' |||| ';
						}
						debug_fwrite($log, $value_arr);
					} else {
						debug_fwrite($log, $value);
					}
				}
			}
			@fclose($fp);
		}
	}

	debug_fwrite($log, "\nEND: ".time()."\n****************************\n\r");
	debug_fclose($log);
}

function doGeoUrlHeader($posts) {
    global $use_default_geourl,$default_geourl_lat,$default_geourl_lon;
    if (count($posts) == 1) {
        // there's only one result  see if it has a geo code
        $row = $posts[0];
        $lat = $row->post_lat;
        $lon = $row->post_lon;
        $title = $row->post_title;
        if(($lon != null) && ($lat != null) ) {
            echo "<meta name=\"ICBM\" content=\"".$lat.", ".$lon."\" />\n";
            echo "<meta name=\"DC.title\" content=\"".convert_chars(strip_tags(get_bloginfo("name")),"unicode")." - ".$title."\" />\n";
            echo "<meta name=\"geo.position\" content=\"".$lat.";".$lon."\" />\n";
            return;
        }
    } else {
        if($use_default_geourl) {
            // send the default here 
            echo "<meta name=\"ICBM\" content=\"".$default_geourl_lat.", ".$default_geourl_lon."\" />\n";
            echo "<meta name=\"DC.title\" content=\"".convert_chars(strip_tags(get_bloginfo("name")),"unicode")."\" />\n";
            echo "<meta name=\"geo.position\" content=\"".$default_geourl_lat.";".$default_geourl_lon."\" />\n";
        }
    }
}

function getRemoteFile($host,$path) {
    $fp = fsockopen($host, 80, $errno, $errstr);
    if ($fp) {
        fputs($fp,"GET $path HTTP/1.0\r\nHost: $host\r\n\r\n");
        while ($line = fgets($fp, 4096)) {
            $lines[] = $line;
        }
        fclose($fp);
        return $lines;
    } else {
        return false;
    }
}

function pingGeoURL($blog_ID) {
    global $blodotgsping_url;

    $ourUrl = $blodotgsping_url."/index.php?p=".$blog_ID;
    $host="geourl.org";
    $path="/ping/?p=".$ourUrl;
    getRemoteFile($host,$path); 
}

/* wp_set_comment_status:
   part of otaku42's comment moderation hack
   changes the status of a comment according to $comment_status.
   allowed values:
   hold   : set comment_approve field to 0
   approve: set comment_approve field to 1
   delete : remove comment out of database
   
   returns true if change could be applied
   returns false on database error or invalid value for $comment_status
 */
function wp_set_comment_status($comment_id, $comment_status) {
    global $wpdb, $tablecomments;

    switch($comment_status) {
		case 'hold':
			$query = "UPDATE $tablecomments SET comment_approved='0' WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		case 'approve':
			$query = "UPDATE $tablecomments SET comment_approved='1' WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		case 'delete':
			$query = "DELETE FROM $tablecomments WHERE comment_ID='$comment_id' LIMIT 1";
		break;
		default:
			return false;
    }
    
    if ($wpdb->query($query)) {
		return true;
    } else {
		return false;
    }
}


/* wp_get_comment_status
   part of otaku42's comment moderation hack
   gets the current status of a comment

   returned values:
   "approved"  : comment has been approved
   "unapproved": comment has not been approved
   "deleted   ": comment not found in database

   a (boolean) false signals an error
 */
function wp_get_comment_status($comment_id) {
    global $wpdb, $tablecomments;
    
    $result = $wpdb->get_var("SELECT comment_approved FROM $tablecomments WHERE comment_ID='$comment_id' LIMIT 1");
    if ($result == NULL) {
        return "deleted";
    } else if ($result == "1") {
        return "approved";
    } else if ($result == "0") {
        return "unapproved";
    } else {
        return false;
    }
}

function wp_notify_postauthor($comment_id, $comment_type='comment') {
    global $wpdb, $tablecomments, $tableposts, $tableusers;
    global $querystring_start, $querystring_equal, $querystring_separator;
    global $blogfilename, $blogname, $siteurl;
    
    $comment = $wpdb->get_row("SELECT * FROM $tablecomments WHERE comment_ID='$comment_id' LIMIT 1");
    $post = $wpdb->get_row("SELECT * FROM $tableposts WHERE ID='$comment->comment_post_ID' LIMIT 1");
    $user = $wpdb->get_row("SELECT * FROM $tableusers WHERE ID='$post->post_author' LIMIT 1");

    if ('' == $user->user_email) return false; // If there's no email to send the comment to

	$comment_author_domain = gethostbyaddr($comment->comment_author_IP);

	$blogname = stripslashes($blogname);
	
	if ('comment' == $comment_type) {
		$notify_message  = "New comment on your post #$comment->comment_post_ID \"".stripslashes($post->post_title)."\"\r\n\r\n";
		$notify_message .= "Author : $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
		$notify_message .= "E-mail : $comment->comment_author_email\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=$comment->comment_author_IP\r\n";
		$notify_message .= "Comment:\r\n".stripslashes($comment->comment_content)."\r\n\r\n";
		$notify_message .= "You can see all comments on this post here: \r\n";
		$subject = '[' . $blogname . '] Comment: "' .stripslashes($post->post_title).'"';
	} elseif ('trackback' == $comment_type) {
		$notify_message  = "New trackback on your post #$comment_post_ID \"".stripslashes($post->post_title)."\"\r\n\r\n";
		$notify_message .= "Website: $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Excerpt: \n".stripslashes($comment->comment_content)."\r\n\r\n";
		$notify_message .= "You can see all trackbacks on this post here: \r\n";
		$subject = '[' . $blogname . '] Trackback: "' .stripslashes($post->post_title).'"';
	} elseif ('pingback' == $comment_type) {
		$notify_message  = "New pingback on your post #$comment_post_ID \"".stripslashes($post->post_title)."\"\r\n\r\n";
		$notify_message .= "Website: $comment->comment_author\r\n";
		$notify_message .= "URI    : $comment->comment_author_url\r\n";
		$notify_message .= "Excerpt: \n[...] $original_context [...]\r\n\r\n";
		$notify_message .= "You can see all pingbacks on this post here: \r\n";
		$subject = '[' . $blogname . '] Pingback: "' .stripslashes($post->post_title).'"';
	}
	$notify_message .= get_permalink($comment->comment_post_ID) . '#comments';

	if ('' == $comment->comment_author_email || '' == $comment->comment_author) {
		$from = "From: \"$blogname\" <wordpress@" . $HTTP_SERVER_VARS['SERVER_NAME'] . '>';
	} else {
		$from = 'From: "' . stripslashes($comment->comment_author) . "\" <$comment->comment_author_email>";
	}

	@mail($user->user_email, $subject, $notify_message, $from);
   
    return true;
}

/* wp_notify_moderator
   notifies the moderator of the blog (usually the admin)
   about a new comment that waits for approval
   always returns true
 */
function wp_notify_moderator($comment_id) {
    global $wpdb, $tablecomments, $tableposts, $tableusers;
    global $querystring_start, $querystring_equal, $querystring_separator;
    global $blogfilename, $blogname, $siteurl;
    
    $comment = $wpdb->get_row("SELECT * FROM $tablecomments WHERE comment_ID='$comment_id' LIMIT 1");
    $post = $wpdb->get_row("SELECT * FROM $tableposts WHERE ID='$comment->comment_post_ID' LIMIT 1");
    $user = $wpdb->get_row("SELECT * FROM $tableusers WHERE ID='$post->post_author' LIMIT 1");

    $comment_author_domain = gethostbyaddr($comment->comment_author_IP);
    $comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $tablecomments WHERE comment_approved = '0'");

    $notify_message  = "A new comment on the post #$comment->comment_post_ID \"".stripslashes($post->post_title)."\" is waiting for your approval\r\n\r\n";
    $notify_message .= "Author : $comment->comment_author (IP: $comment->comment_author_IP , $comment_author_domain)\r\n";
    $notify_message .= "E-mail : $comment->comment_author_email\r\n";
    $notify_message .= "URL    : $comment->comment_author_url\r\n";
    $notify_message .= "Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=$comment->comment_author_IP\r\n";
    $notify_message .= "Comment:\r\n".stripslashes($comment->comment_content)."\r\n\r\n";
    $notify_message .= "To approve this comment, visit: $siteurl/wp-admin/post.php?action=mailapprovecomment&p=".$comment->comment_post_ID."&comment=$comment_id\r\n";
    $notify_message .= "To delete this comment, visit: $siteurl/wp-admin/post.php?action=confirmdeletecomment&p=".$comment->comment_post_ID."&comment=$comment_id\r\n";
    $notify_message .= "Currently $comments_waiting comments are waiting for approval. Please visit the moderation panel:\r\n";
    $notify_message .= "$siteurl/wp-admin/moderation.php\r\n";

    $subject = '[' . stripslashes($blogname) . '] Please approve: "' .stripslashes($post->post_title).'"';
    $admin_email = get_settings("admin_email");
    $from  = "From: $admin_email";

    @mail($admin_email, $subject, $notify_message, $from);
    
    return true;
}


// implementation of in_array that also should work on PHP3
if (!function_exists('in_array')) {

	function in_array($needle, $haystack) {
	    $needle = strtolower($needle);
	    
	    for ($i = 0; $i < count($haystack); $i++) {
		if (strtolower($haystack[$i]) == $needle) {
		    return true;
		}
	    }
	
	    return false;
	}
}

function start_wp() {
	global $post, $id, $postdata, $authordata, $day, $preview, $page, $pages, $multipage, $more, $numpages;
	global $preview_userid,$preview_date,$preview_content,$preview_title,$preview_category,$preview_notify,$preview_make_clickable,$preview_autobr;
	global $pagenow;
	global $HTTP_GET_VARS;
	if (!$preview) {
		$id = $post->ID;
	} else {
		$id = 0;
		$postdata = array (
			'ID' => 0,
			'Author_ID' => $HTTP_GET_VARS['preview_userid'],
			'Date' => $HTTP_GET_VARS['preview_date'],
			'Content' => $HTTP_GET_VARS['preview_content'],
			'Excerpt' => $HTTP_GET_VARS['preview_excerpt'],
			'Title' => $HTTP_GET_VARS['preview_title'],
			'Category' => $HTTP_GET_VARS['preview_category'],
			'Notify' => 1
			);
	}
	$authordata = get_userdata($post->post_author);
	$day = mysql2date('d.m.y', $post->post_date);
	$currentmonth = mysql2date('m', $post->post_date);
	$numpages = 1;
	if (!$page)
		$page = 1;
	if (isset($p))
		$more = 1;
	$content = $post->post_content;
	if (preg_match('/<!--nextpage-->/', $post->post_content)) {
		if ($page > 1)
			$more = 1;
		$multipage = 1;
		$content = stripslashes($post->post_content);
		$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
		$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
		$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
		$pages = explode('<!--nextpage-->', $content);
		$numpages = count($pages);
	} else {
		$pages[0] = stripslashes($post->post_content);
		$multipage = 0;
	}
	return true;
}

function is_new_day() {
	global $day, $previousday;
	if ($day != $previousday) {
		return(1);
	} else {
		return(0);
	}
}

function apply_filters($tag, $string) {
	global $wp_filter;
	if (isset($wp_filter['all'])) {
		foreach ($wp_filter['all'] as $priority => $functions) {
			if (isset($wp_filter[$tag][$priority]))
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], $wp_filter[$tag][$priority]);
			else
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], array());
			$wp_filter[$tag][$priority] = array_unique($wp_filter[$tag][$priority]);
		}

	}
	
	if (isset($wp_filter[$tag])) {
		ksort($wp_filter[$tag]);
		foreach ($wp_filter[$tag] as $priority => $functions) {
			foreach($functions as $function) {
					$string = $function($string);
			}
		}
	}
	return $string;
}

function add_filter($tag, $function_to_add, $priority = 10) {
	global $wp_filter;
	// So the format is wp_filter['tag']['array of priorities']['array of functions']
	if (!@in_array($function_to_add, $wp_filter[$tag]["$priority"])) {
		$wp_filter[$tag]["$priority"][] = $function_to_add;
	}
	return true;
}

function remove_filter($tag, $function_to_remove, $priority = 10) {
	global $wp_filter;
	if (@in_array($function_to_remove, $wp_filter[$tag]["$priority"])) {
		foreach ($wp_filter[$tag]["$priority"] as $function) {
			if ($function_to_remove != $function) {
				$new_function_list[] = $function;
			}
		}
		$wp_filter[$tag]["$priority"] = $new_function_list;
	}
	//die(var_dump($wp_filter));
	return true;
}

/* Highlighting code c/o Ryan Boren */
function get_search_query_terms($engine = 'google') {
    global $s, $s_array;
	$referer = urldecode($_SERVER[HTTP_REFERER]);
	$query_array = array();
	switch ($engine) {
	case 'google':
		// Google query parsing code adapted from Dean Allen's
		// Google Hilite 0.3. http://textism.com
		$query_terms = preg_replace('/^.*q=([^&]+)&?.*$/i','$1', $referer);
		$query_terms = preg_replace('/\'|"/', '', $query_terms);
		$query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
		break;

	case 'lycos':
		$query_terms = preg_replace('/^.*query=([^&]+)&?.*$/i','$1', $referer);
		$query_terms = preg_replace('/\'|"/', '', $query_terms);
		$query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
		break;

	case 'yahoo':
		$query_terms = preg_replace('/^.*p=([^&]+)&?.*$/i','$1', $referer);
		$query_terms = preg_replace('/\'|"/', '', $query_terms);
		$query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
		break;

    case 'wordpress':
        // Check the search form vars if the search terms
        // aren't in the referer.
        if ( ! preg_match('/^.*s=/i', $referer)) {
            if (isset($s_array)) {
                $query_array = $s_array;
            } else if (isset($s)) {
                $query_array = array($s);
            }

            break;
        }

		$query_terms = preg_replace('/^.*s=([^&]+)&?.*$/i','$1', $referer);
		$query_terms = preg_replace('/\'|"/', '', $query_terms);
		$query_array = preg_split ("/[\s,\+\.]+/", $query_terms);
        break;
	}

	return $query_array;
}

function is_referer_search_engine($engine = 'google') {
    global $siteurl;

	$referer = urldecode($_SERVER[HTTP_REFERER]);
    //echo "referer is: $referer<br />";
	if ( ! $engine ) {
		return 0;
	}

	switch ($engine) {
	case 'google':
		if (preg_match('/^http:\/\/w?w?w?\.?google.*/i', $referer)) {
			return 1;
		}
		break;

    case 'lycos':
		if (preg_match('/^http:\/\/search\.lycos.*/i', $referer)) {
			return 1;
		}
        break;

    case 'yahoo':
		if (preg_match('/^http:\/\/search\.yahoo.*/i', $referer)) {
			return 1;
		}
        break;

    case 'wordpress':
        if (preg_match("#^$siteurl#i", $referer)) {
            return 1;
        }
        break;
	}

	return 0;
}

function hilite($text) {
	$search_engines = array('wordpress', 'google', 'lycos', 'yahoo');

	foreach ($search_engines as $engine) {
		if ( is_referer_search_engine($engine)) {
			$query_terms = get_search_query_terms($engine);
			foreach ($query_terms as $term) {
				if (!empty($term) && $term != ' ') {
					if (!preg_match('/<.+>/',$text)) {
						$text = preg_replace('/(\b'.$term.'\b)/i','<span class="hilite">$1</span>',$text);
					} else {
						$text = preg_replace('/(?<=>)([^<]+)?(\b'.$term.'\b)/i','$1<span class="hilite">$2</span>',$text);
					}
				}
			}
			break;
		}
	}

	return $text;
}

?>