<?php
/*
 * Ripple functions file
 * include this to include the world
 */
// Include config file and db class
$df = dirname(__FILE__);
// Composer
require_once $df.'/../vendor/autoload.php';
require_once $df.'/config.php';
require_once $df.'/db.php';
require_once $df.'/password_compat.php';
require_once $df.'/Do.php';
require_once $df.'/Print.php';
require_once $df.'/RememberCookieHandler.php';
require_once $df.'/PrivilegesEnum.php';
// Helpers
require_once $df.'/helpers/PasswordHelper.php';
require_once $df.'/helpers/URL.php';
// Controller system v2
require_once $df.'/pages/Login.php';
$pages = [
	new Login(),
];
// Set timezone to UTC
date_default_timezone_set('America/Toronto');
// Connect to MySQL Database
$GLOBALS['db'] = new DBPDO();
$GLOBALS["s3"] = new Aws\S3\S3Client([
	'region' => $S3Config['region'],
	'endpoint' => $S3Config['endpoint_url'],
	'credentials' => [
		'key' => $S3Config['access_key_id'],
		'secret' => $S3Config['secret_access_key'],
	],
]);
// Birthday
global $isBday;
$isBday = date("dm") == "1003";
/****************************************
 **			GENERAL FUNCTIONS 		   **
 ****************************************/
function redisConnect() {
	if (!isset($_GLOBALS["redis"])) {
		$GLOBALS["redis"] = new Predis\Client();
	}
}


/*
 * redirect
 * Redirects to a URL.
 *
 * @param (string) ($url) Destination URL.
 */
function redirect($url) {
	header('Location: '.$url);
	session_commit();
	exit();
}


/*
 * outputVariable
 * Output $v variable to $fn file
 * Only for debugging purposes
 *
 * @param (string) ($fn) Output file name
 * @param ($v) Variable to output
 */
function outputVariable($v, $fn = "/tmp/ripple.txt") {
	file_put_contents($fn, var_export($v, true), FILE_APPEND);
}


/*
 * randomString
 * Generate a random string.
 * Used to get screenshot id in osu-screenshot.php
 *
 * @param (int) ($l) Length of the generated string
 * @return (string) Generated string
 */
function randomString($l, $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
	$res = '';
	srand((float) microtime() * 1000000);
	for ($i = 0; $i < $l; $i++) {
		$res .= $c[rand() % strlen($c)];
	}
	return $res;
}
function getIP() {
	global $ipEnv;
	return getenv($ipEnv); // Add getenv('HTTP_FORWARDED_FOR')?: before getenv if you are using a dumb proxy. Meaning that if you try to get the user's IP with REMOTE_ADDR, it returns 127.0.0.1 or keeps saying the same IP, always.
	// NEVER add getenv('HTTP_FORWARDED_FOR') if you're not behind a proxy.
	// It can easily be spoofed.

}


/****************************************
 **		HTML/PAGES   FUNCTIONS 		   **
 ****************************************/
/*
 * setTitle
 * sets the title of the current $p page.
 *
 * @param (int) ($p) page ID.
 */
function setTitle($p) {
	$namesRipple = [
		1 =>   'Custom osu! server',
	];
	$namesRAP = [
		99 =>  'You\'ve been tracked',
		100 => 'Dashboard',
		101 => 'System settings',
		102 => 'Users',
		103 => 'Edit user',
		104 => 'Change identity',
		108 => 'Badges',
		109 => 'Edit Badge',
		110 => 'Edit user badges',
		111 => 'Bancho settings',
		116 => 'Admin Logs',
		117 => 'Rank requests',
		118 => 'Privilege Groups',
		119 => 'Edit privilege group',
		120 => 'View users in privilege group',
		121 => 'Give Supporter',
		122 => 'Rollback user',
		123 => 'Wipe user',
		124 => 'Rank beatmap',
		125 => 'Rank beatmap manually',
		126 => 'Reports',
		127 => 'View report',
		135 => 'Search users by IP',
		136 => 'Search users by IP - Results',
		137 => '(Un)restrict user',
		139 => '(Un)ban user',
	];
	if (isset($namesRipple[$p])) {
		return __maketitle('Akatsuki', $namesRipple[$p]);
	} else if (isset($namesRAP[$p])) {
		return __maketitle('Akatsuki Admin', $namesRAP[$p]);
	} else {
		return __maketitle('Akatsuki', '404');
	}
}
function __maketitle($b1, $b2) {
	return "<title>$b1 - $b2</title>";
}


/*
 * printPage
 * Prints the content of a page.
 * For protected pages (logged in only pages), call first sessionCheck() and then print the page.
 * For guest pages (logged out only pages), call first checkLoggedIn() and if false print the page.
 *
 * @param (int) ($p) page ID.
 */
function printPage($p) {
	$exceptions = ['pls goshuujin-sama do not hackerino &gt;////&lt;', 'Only administrators are allowed to see that documentation file.', "<div style='font-size: 40pt;'>ATTEMPTED USER ACCOUNT VIOLATION DETECTED</div>
			<p>We detected an attempt to violate an user account. If you didn't do this on purpose, you can ignore this message and login into your account normally. However if you changed your cookies on purpose and you were trying to access another user's account, don't do that.</p>
			<p>By the way, the attacked user is aware that you tried to get access to their account, and we removed all permanent login hashes. We wish you good luck in even finding the new 's' cookie for that user.</p>
			<p>Don't even try.</p>", 9001 => "don't even try"];
	// Standard page
	switch ($p) {
			// Error page

		case 99:
			if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
				$e = $_GET['e'];
			} elseif (isset($_GET['e']) && strlen($_GET['e']) > 12 && substr($_GET['e'], 0, 12) == 'do_missing__') {
				$s = substr($_GET['e'], 12);
				if (preg_match('/^[a-z0-9-]*$/i', $s) === 1) {
					P::ExceptionMessage('Missing parameter while trying to do action: '.$s);
					$e = -1;
				} else {
					$e = '9001';
				}
			} else {
				$e = '9001';
			}
			if ($e != -1) {
				P::ExceptionMessage($exceptions[$e]);
			}
		break;
			// Home

		case 1:
			P::HomePage();
		break;
			// Admin panel (> 100 pages are admin ones)
		case 100:
			sessionCheckAdmin();
			P::AdminDashboard();
		break;
			// Admin panel - System settings

		case 101:
			sessionCheckAdmin(Privileges::AdminManageSettings);
			P::AdminSystemSettings();
		break;
			// Admin panel - Users

		case 102:
			sessionCheckAdmin(Privileges::AdminManageUsers);
			P::AdminUsers();
		break;
			// Admin panel - Edit user

		case 103:
			sessionCheckAdmin(Privileges::AdminManageUsers);
			P::AdminEditUser();
		break;
			// Admin panel - Change identity

		case 104:
			sessionCheckAdmin(Privileges::AdminManageUsers);
			P::AdminChangeIdentity();
		break;
			// Admin panel - Badges

		case 108:
			sessionCheckAdmin(Privileges::AdminManageBadges);
			P::AdminBadges();
		break;
			// Admin panel - Edit badge

		case 109:
			sessionCheckAdmin(Privileges::AdminManageBadges);
			P::AdminEditBadge();
		break;
			// Admin panel - Edit uesr badges

		case 110:
			sessionCheckAdmin(Privileges::AdminManageUsers);
			P::AdminEditUserBadges();
		break;
			// Admin panel - System settings

		case 111:
			sessionCheckAdmin(Privileges::AdminManageSettings);
			P::AdminBanchoSettings();
		break;

		// Admin panel - Admin logs
		case 116:
			sessionCheckAdmin(Privileges::AdminViewRAPLogs);
			P::AdminLog();
		break;

		// Admin panel - Beatmap rank requests
		case 117:
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			P::AdminRankRequests();
		break;

		// Admin panel - Privileges Groups
		case 118:
			sessionCheckAdmin(Privileges::AdminManagePrivileges);
			P::AdminPrivilegesGroupsMain();
		break;

		// Admin panel - Privileges Groups
		case 119:
			sessionCheckAdmin(Privileges::AdminCaker); // edit needs dev
			P::AdminEditPrivilegesGroups();
		break;

		// Admin panel - Show users in group
		case 120:
			sessionCheckAdmin(Privileges::AdminManagePrivileges);
			P::AdminShowUsersInPrivilegeGroup();
		break;

		// Admin panel - Give donor to user
		case 121:
			sessionCheckAdmin(Privileges::AdminCaker);
			P::AdminGiveDonor();
		break;

		// Admin panel - Rollback User (Regular)
		case 122:
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			P::AdminRollback();
		break;

		// Admin panel - Wipe User (Regular)
		case 123:
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			P::AdminWipe();
		break;

		// Admin panel - Rank beatmap
		case 124:
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			P::AdminRankBeatmap();
		break;

		// Admin panel - Rank beatmap manually
		case 125:
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			P::AdminRankBeatmapManually();
		break;

		// Admin panel - Reports
		case 126:
			sessionCheckAdmin(Privileges::AdminManageReports);
			P::AdminViewReports();
		break;

		// Admin panel - View report
		case 127:
			sessionCheckAdmin(Privileges::AdminManageReports);
			P::AdminViewReport();
		break;

		// Admin panel - Search users by IP
		case 135:
			sessionCheckAdmin(Privileges::AdminManagePrivileges); // pre-2020-12-27: AdminManageUsers
			P::AdminSearchUserByIP();
		break;

		// Admin panel - Search users by IP - Results
		case 136:
			sessionCheckAdmin(Privileges::AdminManagePrivileges); // pre-2020-12-27: AdminManageUsers
			P::AdminSearchUserByIPResults();
		break;

		// Admin panel - (Un)restrict user
		case 137:
			sessionCheckAdmin(Privileges::AdminBanUsers);
			P::AdminRestrictUnrestrictReason();
		break;

		// Show top plays from last 1w
		case 138:
			sessionCheckAdmin(); // TODO: AdminScorewatch?
			P::AdminRecentTopPlays();
		break;

		// Admin panel - (Un)ban user
		case 139:
			sessionCheckAdmin(Privileges::AdminBanUsers);
			P::AdminBanUnbanReason();
		break;

		// 404 page
		default:
			define('NotFound', '<br><h1>404</h1><p>Page not found. Meh.</p>');
			if ($p < 100)
				echo NotFound;
			else {
					echo '
	<div class="container">
		<div class="row">
			<div class="col-lg-12 text-center">
				<div id="content">
				' . NotFound . '
				</div>
			</div>
		</div>
	</div>';
			}
		break;
	}
}


/*
 * printNavbar
 * Prints the navbar.
 * To print tabs only for guests (not logged in), do
 *	if (!checkLoggedIn()) echo('stuff');
 *
 * To print tabs only for logged in users, do
 *	if (checkLoggedIn()) echo('stuff');
 *
 * To print tabs for both guests and logged in users, do
 *	echo('stuff');
 */
function printNavbar() {
	echo '<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
				<div class="container">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>';
						if (isset($_GET['p']) && $_GET['p'] >= 100) {
						echo '<button type="button" class="navbar-toggle with-icon" data-toggle="collapse" data-target="#sidebar-wrapper">
								<span class="glyphicon glyphicon-briefcase">
							</button>';
						}
						global $isBday;
						echo $isBday ? '<a class="navbar-brand" href="index.php"><i class="fa fa-birthday-cake"></i><img src="images/logos/text.png" style="display: inline; padding-left: 10px;"></a>' : '<a class="navbar-brand" href="index.php"><img src="images/logos/text.png"></a>';
					echo '</div>
					<div class="navbar-collapse collapse">';
	// Left elements
	// Not logged left elements
	echo '<ul class="nav navbar-nav navbar-left">';
	if (!checkLoggedIn()) {
		echo '<li><a href="index.php?p=2"><i class="fa fa-sign-in"></i>	Login</a></li>';
	}
	// Logged in left elements
	if (checkLoggedIn()) {
		// Just an easter egg that you'll probably never notice, unless you do it on purpose.
		if (hasPrivilege(Privileges::AdminAccessRAP)) {
			echo '<li><a href="index.php?p=100"><i class="fa fa-cog"></i>	<b>Admin Panel</b></a></li>';
		}
	}
	// Right elements
	echo '</ul><ul class="nav navbar-nav navbar-right">';
	// Logged in right elements
	if (checkLoggedIn()) {
		global $URL;
		echo '<li class="dropdown">
					<a data-toggle="dropdown"><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="22" width="22" />	<b>'.$_SESSION['username'].'</b><span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li class="dropdown-submenu"><a href="index.php?p=103&id='.getUserID($_SESSION['username']).'"><i class="fa fa-user"></i> My profile</a></li>
						<li class="dropdown-submenu"><a href="submit.php?action=logout&csrf='.csrfToken().'"><i class="fa fa-sign-out"></i>	Logout</a></li>
					</ul>
				</li>';
	}
	// Navbar end
	echo '</ul></div></div></nav>';
}


/*
 * printAdminSidebar
 * Prints the admin left sidebar
 */
function printAdminSidebar() {
	echo '<div id="sidebar-wrapper" class="collapse" aria-expanded="false">
					<ul class="sidebar-nav">
						<li class="sidebar-brand">
							<a href="#"><b>A</b>katsuki <b>A</b>dmin <b>P</b>anel</a>
						</li>
						<li><a href="index.php?p=100"><i class="fa fa-tachometer"></i>	Dashboard</a></li>';

						if (hasPrivilege(Privileges::AdminManageSettings))
							echo '<li><a href="index.php?p=101"><i class="fa fa-cog"></i>	System settings</a></li>
							<li><a href="index.php?p=111"><i class="fa fa-server"></i>	Bancho settings</a></li>';

						if (hasPrivilege(Privileges::AdminManageUsers)) {
							echo '<li><a href="index.php?p=102"><i class="fa fa-user"></i>	Users</a></li>';
						}

						if (hasPrivilege(Privileges::AdminManageReports))
							echo '<li><a href="index.php?p=126"><i class="fa fa-flag"></i>	Reports</a></li>';

						if (hasPrivilege(Privileges::AdminManagePrivileges))
							echo '<li><a href="index.php?p=118"><i class="fa fa-group"></i>	Privilege Groups</a></li>';

						if (hasPrivilege(Privileges::AdminManageBadges))
							echo '<li><a href="index.php?p=108"><i class="fa fa-certificate"></i>	Badges</a></li>';

						/*
						if (hasPrivilege(Privileges::AdminManageBeatmaps)) {
							echo '<li><a href="index.php?p=117"><i class="fa fa-music"></i>	Rank requests</a></li>';
							echo '<li><a href="index.php?p=125"><i class="fa fa-level-up"></i>	Rank beatmap manually</a></li>';
						}*/

						if (hasPrivilege(Privileges::AdminViewRAPLogs))
							echo '<li><a href="index.php?p=116"><i class="fa fa-calendar"></i>	Admin log&nbsp;&nbsp;&nbsp;<div class="label label-primary">Free botnets</div></a></li>';

						// echo '<li><a href="https://app.datadoghq.com/apm/home?env=production"><i class="fa fa-bar-chart"></i>	Service monitoring</a></li>';
						echo "</ul>
				</div>";
}


/*
 * printAdminPanel
 * Prints an admin dashboard panel, used to show
 * statistics (like total plays, beta keys left and stuff)
 *
 * @c (string) panel color, you can use standard bootstrap colors or custom ones (add them in style.css)
 * @i (string) font awesome icon of that panel. Recommended doing fa-5x (Eg: fa fa-gamepad fa-5x)
 * @bt (string) big text, usually the value
 * @st (string) small text, usually the name of that stat
 */
function printAdminPanel($c, $i, $bt, $st, $tt="") {
	echo '<div class="col-lg-3 col-md-6">
			<div class="panel panel-'.$c.'">
			<div class="panel-heading">
			<div class="row">
			<div class="col-xs-3"><i class="'.$i.'"></i></div>
			<div class="col-xs-9 text-right">
				<div title="'.$tt.'" class="huge">'.$bt.'</div>
				<div>'.$st.'</div>
			</div></div></div></div></div>';
}


/*
 * getUserCountry
 * Does a call to ip.zxq.co to get the user's IP address.
 *
 * @returns (string) A 2-character string containing the user's country.
 */
function getUserCountry() {
	$ip = getIP();
	if (!$ip || $ip == '127.0.0.1') {
		return 'XX'; // Return XX if $ip isn't valid.
	}
	// otherwise, retrieve the contents from ip.zxq.co's API
	$data = get_contents_http("http://ip.vanilla.rocks/$ip/country");
	// And return the country. If it's set, that is.
	return strlen($data) == 2 ? $data : 'XX';
}
// updateUserCountry updates the user's country in the database with the country they
// are currently connecting from.
function updateUserCountry($userID) {
	$c = getUserCountry();
	if ($c == 'XX')
		return;
	$GLOBALS['db']->execute("UPDATE users SET country = ? WHERE id = ?", [$c, $userID]);
}
function countryCodeToReadable($cc) {
	require_once dirname(__FILE__).'/countryCodesReadable.php';

	return isset($c[$cc]) ? $c[$cc] : 'unknown country';
}


/*
 * getAllowedUsers()
 * Get an associative array, saying whether a user is banned or not on Ripple.
 *
 * @returns (array) see above.

function getAllowedUsers($by = 'username') {
	// get all the allowed users in Ripple
	$allowedUsersRaw = $GLOBALS['db']->fetchAll('SELECT '.$by.', allowed FROM users');
	// Future array containing all the allowed users.
	$allowedUsers = [];
	// Fill up the $allowedUsers array.
	foreach ($allowedUsersRaw as $u) {
		$allowedUsers[$u[$by]] = ($u['allowed'] == '1' ? true : false);
	}
	// Free up some space in the ram by deleting the data in $allowedUsersRaw.
	unset($allowedUsersRaw);

	return $allowedUsers;
}*/
/****************************************
 **	 LOGIN/LOGOUT/SESSION FUNCTIONS	   **
 ****************************************/
/*
 * startSessionIfNotStarted
 * Starts a session only if not started yet.
 */
function startSessionIfNotStarted() {
	if (session_status() == PHP_SESSION_NONE)
		session_start();
	if (isset($_SESSION['username']) && !isset($_SESSION['userid']))
		$_SESSION['userid'] = getUserID($_SESSION['username']);
}


/*
 * sessionCheck
 * Check if we are logged in, otherwise go to login page.
 * Used for logged-in only pages
 */
function sessionCheck() {
	try {
		// Start session
		startSessionIfNotStarted();
		// Check if we are logged in
		if (!isset($_SESSION["username"])) {
			unset($_SESSION['redirpage']);
			$_SESSION['redirpage'] = $_SERVER['REQUEST_URI'];
			throw new Exception('You are not logged in.');
		}
		// Check if we've changed our password
		if ($_SESSION['passwordChanged']) {
			// Update our session password so we don't get kicked
			$_SESSION['password'] = current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username']));
			// Reset passwordChanged
			$_SESSION['passwordChanged'] = false;
		}
		// Check if our password is still valid
		if (current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username'])) != $_SESSION['password']) {
			throw new Exception('Session expired. Please login again.');
		}
		/* Check if we aren't banned (OLD)
		if (current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username'])) == 0) {
			throw new Exception('You are banned.');
		} */
		// Ban check (NEW)
		if (!hasPrivilege(Privileges::UserNormal)) {
			throw new Exception('You are banned.');
		}
		// Set Y cookie
		setYCookie($_SESSION["userid"]);
		// Everything is ok, go on

	}
	catch(Exception $e) {
		addError($e->getMessage());
		// Destroy session if it still exists
		D::Logout();
		// Return to login page
		redirect('index.php?p=2');
	}
}


/*
 * sessionCheckAdmin
 * Check if we are logged in, and we are admin.
 * Used for admin pages (like admin cp)
 * Call this function instead of sessionCheck();
 */
function sessionCheckAdmin($privilege = -1, $e = 0) {
	sessionCheck();
	try {
		// Make sure the user can access RAP and is not banned/restricted
		if (!hasPrivilege(Privileges::AdminAccessRAP) || !hasPrivilege(Privileges::UserPublic) || !hasPrivilege(Privileges::UserNormal)) {
			throw new Exception;
		}

		if ($privilege > -1 && !hasPrivilege($privilege)) {
			throw new Exception;
		}
		return true;
	} catch (Exception $meme) {
		redirect('index.php?p=99&e='.$e);
		return false;
	}
}


/*
 * updateLatestActivity
 * Updates the latest_activity column for $u user
 *
 * @param ($u) (string) User ID
 */
function updateLatestActivity($u) {
	$GLOBALS['db']->execute('UPDATE users SET latest_activity = ? WHERE id = ?', [time(), $u]);
}


/*
 * timeDifference
 * Returns a string with difference from $t1 and $t2
 *
 * @param (int) ($t1) Current time. Usually time()
 * @param (int) ($t2) Event time.
 * @param (bool) ($ago) Output "ago" after time difference
 * @return (string) A string in "x minutes/hours/days (ago)" format
 */
function timeDifference($t1, $t2, $ago = true, $leastText = "Right Now") {
	// Calculate difference in seconds
	// abs and +1 should fix memes
	$d = abs($t1 - $t2 + 1);
	switch ($d) {
		// Right now
		default:
			return $leastText;
		break;

		// 1 year or more
		case $d >= 31556926:
			$n = round($d / 31556926);
			$i = 'year';
		break;

		// 1 month or more
		case $d >= 2629743 && $d < 31556926:
			$n = round($d / 2629743);
			$i = 'month';
		break;

		// 1 day or more
		case $d >= 86400 && $d < 2629743:
			$n = round($d / 86400);
			$i = 'day';
		break;

		// 1 hour or more
		case $d >= 3600 && $d < 86400:
			$n = round($d / 3600);
			$i = 'hour';
		break;

		// 1 minute or more
		case $d >= 60 && $d < 3600:
			$n = round($d / 60);
			$i = 'minute';
		break;
	}

	// Plural, ago and more
	$s = $n > 1 ? 's' : '';
	$a = $ago ? 'ago' : '';

	return $n.' '.$i.$s.' '.$a;
}
$checkLoggedInCache = -100;
/*
 * checkLoggedIn
 * Similar to sessionCheck(), but let the user choose what to do if logged in or not
 *
 * @return (bool) true: logged in / false: not logged in
 */
function checkLoggedIn() {
	global $checkLoggedInCache;
	// Start session
	startSessionIfNotStarted();
	if ($checkLoggedInCache !== -100) {
		return $checkLoggedInCache;
	}
	// Check if we are logged in
	if (!isset($_SESSION['userid'])) {
		$checkLoggedInCache = false;
		return false;
	}
	// Check if our password is still valid
	if ($GLOBALS['db']->fetch('SELECT password FROM users WHERE username = ?', $_SESSION['username']) == $_SESSION['password']) {
		$checkLoggedInCache = false;

		return false;
	}
	// Check if we aren't banned
	//if ($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username']) == 0) {
	if (!hasPrivilege(Privileges::UserNormal)) {
		$checkLoggedInCache = false;

		return false;
	}
	// Everything is ok, go on
	$checkLoggedInCache = true;

	return true;
}
function checkWebsiteMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkGameMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkBanchoMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
// ******** GET USER ID/USERNAME FUNCTIONS *********
$cachedID = false;
/*
 * getUserID
 * Get the user ID of the $u user
 *
 * @param (string) ($u) Username
 * @return (string) user ID of $u
 */
function getUserID($u) {
	global $cachedID;
	if (isset($cachedID[$u])) {
		return $cachedID[$u];
	}
	$ID = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $u);
	if ($ID) {
		$cachedID[$u] = current($ID);
	} else {
		// ID not set, maybe invalid player. Return 0.
		$cachedID[$u] = 0;
	}

	return $cachedID[$u];
}


/*
 * getUserUsername
 * Get the username for $uid user
 *
 * @param (int) ($uid) user ID
 * @return (string) username
 */
function getUserUsername($uid) {
	$username = $GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ? LIMIT 1', $uid);
	if ($username) {
		return current($username);
	} else {
		return 'unknown';
	}
}


/*
 * getPlaymodeText
 * Returns a text representation of a playmode integer.
 *
 * @param (int) ($playModeInt) an integer from 0 to 3 (inclusive) stating the play mode.
 * @param (bool) ($readable) set to false for returning values to be inserted into the db. set to true for having something human readable (osu!standard / Taiko...)
 */
function getPlaymodeText($playModeInt, $readable = false) {
	switch ($playModeInt) {
		case 1:
			return $readable ? 'Taiko' : 'taiko';
		break;
		case 2:
			return $readable ? 'Catch the Beat' : 'ctb';
		break;
		case 3:
			return $readable ? 'osu!mania' : 'mania';
		break;
			// Protection against memes from the users

		default:
			return $readable ? 'osu!standard' : 'std';
		break;
	}
}


/*
 * getScoreMods
 * Gets the mods for the $m mod flag
 *
 * @param (int) ($m) Mod flag
 * @returns (string) Eg: "+ HD, HR"
 */
function getScoreMods($m) {
	require_once dirname(__FILE__).'/ModsEnum.php';
	$r = '';
	if ($m & ModsEnum::NoFail) {
		$r .= 'NF, ';
	}
	if ($m & ModsEnum::Easy) {
		$r .= 'EZ, ';
	}
	if ($m & ModsEnum::NoVideo) {
		$r .= 'NV, ';
	}
	if ($m & ModsEnum::Hidden) {
		$r .= 'HD, ';
	}
	if ($m & ModsEnum::HardRock) {
		$r .= 'HR, ';
	}
	if ($m & ModsEnum::SuddenDeath) {
		$r .= 'SD, ';
	}
	if ($m & ModsEnum::DoubleTime) {
		$r .= 'DT, ';
	}
	if ($m & ModsEnum::Relax) {
		$r .= 'RX, ';
	}
	if ($m & ModsEnum::HalfTime) {
		$r .= 'HT, ';
	}
	if ($m & ModsEnum::Nightcore) {
		$r .= 'NC, ';
		// Remove DT and display only NC
		$r = str_replace('DT, ', '', $r);
	}
	if ($m & ModsEnum::Flashlight) {
		$r .= 'FL, ';
	}
	if ($m & ModsEnum::Autoplay) {
		$r .= 'AP, ';
	}
	if ($m & ModsEnum::SpunOut) {
		$r .= 'SO, ';
	}
	if ($m & ModsEnum::Relax2) {
		$r .= 'AP, ';
	}
	if ($m & ModsEnum::Perfect) {
		$r .= 'PF, ';
	}
	if ($m & ModsEnum::Key4) {
		$r .= '4K, ';
	}
	if ($m & ModsEnum::Key5) {
		$r .= '5K, ';
	}
	if ($m & ModsEnum::Key6) {
		$r .= '6K, ';
	}
	if ($m & ModsEnum::Key7) {
		$r .= '7K, ';
	}
	if ($m & ModsEnum::Key8) {
		$r .= '8K, ';
	}
	if ($m & ModsEnum::keyMod) {
		$r .= '';
	}
	if ($m & ModsEnum::FadeIn) {
		$r .= 'FD, ';
	}
	if ($m & ModsEnum::Random) {
		$r .= 'RD, ';
	}
	if ($m & ModsEnum::LastMod) {
		$r .= 'CN, ';
	}
	if ($m & ModsEnum::Key9) {
		$r .= '9K, ';
	}
	if ($m & ModsEnum::Key10) {
		$r .= '10K, ';
	}
	if ($m & ModsEnum::Key1) {
		$r .= '1K, ';
	}
	if ($m & ModsEnum::Key3) {
		$r .= '3K, ';
	}
	if ($m & ModsEnum::Key2) {
		$r .= '2K, ';
	}
	// Add "+" and remove last ", "
	if (strlen($r) > 0) {
		return '+ '.substr($r, 0, -2);
	} else {
		return '';
	}
}


/*
 * calculateAccuracy
 * Calculates the accuracy of a score in a given gamemode.
 *
 * @param int $n300 The number of 300 hits in a song.
 * @param int $n100 The number of 100 hits in a song.
 * @param int $n50 The number of 50 hits in a song.
 * @param int $ngeki The number of geki hits in a song.
 * @param int $nkatu The number of katu hits in a song.
 * @param int $nmiss The number of missed hits in a song.
 * @param int $mode The game mode.
 */
function calculateAccuracy($n300, $n100, $n50, $ngeki, $nkatu, $nmiss, $mode) {
	// For reference, see: http://osu.ppy.sh/wiki/Accuracy
	switch ($mode) {
		case 0:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $n300 * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
		case 1:
			// Please note this is not what is written on the wiki.
			// However, what was written on the wiki didn't make any sense at all.
			$totalPoints = ($n100 * 50 + $n300 * 100);
			$maxHits = ($nmiss + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 100);
		break;
		case 2:
			$fruits = $n300 + $n100 + $n50;
			$totalFruits = $fruits + $nmiss + $nkatu;
			$accuracy = $fruits / $totalFruits;
		break;
		case 3:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $nkatu * 200 + $n300 * 300 + $ngeki * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300 + $ngeki + $nkatu);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
	}

	return $accuracy * 100; // we're doing * 100 because $accuracy is like 0.9823[...]

}


/**************************
 **   OTHER   FUNCTIONS  **
 **************************/
function get_contents_http($url) {
	// If curl is not installed, attempt to use file_get_contents
	if (!function_exists('curl_init')) {
		$w = stream_get_wrappers();
		if (in_array('http', $w)) {
			return file_get_contents($url);
		}

		return;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// Download the given URL, and return output
	$output = curl_exec($ch);
	/*
				    if(curl_errno($ch))
				    {
				        echo 'error:' . curl_error($ch);
				    }*/
	// Close the cURL resource, and free system resources
	curl_close($ch);

	return $output;
}
function post_content_http($url, $content, $timeout=10) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// POST data
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// Download the given URL, and return output
	$output = curl_exec($ch);
	// Close the cURL resource, and free system resources
	curl_close($ch);

	return $output;
}


/*
 * checkUserExists
 * Check if given user exists
 *
 * @param (string) ($i) username/id
 * @param (bool) ($id) if true, search by id. Default: false
 */
function checkUserExists($u, $id = false) {
	if ($id) {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE id = ?', [$u]);
	} else {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', [$u]);
	}
}


/*
 * getFriendship
 * Check friendship between u0 and u1
 *
 * @param (int/string) ($u0) u0 id/username
 * @param (int/string) ($u1) u1 id/username
 * @param (bool) ($id) If true, u0 and u1 are ids, if false they are usernames
 * @return (int) 0: no friendship, 1: u0 friend with u1, 2: mutual
 */
function getFriendship($u0, $u1, $id = false) {
	// Get id if needed
	if (!$id) {
		$u0 = getUserID($u0);
		$u1 = getUserID($u1);
	}
	// Make sure u0 and u1 exist
	if (!checkUserExists($u0, true) || !checkUserExists($u1, true)) {
		return 0;
	}
	// If user1 is friend of user2, check for mutual.
	if ($GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?', [$u0, $u1]) !== false) {
		if ($u1 == 999 || $GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user2 = ? AND user1 = ?', [$u0, $u1]) !== false) {
			return 2;
		}

		return 1;
	}
	// Otherwise, it's just no friendship.
	return 0;
}



// I don't know what this function is for anymore
function clir($must = false, $redirTo = 'index.php?p=2&e=3') {
	if (checkLoggedIn() === $must) {
		if ($redirTo == "index.php?p=2&e=3") {
			addError('You\'re not logged in.');
			$redirTo == "index.php?p=2";
		}
		redirect($redirTo);
	}
}


/*
 * checkMustHave
 * Makes sure a request has the "Must Have"s of a page.
 * (Must Haves = $mh_GET, $mh_POST)
 */
function checkMustHave($page) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($page->mh_POST) && count($page->mh_POST) > 0) {
			foreach ($page->mh_POST as $el) {
				if (empty($_POST[$el])) {
					redirect('index.php?p=99&e=do_missing__'.$el);
				}
			}
		}
	} else {
		if (isset($page->mh_GET) && count($page->mh_GET) > 0) {
			foreach ($page->mh_GET as $el) {
				if (empty($_GET[$el])) {
					redirect('index.php?p=99&e=do_missing__'.$el);
				}
			}
		}
	}
}


/*
 * accuracy
 * Convert accuracy to string, having 2 decimal digits.
 *
 * @param (float) accuracy
 * @return (string) accuracy, formatted into a string
 */
function accuracy($acc) {
	return number_format(round($acc, 2), 2);
}
function addError($e) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['errors']) || !is_array($_SESSION['errors']))
		$_SESSION['errors'] = array();
	$_SESSION['errors'][] = $e;
}
function addSuccess($s) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['successes']) || !is_array($_SESSION['successes']))
		$_SESSION['successes'] = array();
	$_SESSION['successes'][] = $s;
}
// logIP adds the user to ip_user if they're not in it, and increases occurencies if they are
function logIP($uid) {
	// botnet-track IP
	$GLOBALS['db']->execute("INSERT INTO ip_user (userid, ip, occurencies) VALUES (?, ?, '1')
							ON DUPLICATE KEY UPDATE occurencies = occurencies + 1",
							[$uid, getIP()]);
}

function getJsonCurl($url, $timeout = 1) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$result=curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

function postJsonCurl($url, $data, $timeout = 1) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result=curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

function postWebhookMessage($message, $userID = -1) {
	$timestamp = date("c", strtotime("now"));

	if ($userID == -1) {
		$userID = $_SESSION["userid"];
	}

	$username = $_SESSION['username'];

	$data = json_encode([
		"embeds" => [
			[
				"timestamp" => $timestamp,
				"color" => hexdec("542CB8"),
				"footer" => [
					"text" => "Akatsuki Admin Panel",
				],
				"thumbnail" => [
					"url" => "https://akatsuki.pw/static/logos/logo.png"
				],
				"fields" => [
					[
						"name" => "** **",
						"value" => sprintf("[%s](https://akatsuki.pw/u/%d) %s", $username, $userID, $message),
						"inline" => true
					]
				]
			]
		]
	]);

	postJsonCurl(DISCORD_WEBHOOK_URL, $data, 5);
}

/*
 * bloodcatDirectString()
 * Return a osu!direct-like string for a specific song
 * from a bloodcat song array
 *
 * @param (array) ($arr) Bloodcat data array
 * @param (bool) ($np) If true, output chat np beatmap, otherwise output osu direct search beatmap
 * @return (string) osu!direct-like string
 */
function bloodcatDirectString($arr, $np = false) {
	$s = '';
	if ($np) {
		$s = $arr['id'].'.osz|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['id'].'|0|0|0|';
	} else {
		$s = $arr['id'].'|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['beatmaps'][0]['id'].'|0|0|0||';
		foreach ($arr['beatmaps'] as $diff) {
			$s .= $diff['name'].'@'.$diff['mode'].',';
		}
		$s = rtrim($s, ',');
		$s .= '|';
	}
	return $s;
}

function printBubble($userID, $username, $message, $time, $through) {
	echo '
	<img class="circle" src="' . URL::Avatar() . '/' . $userID . '">
	<div class="bubble">
		<b>' . $username . '</b> ' . $message . '<br>
		<span style="font-size: 80%">' . timeDifference($time, time()) .' through <i>' . $through . '</i></span>
	</div>';
}

function rapLog($message, $userID = -1, $through = "RAP") {
	if ($userID == -1)
		$userID = $_SESSION["userid"];
	$GLOBALS["db"]->execute("INSERT INTO rap_logs (id, userid, text, datetime, through) VALUES (NULL, ?, ?, ?, ?);", [$userID, $message, time(), $through]);
}

function readableRank($rank) {
	switch ($rank) {
		case 1: return "normal"; break;
		case 2: return "supporter"; break;
		case 3: return "developer"; break;
		case 4: return "community manager"; break;
		default: return "akerino"; break;
	}
}

function getUserPrivileges($userID) {
	global $cachedPrivileges;
	if (isset($cachedPrivileges[$userID])) {
		return $cachedPrivileges[$userID];
	}

	$result = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$userID]);
	if ($result) {
		$cachedPrivileges[$userID] = current($result);
	} else {
		$cachedPrivileges[$userID] = 0;
	}
	return getUserPrivileges($userID);
}

function hasPrivilege($privilege, $userID = -1) {
	if ($userID == -1)
		if (!array_key_exists("userid", $_SESSION))
			return false;
		else
			$userID = $_SESSION["userid"];
	$result = getUserPrivileges($userID) & $privilege;
	return $result > 0 ? true : false;
}

function isRestricted($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && hasPrivilege(Privileges::UserNormal, $userID));
}

function isBanned($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && !hasPrivilege(Privileges::UserNormal, $userID));
}

function getIdentityToken($userID, $generate = True) {
	$identityToken = $GLOBALS["db"]->fetch("SELECT token FROM identity_tokens WHERE userid = ? LIMIT 1", [$userID]);
	if (!$identityToken && $generate) {
		// If not, generate a new one
		do {
			$identityToken = hash("sha256", randomString(32));
			$collision = $GLOBALS["db"]->fetch("SELECT id FROM identity_tokens WHERE token = ? LIMIT 1", $identityToken);
		} while ($collision);

		// And save it in db
		$GLOBALS["db"]->execute("INSERT INTO identity_tokens (id, userid, token) VALUES (NULL, ?, ?)", [$userID, $identityToken]);
	} else if ($identityToken) {
		$identityToken = current($identityToken);
	} else {
		$identityToken = false;
	}
	return $identityToken;
}

function setYCookie($userID) {
	$identityToken = getIdentityToken($userID);
	setcookie("y", $identityToken, time()+60*60*24*30*6, "/");	// y of yee
}

function UNIXTimestampToOsuDate($unix) {
	return date("ymdHis", $unix);
}

function isOnline($uid) {
	global $URL;
	try {
		$data = getJsonCurl($URL["bancho"]."/api/v1/isOnline?id=".urlencode($uid));
		return $data["result"];
	} catch (Exception $e) {
		return false;
	}
}

function getDonorPrice($months) {
	return number_format(pow($months * 30 * 0.2, 0.70), 2, ".", "");
}

function getDonorMonths($price) {
	return round(pow($price, (1 / 0.70)) / 30 / 0.2);
}

function unsetCookie($name) {
	unset($_COOKIE[$name]);
	setcookie($name, "", time()-3600);
}

function safeUsername($name) {
	return str_replace(" ", "_", strtolower(trim($name)));
}

function updateBanBancho($userID, $ban) { // $ban is true or false, false meaning unban
	redisConnect();
	$GLOBALS["redis"]->publish('peppy:'.($ban ? 'ban' : 'unban'), $userID);
}

function updateSilenceBancho($userID) {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:silence", $userID);
}

function stripSuccessError($url) {
	$parts = parse_url($url);
	parse_str($parts['query'], $query);
	unset($query["e"]);
	unset($query["s"]);
	return $parts["path"] . "?" .  http_build_query($query);
}

function appendNotes($userID, $notes, $addNl=true, $addTimestamp=true) {
	$fullNote = "";
	if ($addNl)
		$fullNote .= "\n";
	if ($addTimestamp)
		$fullNote .= date("[Y-m-d H:i:s] ");
	$fullNote .= $notes;
	$GLOBALS["db"]->execute("UPDATE users SET notes=CONCAT(COALESCE(notes, ''), ?) WHERE id = ?", [$fullNote, $userID]);
}

function removeFromLeaderboard($userID) {
	redisConnect();
	$country = strtolower($GLOBALS["db"]->fetch("SELECT country FROM users WHERE id = ?", [$userID])["country"]);
	foreach (["std", "taiko", "ctb", "mania"] as $key => $value) {
		$GLOBALS["redis"]->zrem("ripple:leaderboard:".$value, $userID);
		if (strlen($country) > 0 && $country != "xx") {
			$GLOBALS["redis"]->zrem("ripple:leaderboard:".$value.":".$country, $userID);
		}
	}
}

function generateCsrfToken() {
	return bin2hex(openssl_random_pseudo_bytes(32));
}

function csrfToken() {
	if (!isset($_SESSION['csrf'])) {
		$_SESSION['csrf'] = generateCsrfToken();
	}
	return $_SESSION['csrf'];
}

function csrfCheck($givenToken=NULL, $regen=true) {
	if (!isset($_SESSION['csrf'])) {
		return false;
	}
	if ($givenToken === NULL) {
		if (isset($_POST['csrf'])) {
			$givenToken = $_POST['csrf'];
		} else if (isset($_GET['csrf'])) {
			$givenToken = $_GET['csrf'];
		} else {
			return false;
		}
	}
	if (empty($givenToken)) {
		return false;
	}
	$rightToken = $_SESSION['csrf'];
	if ($regen) {
		$_SESSION['csrf'] = generateCsrfToken();
	}
	return hash_equals($rightToken, $givenToken);
}

function giveDonor($userID, $months, $add=true, $premium=false) {
	$userData = $GLOBALS["db"]->fetch("SELECT username, email, donor_expire FROM users WHERE id = ? LIMIT 1", [$userID]);

	if (!$userData) {
		throw new Exception("That user doesn't exist");
	}

	$isDonor = hasPrivilege(Privileges::UserDonor, $userID);

	$username = $userData["username"];

	if (!$isDonor || !$add) {
		$start = time();
	} else {
		$start = $userData["donor_expire"];
		if ($start < time()) {
			$start = time();
		}
	}

	$unixExpire = $start + ((30 * 86400) * $months);
	$monthsExpire = round(($unixExpire - time()) / (30 * 86400));

	if ($premium) {
		$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | 8388612, donor_expire = ? WHERE id = ?", [$unixExpire, $userID]);
		$donorBadge = $GLOBALS["db"]->fetch("SELECT id FROM badges WHERE name = 'premium' LIMIT 1");
	} else {
		$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | 4, donor_expire = ? WHERE id = ?", [$unixExpire, $userID]);
		$donorBadge = $GLOBALS["db"]->fetch("SELECT id FROM badges WHERE name = 'supporter' OR name = 'support' LIMIT 1");
	}

	if (!$donorBadge) {
		throw new Exception("There's no such badge in the database.");
	}

	// Check if they already have the supporter/premium badge
	$hasAlready = $GLOBALS["db"]->fetch("SELECT id FROM user_badges WHERE user = ? AND badge = ? LIMIT 1", [$userID, $donorBadge["id"]]);

	if (!$hasAlready) { // Add their supporter/premium badge
		$GLOBALS["db"]->execute("INSERT INTO user_badges(user, badge) VALUES (?, ?)", [$userID, $donorBadge["id"]]);
	}

	// To finish off, let's give them permissions to edit their custom badge.
	$GLOBALS["db"]->execute("UPDATE users SET can_custom_badge = 1, show_custom_badge = 1 WHERE id = ?", [$userID]);

	return $monthsExpire;
}

function randomFileName($path, $suffix) {
	do {
			$randomStr = randomString(32);
			$file = $path . "/" . $randomStr . $suffix;
			$exists = file_exists($file);
	} while($exists);
	echo $file;
	return $randomStr;
}

function updateMainMenuIconBancho() {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:reload_settings", "reload");
}
