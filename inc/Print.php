<?php

class P
{
	/*
	 * AdminDashboard
	 * Prints the admin panel dashborad page
	 */
	public static function AdminDashboard()
	{
		// Get admin dashboard data
		/*
		$submittedScoresFull = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM scores LIMIT 1'));
		$submittedScores = number_format($submittedScoresFull / 1000000, 2) . "m";
		*/
		$totalScoresFullVanilla = current($GLOBALS['db']->fetch('SELECT SUM(playcount) FROM user_stats WHERE mode IN (0, 1, 2, 3)'));
		$totalScoresVanilla = number_format($totalScoresFullVanilla / 1000000, 2) . "m";

		$totalScoresFullRelax = current($GLOBALS['db']->fetch('SELECT SUM(playcount) FROM user_stats WHERE mode IN (4, 5, 6)'));
		$totalScoresRelax = number_format($totalScoresFullRelax / 1000000, 2) . "m";

		$totalScoresFullAutopilot = current($GLOBALS['db']->fetch('SELECT SUM(playcount) FROM user_stats WHERE mode = 8'));
		$totalScoresAutopilot = number_format($totalScoresFullAutopilot / 1000000, 2) . "m";

		/*$totalPPQuery = $GLOBALS['db']->fetch("SELECT SUM(pp) FROM scores WHERE completed = 3 LIMIT 1");
		$totalPP = 0;
		foreach ($totalPPQuery as $pp) {
			$totalPP += $pp;
		}
		$totalPP = number_format($totalPP);*/

		// Top scores

		$topPlaysVanilla = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores.userid, scores.time, scores.score, scores.pp, scores.play_mode, scores.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores
	INNER JOIN users ON users.id = scores.userid
	INNER JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
    	scores.completed = 3 AND
		users.privileges & 1 AND
        beatmaps.ranked = 2 AND
		scores.play_mode = 0
	ORDER BY scores.pp DESC LIMIT 20');

		$topPlaysRelax = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores_relax.userid, scores_relax.time, scores_relax.score, scores_relax.pp, scores_relax.play_mode, scores_relax.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores_relax
	INNER JOIN users ON users.id = scores_relax.userid
	INNER JOIN beatmaps ON scores_relax.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
		scores_relax.completed = 3 AND
		users.privileges & 1 AND
        beatmaps.ranked = 2 AND
		scores_relax.play_mode = 0
	ORDER BY scores_relax.pp DESC LIMIT 20');

		$topPlaysAutopilot = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores_ap.userid, scores_ap.time, scores_ap.score, scores_ap.pp, scores_ap.play_mode, scores_ap.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores_ap
	INNER JOIN users ON users.id = scores_ap.userid
	INNER JOIN beatmaps ON scores_ap.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
		scores_ap.completed = 3 AND
		users.privileges & 1 AND
		beatmaps.ranked = 2 AND
		scores_ap.play_mode = 0
	ORDER BY scores_ap.pp DESC LIMIT 20');

		// Top scores within the last 2 weeks
		//  (Used to find cheaters, usually)

		$topRecentPlaysVanilla = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores.userid, scores.time, scores.score, scores.pp, scores.play_mode, scores.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores
	INNER JOIN users ON users.id = scores.userid
	INNER JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
    	scores.completed = 3 AND
		users.privileges & 1 AND
        beatmaps.ranked = 2 AND
		scores.time > UNIX_TIMESTAMP(NOW()) - 604800 AND
		scores.play_mode = 0
	ORDER BY scores.pp DESC LIMIT 100');

		$topRecentPlaysRelax = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores_relax.userid, scores_relax.time, scores_relax.score, scores_relax.pp, scores_relax.play_mode, scores_relax.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores_relax
	INNER JOIN users ON users.id = scores_relax.userid
	INNER JOIN beatmaps ON scores_relax.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
	scores_relax.completed = 3 AND
		users.privileges & 1 AND
        beatmaps.ranked = 2 AND
		scores_relax.time > UNIX_TIMESTAMP(NOW()) - 604800
	ORDER BY scores_relax.pp DESC LIMIT 100');


		$topRecentPlaysAutopilot = $GLOBALS['db']->fetchAll('
		SELECT
		users.username, scores_ap.userid, scores_ap.time, scores_ap.score, scores_ap.pp, scores_ap.play_mode, scores_ap.mods, beatmaps.song_name, beatmaps.beatmap_id
		FROM scores_ap
	INNER JOIN users ON users.id = scores_ap.userid
	INNER JOIN beatmaps ON scores_ap.beatmap_md5 = beatmaps.beatmap_md5
	WHERE
	scores_ap.completed = 3 AND
		users.privileges & 1 AND
		beatmaps.ranked = 2 AND
		scores_ap.time > UNIX_TIMESTAMP(NOW()) - 604800
	ORDER BY scores_ap.pp DESC LIMIT 100');

		global $INTERNAL_BANCHO_SERVICE_BASE_URL;
		$onlineUsers = makeJsonWebRequest("GET", $INTERNAL_BANCHO_SERVICE_BASE_URL . "/api/v1/onlineUsers");
		if (!$onlineUsers) {
			$onlineUsers = 0;
		} else {
			$onlineUsers = $onlineUsers["result"];
		}

		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Stats panels
		echo '<div class="row">';
		//printAdminPanel('primary', 'fa fa-gamepad fa-5x', $submittedScores, 'Submitted scores', number_format($submittedScoresFull));
		printAdminPanel('red', 'fa fa-wheelchair-alt fa-5x', $totalScoresVanilla, 'Vanilla plays', number_format($totalScoresFullVanilla));
		printAdminPanel('red', 'fa fa-wheelchair-alt fa-5x', $totalScoresRelax, 'Relax plays', number_format($totalScoresFullRelax));
		printAdminPanel('red', 'fa fa-wheelchair-alt fa-5x', $totalScoresAutopilot, 'Autopilot plays', number_format($totalScoresFullAutopilot));
		printAdminPanel('green', 'fa fa-street-view fa-5x', $onlineUsers, 'Online users');
		//printAdminPanel('yellow', 'fa fa-dot-circle-o fa-5x', $totalPP, 'Total PP');
		echo '</div>';


		// Top plays table (Vanilla)

		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays (Vanilla)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topPlaysVanilla as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="warning">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		// Top plays table (Relax)
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays (Relax)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topPlaysRelax as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="warning">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';


		// Top plays table (Autopilot)
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays (Autopilot)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topPlaysAutopilot as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="warning">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		// Recent top plays table (Vanilla)
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Recent top plays (Vanilla)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topRecentPlaysVanilla as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="danger">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		// Recent top plays table (Relax)
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Recent top plays (Relax)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topRecentPlaysRelax as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="danger">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		// Recent top plays table (Autopilot)
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Recent top plays (Autopilot)</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		//echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topRecentPlaysAutopilot as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="danger">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left"><a href="https://osu.ppy.sh/beatmaps/' . $play['beatmap_id'] . '">' . $bn . '</a> <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		echo '</div>';
	}


	/*
	 * AdminUsers
	 * Prints the admin panel users page
	 */
	public static function AdminUsers()
	{
		// Get admin dashboard data
		$totalUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users'));
		$supporters = current($GLOBALS['db']->fetch('
		    SELECT COUNT(*)
			FROM users
			WHERE privileges & ' . Privileges::UserDonor . ' > 0
			AND NOT privileges & ' . Privileges::UserPremium . ' > 0
			AND NOT privileges & ' . Privileges::AdminManageUsers . ' > 0
			AND donor_expire != 2147483647'));
		$premiums = current($GLOBALS['db']->fetch('
			SELECT COUNT(*) FROM users
			WHERE privileges & ' . Privileges::UserPremium . ' > 0
			AND NOT privileges & ' . Privileges::AdminManageUsers . ' > 0
			AND donor_expire != 2147483647'));
		$bannedUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & 1 = 0'));
		/* Unused, premium used instead 4head
		$modUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & '.Privileges::AdminAccessRAP.'> 0'));
		 Multiple pages
		*/
		$pageInterval = 100;
		$from = (isset($_GET["from"])) ? $_GET["from"] : 999;
		$to = $from + $pageInterval;
		$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE id >= ? AND id < ?', [$from, $to]);
		$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Stats panels
		echo '<div class="row">';
		printAdminPanel('primary', 'fa fa-user fa-5x', $totalUsers, 'Total users');
		printAdminPanel('red', 'fa fa-thumbs-down fa-5x', $bannedUsers, 'Banned users');
		printAdminPanel('yellow', 'fa fa-money fa-5x', $supporters, 'Supporters');
		//printAdminPanel('green', 'fa fa-star fa-5x', $modUsers, 'Admins');
		printAdminPanel('info', 'fa fa-star fa-5x', $premiums, 'Premium members');
		echo '</div>';
		// Quick edit/silence/kick user button
		echo '<br><p align="center" class="mobile-flex"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickEditUserModal">Quick edit user (username)</button>';
		echo '<button type="button" class="btn btn-info" data-toggle="modal" data-target="#quickEditEmailModal">Quick edit user (email)</button>';
		if (hasPrivilege(Privileges::AdminManagePrivileges)) {
			echo '<a href="index.php?p=135" type="button" class="btn btn-warning">Search user by IP</a>';
		}
		echo '<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal">Silence user</button>';
		echo '<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#kickUserModal">Kick user from Bancho</button>';
		echo '</p>';
		// Users plays table
		echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-user"></i>	ID</th><th class="text-center">Username</th><th class="text-center">Privileges Group</th><th class="text-center">Allowed</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';
		foreach ($users as $user) {

			// Get group color/text
			$groupColor = "default";
			$groupText = "None";
			foreach ($groups as $group) {
				if ($user["privileges"] == $group["privileges"] || $user["privileges"] == ($group["privileges"] | Privileges::UserDonor)) {
					$groupColor = $group["color"];
					$groupText = $group["name"];
				}
			}

			// Get allowed color/text
			$allowedColor = "success";
			$allowedText = "Ok";
			if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Not visible and not active, banned
				$allowedColor = "danger";
				$allowedText = "Banned";
			} else if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) > 0) {
				// Not visible but active, restricted
				$allowedColor = "warning";
				$allowedText = "Restricted";
			} else if (($user["privileges"] & Privileges::UserPublic) > 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Visible but not active, disabled (not supported yet)
				$allowedColor = "default";
				$allowedText = "Locked";
			}

			// Print row
			echo '<tr>';
			echo '<td><p class="text-center">' . $user['id'] . '</p></td>';
			echo '<td><p class="text-center"><b>' . $user['username'] . '</b></p></td>';
			echo '<td><p class="text-center"><span class="label label-' . $groupColor . '">' . $groupText . '</span></p></td>';
			echo '<td><p class="text-center"><span class="label label-' . $allowedColor . '">' . $allowedText . '</span></p></td>';
			echo '<td><p class="text-center">
			<div class="btn-group-justified">
			<a title="Edit user" class="btn btn-xs btn-primary" href="index.php?p=103&id=' . $user['id'] . '"><span class="glyphicon glyphicon-pencil"></span></a>';
			if (hasPrivilege(Privileges::AdminBanUsers)) {
				echo '<a title="(Un)restrict user" class="btn btn-xs btn-warning" href="index.php?p=137&id=' . $user['id'] . '"><span class="glyphicon glyphicon-remove-circle"></span></a>';
			}
			//if (hasPrivilege(Privileges::AdminBanUsers)) {
			//	if (isBanned($user["id"])) {
			//		echo '<a title="Unban user" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'&csrf=' . csrfToken() . '\')"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
			//	}/* else {
			//		echo '<a title="Ban user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'&csrf=' . csrfToken() . '\')"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
			//	}*/
			//	if (isRestricted($user["id"])) {
			//		echo '<a title="Remove restrictions" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'&csrf='.csrfToken().'\')"><span class="glyphicon glyphicon-ok-circle"></span></a>';
			//	}/* else {
			//		echo '<a title="Restrict user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'&csrf='.csrfToken().'\')"><span class="glyphicon glyphicon-remove-circle"></span></a>';
			//	}*/
			//}
			echo '	<a title="Change user identity" class="btn btn-xs btn-danger" href="index.php?p=104&id=' . $user['id'] . '"><span class="glyphicon glyphicon-refresh"></span></a>
			</div>
			</p></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p align="center"><a href="index.php?p=102&from=' . ($from - ($pageInterval + 1)) . '">< Previous page</a> | <a href="index.php?p=102&from=' . ($to) . '">Next page ></a></p>';
		echo '</div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Search user by email modal
		echo '<div class="modal fade" id="quickEditEmailModal" tabindex="-1" role="dialog" aria-labelledby="quickEditEmailModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditEmailModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-email-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="quickEditUserEmail" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Email" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-email-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Silence user modal
		echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="silenceUserModal">Silence user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="silence-user-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="silenceUser" hidden>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
		<input type="number" name="c" class="form-control" placeholder="How long" aria-describedby="basic-addon1" required>
		<select name="un" class="selectpicker" data-width="30%">
			<option value="1">Seconds</option>
			<option value="60">Minutes</option>
			<option value="3600">Hours</option>
			<option value="86400">Days</option>
		</select>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1">
		</div>

		<p style="line-height: 15px"></p>

		During the silence period, user\'s client will be locked. <b>Max silence time is 30 days.</b> Set length to 0 to remove the silence.

		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
		</div>
		</div>
		</div>
		</div>';
		// Kick user modal
		echo '<div class="modal fade" id="kickUserModal" tabindex="-1" role="dialog" aria-labelledby="kickUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="kickUserModalLabel">Kick user from Bancho</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="kick-user-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="kickUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</p>
		<p>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1" value="You have been kicked from the server. Please login again." required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="kick-user-form" class="btn btn-primary">Kick user</button>
		</div>
		</div>
		</div>
		</div>';
	}


	/*
	 * AdminEditUser
	 * Prints the admin panel edit user page
	 */
	public static function AdminEditUser()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid user ID!');
			}
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			$lastScoreDetection = $GLOBALS['db']->fetch('SELECT created_at FROM score_detections WHERE user_id = ? ORDER BY created_at DESC LIMIT 1', $_GET['id']);
			$ips = $GLOBALS['db']->fetchAll('SELECT ip, occurencies FROM ip_user WHERE userid = ? ORDER BY occurencies DESC LIMIT 50', $_GET['id']);
			// Check if this user exists
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Cb check
			if ($userData["can_custom_badge"] == 1) {
				$cbText = "Yes";
				$cbCol = "success";
			} else {
				$cbText = "No";
				$cbCol = "danger";
			}
			// Whitelist check
			if ($userData["whitelist"] != 0) {
				$wlCol = "success";
				$wl = array();
				if ($userData["whitelist"] & 1)
					array_push($wl, "Vanilla");
				if ($userData["whitelist"] & 2)
					array_push($wl, "Relax");
				$wlText = implode(" & ", $wl);
			} else {
				$wlText = "No";
				$wlCol = "danger";
			}
			// Userpage check
			if ($userData["userpage_allowed"] == 1) {
				$upText = "Yes";
				$upCol = "success";
			} else {
				$upText = "No";
				$upCol = "danger";
			}
			// Set readonly stuff
			$readonly[0] = ''; // User data stuff
			$readonly[1] = ''; // Username color/style stuff
			$selectDisabled = '';
			// Check if we are editing our account
			if ($userData['username'] == $_SESSION['username'] || hasPrivilege(Privileges::AdminCaker)) {
				// Allow to edit only user stats
				$readonly[0] = 'readonly';
				$selectDisabled = 'disabled';
			} elseif (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && !hasPrivilege(Privileges::AdminCaker)) {
				// We are trying to edit a user with same/higher rank than us :akerino:
				redirect("index.php?p=102&e=You don't have enough permissions to edit this user");
				die();
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-user"></i>	Edit user</font></p>';
			echo '<table class="table table-striped table-hover table-75-center edit-user">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="saveEditUser" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="' . $userData['id'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="' . $userData['username'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Country</td>
			<td>
			<select name="country" class="selectpicker" data-width="100%">
			';
			require_once dirname(__FILE__) . "/countryCodesReadable.php";
			asort($c);
			// Push XX to top
			$c = array('XX' => $c['XX']) + $c;
			reset($c);
			foreach ($c as $k => $v) {
				$sd = "";
				if ($userData['country'] == $k)
					$sd = "selected";
				$ks = strtolower($k);
				if (!file_exists(dirname(__FILE__) . "/../images/flags/$ks.png"))
					$ks = "xx";
				echo "<option value='$k' $sd data-content=\""
					. "<img src='images/flags/$ks.png' alt='$k'>"
					. " $v\"></option>\n";
			}
			echo '
			</select>
			</td>
			</tr>';

			// Get clan information
			$clanInfo = null;
			if ($userData['clan_id']) {
				$clanInfo = $GLOBALS['db']->fetch('SELECT id, name, tag FROM clans WHERE id = ? LIMIT 1', [$userData['clan_id']]);
			}

			echo '<tr>
			<td>Clan</td>
			<td>';
			if ($clanInfo) {
				echo '<span class="label label-info">' . htmlspecialchars($clanInfo['tag']) . '</span> ';
				echo '<a href="index.php?p=141&id=' . $clanInfo['id'] . '">' . htmlspecialchars($clanInfo['name']) . '</a>';
			} else {
				echo '<em>No clan</em>';
			}
			echo '</td>
			</tr>';

			// Discord link status
			$hasDiscordLink = !empty($userData['discord_account_id']);
			echo '<tr>
			<td>Linked Discord</td>
			<td>';
			if ($hasDiscordLink) {
				echo '<span class="label label-success">Yes</span>';
				echo ' <a onclick="sure(\'submit.php?action=resetDiscordLink&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\')">(reset link)</a>';
			} else {
				echo '<span class="label label-danger">No</span>';
			}
			echo '</td>
			</tr>';

			echo '<tr>
			<td>Registered (dd/mm/yyyy) </td>
			<td>' . date('d/m/Y', $userData['register_datetime']) . '</td>
			</tr>';
			echo '<tr>
			<td>Latest activity (dd/mm/yyyy)</td>
			<td>' . date('d/m/Y', $userData['latest_activity']) . '</td>
			</tr>';
			echo '<tr>
			<td>Account Standing</td>
			<td>';

			if (isBanned($userData["id"])) {
				echo "Banned";
			} else if (isRestricted($userData["id"])) {
				echo "Restricted";
			} else if (!hasPrivilege(Privileges::UserNormal, $userData["id"])) {
				echo "Locked";
			} else {
				echo "Ok";
			}

			echo '</td>
			</tr>';

			echo '<tr>
			<td>Cheated score last detected at (dd/mm/yyyy)</td>
			<td>' . (($lastScoreDetection && $lastScoreDetection['created_at']) ? (new DateTime($lastScoreDetection['created_at']))->format('d/m/Y') : 'Never') . '</td>
			</tr>';

			echo '<tr class="single-row">
			<td>Whitelisted</td>
			<td><span class="label label-' . $wlCol . '">' . $wlText . '</span></td>
			</tr>';
			echo '<tr class="single-row">
			<td>Userpage allowed</td>
			<td><span class="label label-' . $upCol . '">' . $upText . '</span></td>
			</tr>';
			echo '<tr class="single-row">
			<td>Can edit custom badge</td>
			<td><span class="label label-' . $cbCol . '">' . $cbText . '</span></td>
			</tr>';

			echo '<tr>
			<td>Avatar<br><a onclick="sure(\'submit.php?action=resetAvatar&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\')">(reset avatar)</a></td>
			<td>
				<p align="center">
					<img src="' . URL::PublicAvatarServiceBaseUrl() . '/' . $_GET['id'] . '" height="50" width="50"></img>
				</p>
			</td>
			</tr>';

			if (isBanned($userData["id"]) || isRestricted($userData["id"])) {
				$canAppeal = time() - $userData["ban_datetime"] >= 86400 * (30 * 2); // Seconds in a day * days in a month
				echo '<tr class="';
				echo $canAppeal ? 'success' : 'warning';
				echo '">
				<td>Ban/Restricted Date<br><i>(dd/mm/yyyy)</i></td>
				<td>' . date('d/m/Y', $userData["ban_datetime"]) . "<br>";
				echo $canAppeal ? '<i> (can appeal)</i>' : '<i> (can\'t appeal yet)<i>';
				echo '</td>
				</tr>';
			}

			if (hasPrivilege(Privileges::UserDonor, $userData["id"])) {
				$donorExpire = timeDifference($userData["donor_expire"], time(), false);
				echo '<tr>
				<td>' . (hasPrivilege(Privileges::UserPremium) ? 'Premium' : 'Supporter') . ' expires in</td>
				<td>' . $donorExpire . '</td>
				</tr>';
			}

			echo '<tr>
			<td>A.K.A</td>
			<td><p class="text-center"><input type="text" name="aka" class="form-control" value="' . htmlspecialchars($userData['username_aka']) . '"></td>
			</tr>';
			echo '<tr>
			<td>Userpage<br><a onclick="censorUserpage();">(reset userpage)</a></td>
			<td><p class="text-center"><textarea name="up" class="form-control" style="overflow:auto;resize:vertical;height:200px">' . $userData['userpage_content'] . '</textarea></td>
			</tr>';
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				echo '<tr>
				<td>Silence end time<br><a onclick="removeSilence();">(remove silence)</a></td>
				<td><p class="text-center"><input type="text" name="se" class="form-control" value="' . $userData['silence_end'] . '"></td>
				</tr>';
				echo '<tr>
				<td>Silence reason</td>
				<td><p class="text-center"><input type="text" name="sr" class="form-control" value="' . $userData['silence_reason'] . '"></td>
				</tr>';
			}
			if (hasPrivilege(Privileges::AdminManagePrivileges)) {
				$gd = $userData["id"] == $_SESSION["userid"] ? "disabled" : "";
				echo '<tr>
				<td>Privileges<br><i class="no-mobile">(Don\'t touch UserPublic or UserNormal. Use ban/restricted buttons instead to avoid messing up)</i></td>
				<td>';
				$refl = new ReflectionClass("Privileges");
				$privilegesList = $refl->getConstants();
				foreach ($privilegesList as $i => $v) {
					if ($v <= 0)
						continue;
					$c = (($userData["privileges"] & $v) > 0) ? "checked" : "";
					$d = ($v <= 2 && $gd != "disabled") ? "disabled" : "";
					echo '<label><input name="privilege" value="' . $v . '" type="checkbox" onclick="updatePrivileges();" ' . $c . ' ' . $gd . ' ' . $d . '>	' . $i . ' (' . $v . ')</label><br>';
				}
				echo '</tr>';
				$ro = $userData["id"] == $_SESSION["userid"] ? "readonly" : "";
				echo '<tr>
				<td>Privilege number</td>
				<td><input class="form-control" id="privileges-value" name="priv" value="' . $userData["privileges"] . '" ' . $ro . '></td>
				</tr>';
				echo '<tr>
				<td>Privilege group<i class="no-mobile">(This is basically a preset and will replace every existing privilege)</i></td>
				<td>
					<select id="privileges-group" name="privgroup" class="selectpicker" data-width="100%" onchange="groupUpdated();" ' . $gd . '>';
				$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
				echo "<option value='-1'>None</option>";
				foreach ($groups as $group) {
					$s = (($userData["privileges"] == $group["privileges"]) || ($userData["privileges"] == ($group["privileges"] | Privileges::UserDonor))) ? "selected" : "";
					echo "<option value='$group[privileges]' $s>$group[name]</option>";
				}
				echo '</select>
				</td>
				</tr>';
			}
			if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
				echo '<tr>
				<td>Custom badge</td>
				<td>
					<p align="center">
						<i class="fa ' . htmlspecialchars($userData["custom_badge_icon"]) . ' fa-2x"></i>
						<br>
						<b>' . htmlspecialchars($userData["custom_badge_name"]) . '</b>
					</p>
				</td>
				</tr>';
			}
			echo '<tr>
			<td>Notes for CMs
			<br>
			<i>(visible only from RAP)</i></td>
			<td><textarea name="ncm" class="form-control" style="overflow:auto;resize:vertical;height:500px">' . $userData["notes"] . '</textarea></td>
			</tr>';

			if (hasPrivilege(Privileges::AdminManagePrivileges)) {
				echo '<tr><td>Top 50 IPs (descending by occurrence count)<br>';
				echo '<i><a href="index.php?p=136&uid=' . $_GET["id"] . '">(search users with these IPs)</a></i>';
				echo '</td><td><ul>';

				foreach ($ips as $ip) {
					echo "<li>$ip[ip] <a class='getcountry' data-ip='$ip[ip]' title='Click to retrieve IP country'>(?)</a> ($ip[occurencies])</li>";
				}
			}
			echo '</ul></td></tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center table-50-center bottom-padded">
					<button type="submit" form="system-settings-form" class="btn btn-primary">Save changes</button><br><br>
					<div class="bottom-fixed">
						<div class="alert alert-warning">
							<i class="fa fa-exclamation-triangle"></i>	<b>Make sure to save before using any of the functions below, or changes will be lost</b>.
						</div>
						<ul class="list-group">
							<li class="list-group-item list-group-item-info">
							Actions
							<a title="Pin/Unpin" class="unpin btn btn-xs btn-primary no-mobile"><span class="glyphicon glyphicon-pushpin"></span></a></li>
							<li class="list-group-item mobile-flex">';
			if (hasPrivilege(Privileges::AdminManageBadges)) {
				echo '<a href="index.php?p=110&id=' . $_GET['id'] . '" class="btn btn-success">Edit badges</a>';
			}
			echo '	<a href="index.php?p=104&id=' . $_GET['id'] . '" class="btn btn-info">Change identity</a>';
			echo '	<a href="index.php?p=105&id=' . $_GET['id'] . '" class="btn btn-info">Change whitelist</a>';
			echo '	<a href="index.php?p=106&id=' . $_GET['id'] . '" class="btn btn-info">Change email address</a>';
			if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
				echo '	<a onclick="sure(\'submit.php?action=removeDonor&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\');" class="btn btn-danger">Remove donor</a>';
			}
			echo '	<a href="index.php?p=121&id=' . $_GET['id'] . '" class="btn btn-warning">Give supporter</a>';
			echo '	<a href="https://akatsuki.gg/u/' . $_GET['id'] . '" class="btn btn-primary">View profile</a>';
			echo '</li>
						</ul>';

			echo '<ul class="list-group">
						<li class="list-group-item list-group-item-danger">Dangerous Zone</li>
						<li class="list-group-item mobile-flex">';
			if (hasPrivilege(Privileges::AdminWipeUsers)) { // Ok this is pretty cursed lol
				echo '	<a href="index.php?p=123&id=' . $_GET["id"] . '" class="btn btn-danger">Wipe account</a>';
				echo '	<a href="index.php?p=122&id=' . $_GET["id"] . '" class="btn btn-danger">Rollback account</a>';
			}
			if (hasPrivilege(Privileges::AdminCaker)) { // Only allow superadmin to lock from admin panel.
				echo '	<a onclick="sure(\'submit.php?action=lockUnlockUser&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\', \'Restrictions and bans will be removed from this account if you lock it. Make sure to lock only accounts that are not banned or restricted.\')" class="btn btn-danger">(Un)lock user</a>';
			}

			if (hasPrivilege(Privileges::AdminBanUsers)) {
				echo '	<a href="index.php?p=137&id=' . $_GET["id"] . '" class="btn btn-danger">(Un)restrict user</a>';
				echo '	<a href="index.php?p=139&id=' . $_GET["id"] . '" class="btn btn-danger">(Un)ban user</a>';
				/*if (isBanned($_GET["id"])) {
								echo '	<a onclick="sure(\'submit.php?action=banUnbanUser&id='.$_GET['id'].'&csrf=' . csrfToken() . '\')" class="btn btn-danger">Unban user</a>';
							}
							if (isRestricted($_GET["id"])) {
								echo '	<a onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$_GET['id'].'&csrf='.csrfToken().'\')" class="btn btn-danger">Unrestrict user</a>';
							}*/

				echo '	<a onclick="sure(\'submit.php?action=clearHWID&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\');" class="btn btn-danger">Clear HWID matches</a>';
			}
			echo '		<a onclick="sure(\'submit.php?action=toggleCustomBadge&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\');" class="btn btn-danger">' . (($userData["can_custom_badge"] == 1) ? "Revoke" : "Grant") . ' custom badge</a>';
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				echo '		<a onclick="sure(\'submit.php?action=toggleUserpage&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\');" class="btn btn-danger">' . (($userData["userpage_allowed"] == 1) ? "Disallow" : "Allow") . ' userpage</a>';
			}
			if (hasPrivilege(Privileges::AdminManageUsers)) {
				echo '		<a onclick="reallysure(\'submit.php?action=deleteUserAccount&id=' . $_GET['id'] . '&csrf=' . csrfToken() . '\');" class="btn btn-danger">Delete user account</a>';
			}
			echo '<br>
							</li>
						</ul>
					</div>';

			echo '</div>
		</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminChangeIdentity
	 * Prints the admin panel change identity page
	 */
	public static function AdminChangeIdentity()
	{
		try {
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we are trying to edit our account or a higher rank account
			if ($userData['username'] != $_SESSION['username'] && !hasPrivilege(Privileges::AdminCaker) && (($userData['privileges'] & Privileges::AdminManageUsers) > 0)) {

				throw new Exception("You don't have enough permissions to edit this user.");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change identity</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="changeIdentity" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="' . $userData['id'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Old Username</td>
			<td><p class="text-center"><input type="text" name="oldu" class="form-control" value="' . $userData['username'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>New Username</td>
			<td><p class="text-center"><input type="text" name="newu" class="form-control"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change identity</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminChangeWhitelist
	 * Prints the admin panel change whitelist page
	 */
	public static function AdminChangeWhitelist()
	{
		try {
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we are trying to edit our account or a higher rank account
			if ($userData['username'] != $_SESSION['username'] && !hasPrivilege(Privileges::AdminCaker) && (($userData['privileges'] & Privileges::AdminManageUsers) > 0)) {

				throw new Exception("You don't have enough permissions to edit this user.");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change whitelist</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="changeWhitelist" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="' . $userData['id'] . '" readonly></td>
			</tr>';
			// not a real input, just for displaying the old value
			echo '<tr>
			<td>Old Whitelist</td>
			<td><p class="text-center"><input type="text" name="oldwhitelist" class="form-control" value="' . getWhitelist($userData['whitelist']) . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>New Whitelist</td>
			<td>
			<select name="newwhitelist" class="selectpicker" data-width="100%">
			<option value="0">None</option>
			<option value="1">Vanilla</option>
			<option value="2">Relax</option>
			<option value="3">Vanilla & Relax</option>
			</select>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change whitelist</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * AdminChangeEmailAddress
	 * Prints the admin panel change email address page
	 */
	public static function AdminChangeEmailAddress()
	{
		try {
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we are trying to edit our account or a higher rank account
			if ($userData['username'] != $_SESSION['username'] && !hasPrivilege(Privileges::AdminCaker) && (($userData['privileges'] & Privileges::AdminManageUsers) > 0)) {

				throw new Exception("You don't have enough permissions to edit this user.");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change email address</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="changeEmailAddress" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="' . $userData['id'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>New Email Address</td>
			<td><p class="text-center"><input type="text" name="newe" class="form-control"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change email address</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminSystemSettings
	 * Prints the admin panel system settings page
	 */
	public static function AdminSystemSettings()
	{
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$wm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'"));
		$gm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'"));
		$r = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'"));
		$ga = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		$ha = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($wm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($gm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($r == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<p align="center"><font size=5><i class="fa fa-cog"></i>	System settings</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="saveSystemSettings" hidden>';
		echo '<tr>
		<td>Maintenance mode (website)</td>
		<td>
		<select name="wm" class="selectpicker" data-width="100%">
		<option value="1" ' . $selected[0][1] . '>On</option>
		<option value="0" ' . $selected[0][2] . '>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Maintenance mode (in-game)</td>
		<td>
		<select name="gm" class="selectpicker" data-width="100%">
		<option value="1" ' . $selected[1][1] . '>On</option>
		<option value="0" ' . $selected[1][2] . '>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Registration</td>
		<td>
		<select name="r" class="selectpicker" data-width="100%">
		<option value="1" ' . $selected[2][1] . '>On</option>
		<option value="0" ' . $selected[2][2] . '>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Global alert<br>(visible on every page of the website)</td>
		<td><textarea type="text" name="ga" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">' . $ga . '</textarea></td>
		</tr>';
		echo '<tr>
		<td>Homepage alert<br>(visible only on the home page)</td>
		<td><textarea type="text" name="ha" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">' . $ha . '</textarea></td>
		</tr>';
		echo '<tr class="success"><td colspan=2><p align="center">Click <a href="index.php?p=111">here</a> for bancho settings</p></td></tr>';
		echo '</tbody></form>';
		echo '</table>';
		echo '<div class="text-center"><div class="btn-group" role="group">
		<button type="submit" form="system-settings-form" class="btn btn-primary">Save settings</button>
		</div></div>';
		echo '</div>';
	}


	/*
	 * AdminBadges
	 * Prints the admin panel badges page
	 */
	public static function AdminBadges()
	{
		// Get data
		$badgesData = $GLOBALS['db']->fetchAll('SELECT * FROM badges');
		// Print docs stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Badges</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>
		<tr><th class="text-center"><i class="fa fa-certificate"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Icon</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($badgesData as $badge) {
			// Print row for this badge
			echo '<tr>
			<td><p class="text-center">' . $badge['id'] . '</p></td>
			<td><p class="text-center">' . $badge['name'] . '</p></td>
			<td><p class="text-center"><i class="fa ' . $badge['icon'] . ' fa-2x"></i></p></td>
			<td><p class="text-center">
			<div class="btn-group-justified">
			<a title="Edit badge" class="btn btn-xs btn-primary" href="index.php?p=109&id=' . $badge['id'] . '"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="Delete badge" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeBadge&id=' . $badge['id'] . '&csrf=' . csrfToken() . '\');"><span class="glyphicon glyphicon-trash"></span></a>
			</div>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div class="text-center">
			<a href="index.php?p=109&id=0" type="button" class="btn btn-primary">Add a new badge</a>
			<a type="button" class="btn btn-success" data-toggle="modal" data-target="#quickEditUserBadgesModal">Edit user badges</a>
		</div>';
		echo '</div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserBadgesModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserBadgesModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserBadgesModalLabel">Edit user badges</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="quickEditUserBadges" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user badges</button>
		</div>
		</div>
		</div>
		</div>';
	}


	/*
	 * AdminEditBadge
	 * Prints the admin panel edit badge page
	 */
	public static function AdminEditBadge()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid badge id');
			}
			// Check if we are editing or creating a new badge
			if ($_GET['id'] > 0) {
				$badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			} else {
				$badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
			}
			// Check if this doc page exists
			if (!$badgeData) {
				throw new Exception("That badge doesn't exist");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit badge</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="saveBadge" hidden>
			<input name="c" id="badge-colour-value" hidden value="' . $badgeData['colour'] . '" >';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="' . $badgeData['id'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><p class="text-center"><input type="text" name="n" class="form-control" value="' . $badgeData['name'] . '" ></td>
			</tr>';
			echo '<tr>
			<td>Icon</td>
			<td><p class="text-center"><input type="text" name="i" class="form-control icp icp-auto" value="' . $badgeData['icon'] . '" ></td>
			</tr>';
			echo '<tr>
			<td>Colour</td>
			<td style="display: flex; align-items: center; gap: 10px;"><input type="text" id="badge-colour" class="form-control" value="' . $badgeData['colour'] . '" > <button type="button" onclick="resetColour()" class="btn btn-danger">Reset colour</button></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminEditUserBadges
	 * Prints the admin panel edit user badges page
	 */
	public static function AdminEditUserBadges()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			// get all badges
			$allBadges = $GLOBALS['db']->fetchAll("SELECT id, name FROM badges");
			// Get user badges
			$userBadges = $GLOBALS['db']->fetchAll('SELECT badge FROM user_badges ub WHERE ub.user = ?', $_GET['id']);
			// Get username
			$username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $_GET['id']));
			// Print edit user badges stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit user badges</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-user-badges" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="saveUserBadges" hidden>';
			echo '<tr>
			<td>User</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			for ($i = 1; $i <= 6; $i++) {
				echo '<tr>
				<td>Badge ' . $i . '</td>
				<td>';
				echo "<select name='b0$i' class='selectpicker' data-width='100%'>";
				foreach ($allBadges as $badge) {
					$selected = "";
					if ($badge["id"] == @$userBadges[$i - 1]["badge"])
						$selected = " selected";
					echo "<option value='$badge[id]'$selected>$badge[name]</option>";
				}
				echo '</select></td>
				</tr>';
			}
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminBanchoSettings
	 * Prints the admin panel bancho settings page
	 */
	public static function AdminBanchoSettings()
	{
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$bm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'"));
		$od = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'free_direct'"));
		$rm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'"));
		$mi = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
		$lm = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'"));
		$ln = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
		$cv = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_versions'"));
		$cmd5 = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_md5s'"));
		$icons = $GLOBALS["db"]->fetchAll("SELECT * FROM main_menu_icons");
		$hasDefault = current($GLOBALS["db"]->fetch("SELECT COUNT(*) FROM main_menu_icons WHERE is_default = 1 LIMIT 1")) > 0;
		$hasIcon = current($GLOBALS["db"]->fetch("SELECT COUNT(*) FROM main_menu_icons WHERE is_current = 1 LIMIT 1")) > 0;
		$isDefault = $GLOBALS["db"]->fetch("SELECT is_default FROM main_menu_icons WHERE is_current = 1 LIMIT 1")["is_default"] == 1;
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($bm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($rm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($od == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<form id="uploadForm" action="submit.php" method="POST" enctype="multipart/form-data">
		<input form="uploadForm" name="action" value="uploadMainMenuIcon" hidden>
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		</form>
		<p align="center"><font size=5><i class="fa fa-server"></i>	Bancho settings</font></p>';
		echo '<table class="table table-striped table-hover table-75-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST">
		<input name="csrf" type="hidden" value="' . csrfToken() . '">
		<input name="action" value="saveBanchoSettings" hidden>';
		echo '<tr>
		<td>Bancho maintenance mode</td>
		<td>
		<select name="bm" class="selectpicker" data-width="100%">
		<option value="1" ' . $selected[0][1] . '>On</option>
		<option value="0" ' . $selected[0][2] . '>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Main menu icon</td>
		<td>
			<table class="table table-striped">
				<tbody>';
		foreach ($icons as $icon) {
			echo '
					<tr class="' . ($icon["is_current"] ? "success" : ($icon["is_default"] ? "warning" : "")) . '">
						<td><a href="https://i.akatsuki.gg/' . $icon["file_id"] . '.png" target="_blank">' . $icon["name"] . '</a> - <a href="' . $icon["url"] . '" target="_blank">' . $icon["url"] . '</td>
						<td style="text-align: right">
							<a ' . ($icon["is_current"] ? "disabled" : "") . ' title="Set as main menu icon" class="btn btn-success btn-xs" href="submit.php?action=setMainMenuIcon&id=' . $icon["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-check"></i></a>
							<a ' . ($icon["is_default"] ? "disabled" : "") . ' title="Set as default main menu icon" class="btn btn-info btn-xs" href="submit.php?action=setDefaultMainMenuIcon&id=' . $icon["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-asterisk"></i></a>
							<a title="Delete main menu icon" class="btn btn-danger btn-xs" href="submit.php?action=deleteMainMenuIcon&id=' . $icon["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-trash"></i></a>
						</td>
					</tr>';
		}
		echo '
					<tr class="info">
						<td colspan="2" style="vertical-align: middle"><input form="uploadForm" type="file" name="file"></td>
					</tr>
					<tr class="info">
						<td>
							<input form="uploadForm" type="text" name="name" class="form-control" placeholder="Icon name">
						</td>
						<td>
							<input form="uploadForm" type="text" name="url" class="form-control" placeholder="Click URL">
						</td>
					</tr>
					<tr class="info">
						<td colspan="3">PNG only. Recommended size: 927x300.</td>
					</tr>
					<tr class="info">
						<td colspan="3">
							<button form="uploadForm" type="submit" class="btn btn-primary" style="width: 100%"><i class="fa fa-upload"></i> Upload</button>
						</td>
					</tr>
					<tr class="warning">
						<td colspan="3">
							<a style="width: 49%; float: left;" ' . ((!$hasDefault || $isDefault) ? "disabled" : "") . ' href="submit.php?action=restoreMainMenuIcon&csrf=' . csrfToken() . '" class="btn btn-warning"><i class="fa fa-fast-backward"></i> Restore default</a>
							<a style="width: 49%; float: right;"' . (!$hasIcon ? "disabled" : "") . ' href="submit.php?action=removeMainMenuIcon&csrf=' . csrfToken() . '" class="btn btn-danger"><i class="fa fa-eraser"></i> Remove main menu icon</a>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
		</tr>';
		echo '<tr>
		<td>Login notification</td>
		<td><textarea type="text" name="ln" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">' . $ln . '</textarea></td>
		</tr>';
		echo '<tr class="success">
		<td colspan=2><p align="center"><b>Settings are automatically reloaded on Bancho when you press "Save settings".</b> There\'s no need to do <i>!system reload</i> manually anymore.</p></td>
		</tr>';
		echo '</tbody><table>
		<div class="text-center"><button type="submit" class="btn btn-primary">Save settings</button></div></form>';
		echo '</div>';
	}


	/*
	 * AdminLog
	 * Prints the admin log page
	 */
	public static function AdminLog()
	{
		// TODO: Ask stampa piede COME SI DICHIARANO LE COSTANTY IN PIACCAPPI??
		$pageInterval = 50;

		// Get data
		$first = false;
		if (isset($_GET["from"])) {
			$from = $_GET["from"];
			$first = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1")) == $from;
		} else {
			$from = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1"));
			$first = true;
		}
		$to = $from - $pageInterval;
		$logs = $GLOBALS['db']->fetchAll('SELECT rap_logs.*, users.username FROM rap_logs LEFT JOIN users ON rap_logs.userid = users.id WHERE rap_logs.id <= ? AND rap_logs.id > ? ORDER BY rap_logs.datetime DESC', [$from, $to]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper" style="text-align: left;">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span class="centered"><h2><i class="fa fa-calendar"></i>	Admin Log</h2></span>';
		// Main page content here
		echo '<div class="bubbles-container">';
		if (!$logs) {
			printBubble(999, "You", "have reached the end of the life the universe and everything. Now go OD on some meth or something because ur done.", time() - (43 * 60), "The Hitchhiker's Guide to the Galaxy");
		} else {
			$lastDay = -1;
			foreach ($logs as $entry) {
				$currentDay = date("z", $entry["datetime"]);
				if ($lastDay != $currentDay)
					echo '<div class="line"><div class="line-text"><span class="label label-primary">' . date("d/m/Y", $entry["datetime"]) . '</span></div></div>';
				printBubble($entry["userid"], $entry["username"], $entry["text"], $entry["datetime"], $entry["through"]);
				$lastDay = $currentDay;
			}
		}
		echo '</div>';
		echo '<br><br><p align="center">';
		if (!$first)
			echo '<a href="index.php?p=116&from=' . ($from + $pageInterval) . '">< Prev page</a>';
		if (!$first && $logs)
			echo ' | ';
		if ($logs)
			echo '<a href="index.php?p=116&from=' . $to . '">Next page</a> ></p>';
		// Template end
		echo '</div>';
	}


	/*
	 * HomePage
	 * Prints the homepage
	 */
	public static function HomePage()
	{
		P::GlobalAlert();
		// Home success message
		$success = ['forgetDone' => 'Done! Your "Stay logged in" tokens have been deleted from the database.'];
		$error = [1 => 'You are already logged in.'];
		if (!empty($_GET['s']) && isset($success[$_GET['s']])) {
			self::SuccessMessage($success[$_GET['s']]);
		}
		if (!empty($_GET['e']) && isset($error[$_GET['e']])) {
			self::ExceptionMessage($error[$_GET['e']]);
		}
		$color = "pink";
		if (mt_rand(0, 9) == 0) {
			switch (mt_rand(0, 3)) {
				case 0:
					$color = "red";
					break;
				case 1:
					$color = "blue";
					break;
				case 2:
					$color = "green";
					break;
				case 3:
					$color = "orange";
					break;
			}
		}
		echo '<p align="center">
		<object data="https://akatsuki.gg/static/images/logos/logo.png" type="image/png" class="akatsuki-logo"></object>
		</p>';
		global $isBday;
		if ($isBday) {
			echo '<h1>Happy birthday Akatsuki!</h1>';
		} else {
			echo '<h1>Welcome to Akatsuki\'s Admin Panel.</h1>';
		}
		// Home alert
		self::HomeAlert();
	}


	/*
	 * ExceptionMessage
	 * Display an error alert with a custom message.
	 *
	 * @param (string) ($e) The custom message (exception) to display.
	 */
	public static function ExceptionMessage($e, $ret = false)
	{
		$p = '<div class="container alert alert-danger" role="alert" style="width: 100%;"><p align="center"><b>An error occurred:<br></b>' . $e . '</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function ExceptionMessageStaccah($s, $ret = false)
	{
		return P::ExceptionMessage(htmlspecialchars($s), $ret);
	}


	/*
	 * SuccessMessage
	 * Display a success alert with a custom message.
	 *
	 * @param (string) ($s) The custom message to display.
	 */
	public static function SuccessMessage($s, $ret = false)
	{
		$p = '<div class="container alert alert-success" role="alert" style="width:100%;"><p align="center">' . $s . '</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function SuccessMessageStaccah($s, $ret = false)
	{
		return P::SuccessMessage(htmlspecialchars($s), $ret);
	}


	/*
	 * Messages
	 * Displays success/error messages from $_SESSION[errors] or $_SESSION[successes]
	 * (aka success/error messages set with addError and addSuccess).
	 *
	 * @return bool Whether something was printed.
	 */
	public static function Messages()
	{
		$p = false;
		if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
			foreach ($_SESSION['errors'] as $err) {
				self::ExceptionMessage($err);
				$p = true;
			}
			$_SESSION['errors'] = array();
		}
		if (isset($_SESSION['successes']) && is_array($_SESSION['successes'])) {
			foreach ($_SESSION['successes'] as $s) {
				self::SuccessMessage($s);
				$p = true;
			}
			$_SESSION['successes'] = array();
		}
		return $p;
	}


	/*
	 * LoggedInAlert
	 * Display a message to the user that he's already logged in.
	 * Printed when a logged in user tries to view a guest only page.
	 */
	public static function LoggedInAlert()
	{
		echo '<div class="alert alert-warning" role="alert">You are already logged in.</i></div>';
	}


	/*
	 * MaintenanceAlert
	 * Prints the maintenance alert and die if we are normal users
	 * Prints the maintenance alert and keep printing the page if we are mod/admin
	 */
	public static function MaintenanceAlert()
	{
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Akatsuki\'s website is in <b>maintenance mode</b>. Only moderators and administrators have access to the full website.</p></div>';
		} catch (Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Akatsuki\'s website is in <b>maintenance mode</b>. We are working for you, <b>please come back later.</b></p></div>';
			die();
		}
	}


	/*
	 * GameMaintenanceAlert
	 * Prints the game maintenance alert
	 */
	public static function GameMaintenanceAlert()
	{
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Akatsuki\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u><br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		} catch (Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Akatsuki\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u></b></p></div>';
		}
	}


	/*
	 * BanchoMaintenance
	 * Prints the game maintenance alert
	 */
	public static function BanchoMaintenanceAlert()
	{
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Akatsuki\'s Bancho server is in maintenance mode. You can\'t play on Akatsuki right now. Try again later.<br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		} catch (Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Akatsuki\'s Bancho server is in maintenance mode. You can\'t play on Akatsuki right now. Try again later.</p></div>';
		}
	}


	/*
	 * MaintenanceStuff
	 * Prints website/game maintenance alerts
	 */
	public static function MaintenanceStuff()
	{
		// Check Bancho maintenance
		if (checkBanchoMaintenance()) {
			self::BanchoMaintenanceAlert();
		}
		// Game maintenance check
		if (checkGameMaintenance()) {
			self::GameMaintenanceAlert();
		}
		// Check website maintenance
		if (checkWebsiteMaintenance()) {
			self::MaintenanceAlert();
		}
	}


	/*
	 * GlobalAlert
	 * Prints the global alert (only if not empty)
	 */
	public static function GlobalAlert()
	{
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning" role="alert"><p align="center">' . $m . '</p></div>';
		}
		self::RestrictedAlert();
	}


	/*
	 * HomeAlert
	 * Prints the home alert (only if not empty)
	 */
	public static function HomeAlert()
	{
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning" role="alert"><p align="center">' . $m . '</p></div>';
		}
	}


	/*
	 * FriendlistPage
	 * Prints the friendlist page.
	 */
	public static function FriendlistPage()
	{
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user friends
		$ourID = getUserID($_SESSION['username']);
		$friends = $GLOBALS['db']->fetchAll('
		SELECT user2, users.username
		FROM users_relationships
		LEFT JOIN users ON users_relationships.user2 = users.id
		WHERE user1 = ? AND users.privileges & 1 > 0', [$ourID]);
		// Title and header message
		echo '<h1><i class="fa fa-star"></i>	Friends</h1>';
		if (count($friends) == 0) {
			echo '<b>You don\'t have any friends.</b> You can add someone to your friends list<br>by clicking the <b>"Add as friend"</b> button on someones\'s profile.<br>You can add friends from the game client too.';
		} else {
			// Friendlist
			echo '<table class="table table-striped table-hover table-50-center">
			<thead>
			<tr><th class="text-center">Username</th><th class="text-center">Mutual</th></tr>
			</thead>
			<tbody>';
			// Loop through every friend and output its username and mutual status
			foreach ($friends as $friend) {
				$uname = $friend['username'];
				$mutualIcon = ($friend['user2'] == 999 || getFriendship($friend['user2'], $ourID, true) == 2) ? '<i class="fa fa-heart"></i>' : '';
				echo '<tr><td><div align="center"><a href="index.php?p=103&id=' . $friend['user2'] . '">' . $uname . '</a></div></td><td><div align="center">' . $mutualIcon . '</div></td></tr>';
			}
			echo '</tbody></table>';
		}
	}


	/*
	 * AdminRankRequests
	 * Prints the admin rank requests
	 */
	public static function AdminRankRequests()
	{
		global $ScoresConfig;
		// Get data
		$rankRequestsToday = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? LIMIT " . $ScoresConfig["rankRequestsQueueSize"], [time() - (24 * 3600)]);
		$rankRequests = $GLOBALS["db"]->fetchAll("SELECT rank_requests.*, users.username FROM rank_requests LEFT JOIN users ON rank_requests.userid = users.id WHERE time > ? ORDER BY id DESC LIMIT " . $ScoresConfig["rankRequestsQueueSize"], [time() - (24 * 3600)]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span class="centered"><h2><i class="fa fa-music"></i>	Beatmap rank requests</h2></span>';
		// Main page content here
		echo '<div class="page-content-wrapper">';
		//echo '<div style="width: 50%; margin-left: 25%;" class="alert alert-info" role="alert"><i class="fa fa-info-circle"></i>	Only the requests made in the past 24 hours are shown. <b>Make sure to load every difficulty in-game before ranking a map.</b><br><i>(We\'ll add a system that does it automatically soonTM)</i></div>';
		echo '<hr>
		<h2 style="display: inline;">' . $rankRequestsToday["count"] . '</h2><h3 style="display: inline;">/' . $ScoresConfig["rankRequestsQueueSize"] . '</h3><br><h4>requests submitted today</h4>
		<hr>';
		echo '<table class="table table-striped table-hover" style="width: 94%; margin-left: 3%;">
		<thead>
		<tr><th><i class="fa fa-music"></i>	ID</th><th>Artist & song</th><th>Difficulties</th><th>Mode</th><th>From</th><th>When</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($rankRequests as $req) {
			$criteria = $req["type"] == "s" ? "beatmapset_id" : "beatmap_id";
			$b = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name, ranked FROM beatmaps WHERE " . $criteria . " = ? LIMIT 1", [$req["bid"]]);

			if ($b) {
				$matches = [];
				if (preg_match("/(.+)(\[.+\])/i", $b["song_name"], $matches)) {
					$song = $matches[1];
				} else {
					$song = "Wat";
				}
			} else {
				$song = "Unknown";
			}

			if ($req["type"] == "s")
				$bsid = $req["bid"];
			else
				$bsid = $b ? $b["beatmapset_id"] : 0;

			$today = !($req["time"] < time() - 86400);
			$beatmaps = $GLOBALS["db"]->fetchAll("SELECT song_name, beatmap_id, ranked FROM beatmaps WHERE beatmapset_id = ? LIMIT 15", [$bsid]);
			$allUnranked = true;
			$forceParam = "1";
			foreach ($beatmaps as $beatmap) {
				if ($beatmap["ranked"] >= 2) {
					$allUnranked = false;
					$forceParam = "0";
				}
			}

			if ($req["blacklisted"] == 1) {
				$rowClass = "danger";
			} else if ($allUnranked) {
				$rowClass = $today ? "success" : "default";
			} else {
				$rowClass = "default";
			}

			/*if (($bsid & 1073741824) > 0) {
				$host = "osu!mp";
			} else if (($bsid & 536870912) > 0) {
				$host = "ripple";
			} else {
				$host = "osu!";
			}*/

			echo "<tr class='$rowClass'>
				<td><a href='https://osu.ppy.sh/s/$bsid' target='_blank'>$req[type]/$req[bid]</a></td>
				<td>$song</td>
				<td>$req[username]</td>
				<td>" . timeDifference(time(), $req["time"]) . "</td>
				<td>
					<p class='text-center'>
						<a title='Edit ranked status' class='btn btn-xs btn-primary' href='index.php?p=124&bsid=$bsid&force=" . $forceParam . "'><span class='glyphicon glyphicon-pencil'></span></a>
					</p>
				</td>
			</tr>";
		}
		echo '</tbody>';
		echo '</table>';
		// Template end
		echo '</div>';
	}


	public static function AdminPrivilegesGroupsMain()
	{
		// Get data
		$groups = $GLOBALS['db']->fetchAll('SELECT * FROM privileges_groups ORDER BY id ASC');
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span class="centered"><h2><i class="fa fa-group"></i>	Privilege Groups</h2></span>';
		// Main page content here
		echo '<div align="center">';
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-group"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Privileges</th><th class="text-center">Action</th></tr>
		</thead>
		<tbody>';
		foreach ($groups as $group) {
			echo "<tr>
					<td style='text-align: center;'>$group[id]</td>
					<td style='text-align: center;'>$group[name]</td>
					<td style='text-align: center;'>$group[privileges]</td>
					<td style='text-align: center;'>
						<div class='btn-group-justified'>";
			if (hasPrivilege(Privileges::AdminCaker)) { //edit needs dev
				echo		"<a href='index.php?p=119&id=$group[id]' title='Edit' class='btn btn-xs btn-primary'><span class='glyphicon glyphicon-pencil'></span></a>";
			}
			echo			"<a href='index.php?p=119&h=$group[id]' title='Inherit' class='btn btn-xs btn-warning'><span class='glyphicon glyphicon-copy'></span></a>
							<a href='index.php?p=120&id=$group[id]' title='View users in this group' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-search'></span></a>
						</div>
					</td>
				</tr>";
		}
		echo '</tbody>
		</table>';

		echo '<a href="index.php?p=119" type="button" class="btn btn-primary">New group</a>';

		echo '</div>';
		// Template end
		echo '</div>';
	}


	public static function AdminEditPrivilegesGroups()
	{
		try {
			// Check if id is set, otherwise set it to 0 (new badge)
			if (!isset($_GET['id']) && !isset($_GET["h"])) {
				$_GET['id'] = 0;
			}
			// Check if we are editing, creating or inheriting a new group
			if (isset($_GET["h"])) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', [$_GET['h']]);
				$privilegeGroupData["id"] = 0;
				$privilegeGroupData["name"] .= " (child)";
			} else if ($_GET["id"] > 0) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', $_GET['id']);
			} else {
				$privilegeGroupData = ['id' => 0, 'name' => 'New Privilege Group', 'privileges' => 0, 'color' => 'default'];
			}
			// Check if this group exists
			if (!$privilegeGroupData) {
				throw new Exception("That privilege group doesn't exists");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-group"></i>	Privilege Group</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="savePrivilegeGroup" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><input type="number" name="id" class="form-control" value="' . $privilegeGroupData['id'] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><input type="text" name="n" class="form-control" value="' . $privilegeGroupData['name'] . '" ></td>
			</tr>';
			echo '<tr>
			<td>Privileges</td>
			<td>';

			$refl = new ReflectionClass("Privileges");
			$privilegesList = $refl->getConstants();
			foreach ($privilegesList as $i => $v) {
				if ($v <= 0)
					continue;
				$c = (($privilegeGroupData["privileges"] & $v) > 0) ? "checked" : "";
				echo '<label class="colucci"><input name="privileges" value="' . $v . '" type="checkbox" onclick="updatePrivileges();" ' . $c . '>	' . $i . ' (' . $v . ')</label><br>';
			}
			echo '</td></tr>';

			echo '<tr>
			<td>Privileges number</td>
			<td><input class="form-control" id="privileges-value" name="priv" value="' . $privilegeGroupData["privileges"] . '"></td>
			</tr>';

			// Selected stuff
			$sel = ["", "", "", "", "", ""];
			switch ($privilegeGroupData["color"]) {
				case "default":
					$sel[0] = "selected";
					break;
				case "success":
					$sel[1] = "selected";
					break;
				case "warning":
					$sel[2] = "selected";
					break;
				case "danger":
					$sel[3] = "selected";
					break;
				case "primary":
					$sel[4] = "selected";
					break;
				case "info":
					$sel[5] = "selected";
					break;
			}

			echo '<tr>
			<td>Color<br><i>(used in RAP users listing page)</i></td>
			<td>
			<select name="c" class="selectpicker" data-width="100%">
				<option value="default" ' . $sel[0] . '>Gray</option>
				<option value="success" ' . $sel[1] . '>Green</option>
				<option value="warning" ' . $sel[2] . '>Yellow</option>
				<option value="danger" ' . $sel[3] . '>Red</option>
				<option value="primary" ' . $sel[4] . '>Blue</option>
				<option value="info" ' . $sel[5] . '>Light Blue</option>
			</select>
			</td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=119&e=' . $e->getMessage());
		}
	}


	public static function AdminShowUsersInPrivilegeGroup()
	{
		// Exist check
		try {
			if (!isset($_GET["id"])) {
				throw new Exception("That group doesn't exist");
			}

			// Get data
			$groupData = $GLOBALS["db"]->fetch("SELECT * FROM privileges_groups WHERE id = ?", [$_GET["id"]]);
			if (!$groupData) {
				throw new Exception("That group doesn't exist");
			}
			$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE privileges = ? OR privileges = ? | ' . Privileges::UserDonor, [$groupData["privileges"], $groupData["privileges"]]);
			// Print sidebar and template stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Header
			echo '<span class="centered"><h2><i class="fa fa-search"></i>	Users in ' . $groupData["name"] . ' group</h2></span>';
			// Main page content here
			echo '<div align="center">';
			echo '<table class="table table-striped table-hover table-75-center">
			<thead>
			<tr><th class="text-left"><i class="fa fa-group"></i>	ID</th><th class="text-center">Username</th></tr>
			</thead>
			<tbody>';
			foreach ($users as $user) {
				echo "<tr>
						<td style='text-align: center;'>$user[id]</td>
						<td style='text-align: center;'><a href='index.php?p=103&id=$user[id]'>$user[username]</a></td>
					</tr>";
			}
			echo '</tbody>
			</table>';

			echo '</div>';
			// Template end
			echo '</div>';
		} catch (Exception $e) {
			redirect("index.php?p=118?e=" . $e->getMessage());
		}
	}


	public static function RestrictedAlert()
	{
		if (!checkLoggedIn()) {
			return;
		}

		if (!hasPrivilege(Privileges::UserPublic)) {
			echo '<div class="alert alert-danger" role="alert">
					<p align="center"><i class="fa fa-exclamation-triangle"></i><b>Your account is currently in restricted mode</b> due to inappropriate behavior or a violation of the <a href=\'index.php?p=23\'>rules</a>.<br>You can\'t interact with other users, you can perform limited actions and your user profile and scores are hidden.<br>Read the <a href=\'index.php?p=23\'>rules</a> again carefully, and if you think this is an error, send an email to <b>support@akatsuki.gg</b>.</p>
				  </div>';
		}
	}

	/*
	 * AdminRestrict
	 * Prints the admin wipe page
	 */
	public static function AdminRestrictUnrestrictReason()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-eraser"></i>	(Un)restrict account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-restrict-unrestrict" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="restrictUnrestrictUserReason" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="' . $_GET["id"] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Reason</td>
			<td><p class="text-center"><input type="text" name="reason" class="form-control"></td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-restrict-unrestrict" class="btn btn-primary">(Un)restrict user</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}

	/*
	 * AdminBan
	 */
	public static function AdminBanUnbanReason()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-eraser"></i>	(Un)ban account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-ban-unban" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="banUnbanUserReason" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="' . $_GET["id"] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Reason</td>
			<td><p class="text-center"><input type="text" name="reason" class="form-control"></td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-ban-unban" class="btn btn-primary">(Un)ban user</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminGiveDonor
	 * Prints the admin give donor page
	 */
	public static function AdminGiveDonor()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-money"></i>	Give supporter</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="edit-user-badges" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="giveDonor" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="' . $_GET["id"] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input name="m" type="number" class="form-control" placeholder="Months" required></input>
			</td>
			</tr>';
			echo '<tr>
			<td>Operation type</td>
			<td>
			<select name="type" class="selectpicker" data-width="100%">
				<option value=0 selected>Add months</option>
				<option value=1>Replace months</option>
			</select></td>
			</tr>';
			echo '<tr>
			<td> Supporter type</td>
			<td>
			<select name="stype" class="selectpicker" data-width="100%">
				<option value=0 selected>Supporter</option>
				<option value=1>Premium</option>
			</select></td>
			</tr>';


			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Give supporter</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminRollback
	 * Prints the admin rollback page
	 */
	public static function AdminRollback()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-fast-backward"></i>	Rollback account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-rollback" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="rollback" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="' . $_GET["id"] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input type="number" name="length" class="form-control" style="width: 40%; display: inline;">
			<div style="width: 5%; display: inline-block;"></div>
			<select name="period" class="selectpicker" data-width="53%">
				<option value="d">Days</option>
				<option value="w">Weeks</option>
				<option value="m">Months</option>
				<option value="y">Years</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-rollback" class="btn btn-primary">Rollback account</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminWipe
	 * Prints the admin wipe page
	 */
	public static function AdminWipe()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<div class="container alert alert-danger" role="alert" style="width: 100%;"><p align="center"><b>Reminder:<br></b>Admins should not provide wipes for users who have not purchased supporter, unless it is warranted.</p></div>';
			echo '<p align="center"><font size=5><i class="fa fa-eraser"></i>	Wipe account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-wipe" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="wipeAccount" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="' . $_GET["id"] . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="' . $username . '" readonly></td>
			</tr>';
			echo '<tr>
			<td>Gamemode</td>
			<td>
			<select name="gm" class="selectpicker" data-width="100%">
				<option value="-1">All</option>
				<option value="0">osu!</option>
				<option value="1">osu!taiko</option>
				<option value="2">osu!catch</option>
				<option value="3">osu!mania</option>
			</select>';
			echo '<tr>
			<td>Akatsuki Mode</td>
			<td>
			<select name="rx" class="selectpicker" data-width="100%">
				<option value="0">Vanilla</option>
				<option value="1">Relax</option>
				<option value="2">Autopilot</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-wipe" class="btn btn-primary">Wipe account</button></div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	 */
	public static function AdminRankBeatmap()
	{
		try {
			// Check if id is set
			if (!isset($_GET['bsid']) || empty($_GET['bsid'])) {
				throw new Exception('Invalid beatmap set id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><h2><i class="fa fa-music"></i>	Rank beatmap</h2></p>';

			echo '<br><br>';

			echo '<div id="main-content">
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Loading beatmap data from osu!api...</h3>
				<h5>This might take a while</h5>
			</div>';
			echo '</div>';
			echo '</div>';
		} catch (Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=117&e=' . $e->getMessage());
		}
	}


	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	 */
	public static function AdminRankBeatmapManually()
	{
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-level-up"></i>	Rank beatmap manually</h2></p>';

		echo '<br>';

		echo '
		<div class="narrow-content">
			<form action="submit.php" method="POST">
				<input name="csrf" type="hidden" value="' . csrfToken() . '">
				<input name="action" value="redirectRankBeatmap" hidden>
				<input name="id" type="text" class="form-control" placeholder="Beatmap(set) id" style="width: 40%; display: inline;">
				<div style="width: 1%; display: inline-block;"></div>
				<select name="type" class="selectpicker bs-select-hidden" data-width="25%">
					<option value="bid" selected="">Beatmap ID</option>
					<option value="bsid">Beatmap Set ID</option>
				</select>
				<hr>
				<button type="submit" class="btn btn-primary">Edit ranked status</button>
			</form>
		</div>';

		echo '</div>';
		echo '</div>';
	}


	public static function AdminViewReports()
	{
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		self::MaintenanceStuff();
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-flag"></i>	Reports</h2></p>';

		echo '<br>';

		$reports = $GLOBALS["db"]->fetchAll("SELECT * FROM reports ORDER BY id DESC LIMIT 50;");
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-flag"></i>	ID</th><th class="text-center">From</th><th class="text-center">Target</th><th class="text-l">Reason</th><th class="text-center">When</th><th class="text-center">Assignee</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($reports as $report) {
			if ($report['assigned'] == 0) {
				$rowClass = "danger";
				$assignee = "No one";
			} else if ($report['assigned'] == -1) {
				$rowClass = "success";
				$assignee = "Solved";
			} else if ($report["assigned"] == -2) {
				$rowClass = "warning";
				$assignee = "Useless";
			} else {
				$rowClass = "";
				$assignee = '<img class="circle" style="width: 30px; height: 30px; margin-top: 0px;" src="https://a.akatsuki.gg/' . $report['assigned'] . '"> ' . getUserUsername($report['assigned']);
			}
			echo '<tr class="' . $rowClass . '">
			<td><p class="text-center">' . $report['id'] . '</p></td>
			<td><p class="text-center"><a href="index.php?p=103&id=' . $report["from_uid"] . '" target="_blank">' . getUserUsername($report['from_uid']) . '</a></p></td>
			<td><p class="text-center"><b><a href="index.php?p=103&id=' . $report["to_uid"] . '" target="_blank">' . getUserUsername($report['to_uid']) . '</a></b></p></td>
			<td><p>' . htmlspecialchars(substr($report['reason'], 0, 40)) . '</p></td>
			<td><p>' . timeDifference(time(), $report['time']) . '</p></td>
			<td><p class="text-center">' . $assignee . '</p></td>
			<td><p class="text-center">
			<a title="View/Edit report" class="btn btn-xs btn-primary" href="index.php?p=127&id=' . $report['id'] . '"><span class="glyphicon glyphicon-zoom-in"></span></a>
			<!-- <a title="Set as solved" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-ok"></span></a>-->
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '</div>';
		echo '</div>';
	}

	public static function AdminViewReport()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$report = $GLOBALS["db"]->fetch("SELECT * FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$report) {
				throw new Exception("Invalid report id");
			}
			$statusRowClass = "";
			if ($report["assigned"] == 0) {
				$status = "Unassigned";
			} else if ($report["assigned"] == -1) {
				$status = "Solved";
				$statusRowClass = "info";
			} else if ($report["assigned"] == -2) {
				$status = "Useless";
				$statusRowClass = "warning";
			} else {
				$status = "Assigned to " . getUserUsername($report["assigned"]);
				if ($report["assigned"] == $_SESSION["userid"]) {
					$statusRowClass = "success";
				}
			}
			$reportedCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE to_uid = ? AND time >= ? LIMIT 1", [$report["to_uid"], time() - 86400 * 30])["count"];
			$uselessCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE from_uid = ? AND assigned = -2 AND time >= ? LIMIT 1", [$report["from_uid"], time() - 86400 * 30])["count"];

			$takeButtonText = $report["assigned"] == 0 || $report["assigned"] != $_SESSION["userid"] ? "Take" : "Leave";
			$takeButtonDisabled = $report["assigned"] < 0  ? "disabled" : "";

			$solvedButtonText = $report["assigned"] != -1 ? "Mark as solved" : "Mark as unsolved";
			$solvedButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -1 ? "disabled" : "";

			$uselessButtonText = $report["assigned"] != -2 ? "Mark as useless" : "Mark as useful";
			$uselessButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -2 ? "disabled" : "";

			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			self::MaintenanceStuff();
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			echo '<p align="center">
				<h2><i class="fa fa-flag"></i>	View report</h2>
				<h4><a href="index.php?p=126"><i class="fa fa-chevron-left"></i>&nbsp;&nbsp;Back</a></h4>
			</p>';

			echo '<br>';

			echo '
			<div class="narrow-content">
				<table class="table table-striped table-hover table-100-center"><tbody>
					<tr>
						<td><b>From</b></td>
						<td>' . getUserUsername($report["from_uid"]) . '</td>
					</tr>
					<tr>
						<td><b>Reported user</b></td>
						<td><a href="index.php?p=103&id=' . $report["to_uid"] . '" target="_blank" class="badguy">' . getUserUsername($report["to_uid"]) . '</a></td>
					</tr>
					<tr>
						<td><b>Reason</b></td>
						<td><i>' . htmlspecialchars($report["reason"]) . '</i></td>
					</tr>
					<tr>
						<td><b>When</b></td>
						<td>' . timeDifference(time(), $report["time"]) . '</td>
					</tr>
					<tr>
						<td><b>Chatlog*</b></td>
						<td class="code">' . $report["chatlog"] .  '</td>
					</tr>
					<tr class="' . $statusRowClass . '">
						<td><b>Status</b></td>
						<td>' . $status . '</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["to_uid"]) . '</b> has been reported <b>' . $reportedCount . '</b> times in the last month</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["from_uid"]) . '</b> has sent <b>' . $uselessCount . '</b> useless reports in the last month</td>
					</tr>
				</table>

				<ul class="list-group">
					<li class="list-group-item list-group-item-warning">Ticket actions</li>
					<li class="list-group-item mobile-flex">
						<a class="btn btn-warning ' . $takeButtonDisabled . '" href="submit.php?action=takeReport&id=' . $report["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-bolt"></i> ' . $takeButtonText . ' ticket</a>
						<a class="btn btn-success ' . $solvedButtonDisabled . '" href="submit.php?action=solveUnsolveReport&id=' . $report["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-check"></i> ' . $solvedButtonText . '</a>
						<a class="btn btn-danger ' . $uselessButtonDisabled . '" href="submit.php?action=uselessUsefulReport&id=' . $report["id"] . '&csrf=' . csrfToken() . '"><i class="fa fa-trash"></i> ' . $uselessButtonText . '</a>
					</li>
				</ul>

				<ul class="list-group">
					<li class="list-group-item list-group-item-danger">Quick actions</li>
					<li class="list-group-item mobile-flex">
						<a class="btn btn-primary" href="index.php?p=103&id=' . $report["to_uid"] . '"><i class="fa fa-expand"></i> View reported user in RAP</a>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["to_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence reported user</div>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["from_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence source user</div>
						';
			//$restrictedDisabled = isRestricted($report["to_uid"]) ? "disabled" : "";
			//echo '<a class="btn btn-danger ' . $restrictedDisabled . '" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id=' . $report["to_uid"] . '&resend=1&csrf='.csrfToken().'\')"><i class="fa fa-times"></i> Restrict reported user</a>';
			echo '</li>
				</ul>

				<i><b>*</b> Latest 10 public messages sent from reported user before getting reported, trimmed to 50 characters.</i>

			</div>';

			echo '</div>';
			echo '</div>';
			// Silence user modal
			echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
			<div class="modal-dialog">
			<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Silence user</h4>
			</div>
			<div class="modal-body">
			<p>
			<form id="silence-user-form" action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="silenceUser" hidden>
			<input name="resend" value="1" hidden>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
			<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
			<input type="number" name="c" class="form-control" placeholder="How long" aria-describedby="basic-addon1" required>
			<select name="un" class="selectpicker" data-width="30%">
				<option value="1">Seconds</option>
				<option value="60">Minutes</option>
				<option value="3600">Hours</option>
				<option value="86400">Days</option>
			</select>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
			<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1">
			</div>

			<p style="line-height: 15px"></p>

			During the silence period, user\'s client will be locked. <b>Max silence time is 30 days.</b> Set length to 0 to remove the silence.

			</form>
			</p>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
			</div>
			</div>
			</div>
			</div>';
		} catch (Exception $e) {
			redirect("index.php?p=126&e=" . $e->getMessage());
		}
	}

	public static function AdminSearchUserByIP()
	{
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-map-marker"></i>	Search user by IP</h2></p>';
		echo '<br><p align="center"><h2>Remember, this is not 100% evidence! Take it with a grain of salt!</h2></p><br>';

		echo '
		<div class="narrow-content">
			<form action="index.php?p=136" method="POST">
				<input name="csrf" type="hidden" value="' . csrfToken() . '">
				<div>
					Specify 1 IP per line
					<textarea name="ips" class="form-control" style="overflow:auto;resize:vertical;min-height:200px; margin-bottom: 10px;"></textarea>
				</div>
				<div>
					<button type="submit" class="btn btn-primary">Search</button>
				</div>
			</form>
		</div>';

		echo '</div>';
		echo '</div>';
	}

	public static function AdminRecentTopPlays()
	{
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}

		$playsVanilla = $GLOBALS['db']->fetchAll(
			'
			SELECT
				beatmaps.song_name, beatmaps.beatmap_id, scores.beatmap_md5,
				users.username, scores.userid, scores.time, scores.score,
				scores.pp, scores.play_mode, scores.mods
			FROM scores
			LEFT JOIN beatmaps USING(beatmap_md5)
			LEFT JOIN users ON users.id = scores.userid
			WHERE scores.completed = 3
				AND users.privileges & 1
				AND users.whitelist & 1
				AND scores.play_mode = 0
				AND beatmaps.ranked = 2
				AND scores.time > UNIX_TIMESTAMP(NOW()) - 604800
			ORDER BY scores.pp DESC LIMIT 100'
		);

		$playsRelax = $GLOBALS['db']->fetchAll(
			'
			SELECT
				beatmaps.song_name, beatmaps.beatmap_id, scores_relax.beatmap_md5,
				users.username, scores_relax.userid, scores_relax.time, scores_relax.score,
				scores_relax.pp, scores_relax.play_mode, scores_relax.mods
			FROM scores_relax
			LEFT JOIN beatmaps USING(beatmap_md5)
			LEFT JOIN users ON users.id = scores_relax.userid
			WHERE scores_relax.completed = 3
				AND users.privileges & 1
				AND users.whitelist & 2
				AND scores_relax.play_mode = 0
				AND beatmaps.ranked = 2
				AND scores_relax.time > UNIX_TIMESTAMP(NOW()) - 604800
			ORDER BY scores_relax.pp DESC LIMIT 100'
		);

		// Vanilla
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top Vanilla plays for the week</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		foreach ($playsVanilla as $play) {
			if ($play['song_name']) {
				$bn = '<a href="https://akatsuki.gg/b/' . $play['beatmap_id'] . '">' . $play['song_name'] . '</a>';
			} else {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="info">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left">' . $bn . ' <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		// Relax
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top Relax plays for the week</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		foreach ($playsRelax as $play) {
			if ($play['song_name']) {
				$bn = '<a href="https://akatsuki.gg/b/' . $play['beatmap_id'] . '">' . $play['song_name'] . '</a>';
			} else {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="info">';
			echo '<td><p class="text-left"><a href="index.php?p=103&id=' . $play["userid"] . '"><b>' . $play['username'] . '</b></a></p></td>';
			echo '<td><p class="text-left">' . $bn . ' <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">' . $pm . '</p></td>';
			echo '<td><p class="text-left">' . timeDifference(time(), $play['time']) . '</p></td>';
			//echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>' . number_format($play['pp']) . '</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';
	}

	public static function AdminSearchUserByIPResults()
	{
		try {
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			$ips = [];
			$userFilter = isset($_GET["uid"]) && !empty($_GET["uid"]);
			if ($userFilter) {
				if ($_GET["uid"] != $_SESSION["userid"] && hasPrivilege(Privileges::AdminManageUsers, $_GET["uid"])) {
					throw new Exception("You don't have enough privileges to do that.");
				}
				$results = $GLOBALS["db"]->fetchAll("SELECT ip FROM ip_user WHERE userid = ? AND ip != ''", [$_GET["uid"]]);
				foreach ($results as $row) {
					array_push($ips, $row["ip"]);
				}
			} else if (isset($_POST["ips"]) && !empty($_POST["ips"])) {
				$ips = explode("\n", $_POST["ips"]);
			} else {
				throw new Exception("No IPs or uid passed.");
			}

			echo '<p align="center"><h2><i class="fa fa-map-marker"></i>	Search user by IP ' . ($userFilter ? '(user filter mode)' : '') . '</h2></p>';
			echo '<br><p align="center"><h2>Remember, this is not 100% evidence! Take it with a grain of salt!</h2></p><br>';
			echo '<br>';
			$conditions = "";
			foreach ($ips as $i => $ip) {
				$conditions .= "?, ";
				$ips[$i] = trim($ips[$i]);
			}
			$conditions = trim($conditions, ", ");
			$results = $GLOBALS["db"]->fetchAll("SELECT ip_user.*, users.username, users.privileges FROM ip_user JOIN users ON ip_user.userid = users.id WHERE ip IN ($conditions) ORDER BY ip DESC", $ips);

			echo '<table class="table table-striped table-hover table-75-center">
			<thead>
			<tr>';
			echo '<th><i class="fa fa-umbrella"></i>	IP</th>
				<th>User</th>
				<th>Privileges</th>
				<th>Occurrencies</th>
			</tr>
			</thead>';
			echo '<tbody>';

			$hax = false;
			foreach ($results as $row) {
				if (($row["privileges"] & 3) >= 3) {
					$groupColor = "success";
					$groupText = "Ok";
				} else if (($row["privileges"] & 2) >= 2) {
					$groupColor = "warning";
					$groupText = "Restricted";
				} else {
					$groupColor = "danger";
					$groupText = "Banned";
				}
				if ($userFilter && $row["userid"] != $_GET["uid"]) {
					$hax = true;
				}
				echo "<tr class='" . ($userFilter && $row["userid"] != $_GET["uid"] ? "danger bold" : "") . "'>
				<td>$row[ip] <a class='getcountry' data-ip='$row[ip]'>(?)</a></td>
				<td><a href='index.php?p=103&id=$row[userid]' target='_blank'>$row[username]</a> <i>($row[userid])</i></td>
				<td><span class='label label-$groupColor'>$groupText</span></td>
				<td>$row[occurencies]</td>
				</tr>";
			}

			if ($userFilter && !$hax) {
				echo '<td class="success" style="text-align: center" colspan=4><i class="fa fa-thumbs-up"></i>	<b>Looking good!</b></td>';
			} else if ($userFilter) {
				echo '<td class="warning" style="text-align: center" colspan=4><i class="fa fa-warning"></i>	<b>Ohoh, opsie wopsie!</b></td>';
			}

			echo '</tbody>
			</table><hr>';

			echo '<h4><i class="fa fa-map-marker"></i>	The above are all the users that used one of these IPs at least once:</h4>';
			foreach ($ips as $ip) {
				echo "$ip<br>";
			}

			echo '<hr>';
			echo '<form action="submit.php" method="POST">
			<input name="csrf" type="hidden" value="' . csrfToken() . '">
			<input name="action" value="bulkBan" hidden>';
			foreach ($results as $row) {
				echo '<input hidden name="uid[]" value="' . $row["userid"] . '">';
			}
			echo '<b>Bulk notes (will be added to already banned users too):</b>
			<div>
				<textarea name="notes" class="form-control" style="overflow:auto;resize:vertical;min-height:80px; width: 50%; margin: 0 auto 10px auto;"></textarea>
			</div>';
			echo '<a onclick="reallysuredialog() && $(\'form\').submit();" class="btn btn-danger">Bulk ban</a>
			</form>';

			echo '</div>';
			echo '</div>';
		} catch (Exception $e) {
			redirect('index.php?p=135&e=' . $e->getMessage());
		}
	}

	/*
	 * AdminClans
	 * Prints the admin panel clans list page
	 */
	public static function AdminClans()
	{
		try {
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-users"></i>	Clans Management</font></p>';

			// Search functionality
			$searchName = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
			$searchTag = isset($_GET['search_tag']) ? trim($_GET['search_tag']) : '';
			$searchOwner = isset($_GET['search_owner']) ? trim($_GET['search_owner']) : '';

			// Build search conditions
			$searchConditions = [];
			$searchParams = [];

			if (!empty($searchName)) {
				$searchConditions[] = 'c.name LIKE ?';
				$searchParams[] = '%' . $searchName . '%';
			}

			if (!empty($searchTag)) {
				$searchConditions[] = 'c.tag LIKE ?';
				$searchParams[] = '%' . $searchTag . '%';
			}

			if (!empty($searchOwner)) {
				$searchConditions[] = 'o.username LIKE ?';
				$searchParams[] = '%' . $searchOwner . '%';
			}

			$whereClause = '';
			if (!empty($searchConditions)) {
				$whereClause = 'WHERE ' . implode(' AND ', $searchConditions);
			}

			// Pagination setup
			$pageInterval = 20; // Show 20 clans per page
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			if ($page < 1) $page = 1; // Ensure page is at least 1
			$offset = ($page - 1) * $pageInterval;

			// Get total count of clans with search
			$countQuery = 'SELECT COUNT(*) FROM clans c LEFT JOIN users o ON c.owner = o.id ' . $whereClause;
			$totalClans = current($GLOBALS['db']->fetch($countQuery, $searchParams));
			$totalPages = max(1, ceil($totalClans / $pageInterval)); // Ensure at least 1 page

			// Validate page number
			if ($page > $totalPages) {
				$page = $totalPages;
				$offset = ($page - 1) * $pageInterval;
			}

			// Get clans with member count and owner info (paginated and filtered)
			try {
				$query = '
					SELECT
						c.*,
						COUNT(u.id) as member_count,
						o.username as owner_name
					FROM clans c
					LEFT JOIN users u ON c.id = u.clan_id
					LEFT JOIN users o ON c.owner = o.id
					' . $whereClause . '
					GROUP BY c.id
					ORDER BY member_count DESC, c.name ASC
					LIMIT ' . $pageInterval . ' OFFSET ' . $offset;

				$clans = $GLOBALS['db']->fetchAll($query, $searchParams);
			} catch (Exception $e) {
				$clans = [];
				echo '<p align="center"><small>Error getting clans: ' . htmlspecialchars($e->getMessage()) . '</small></p>';
			}

			// Search buttons
			echo '<div class="row" style="margin-bottom: 20px;">';
			echo '<div class="col-md-4">';
			echo '<form action="index.php?p=140" method="GET" style="display: inline;">';
			echo '<input type="hidden" name="p" value="140">';
			echo '<div class="input-group">';
			echo '<input type="text" name="search_name" class="form-control" placeholder="Search by name..." value="' . htmlspecialchars($searchName) . '">';
			echo '<span class="input-group-btn">';
			echo '<button type="submit" class="btn btn-primary">Search by Name</button>';
			echo '</span>';
			echo '</div>';
			echo '</form>';
			echo '</div>';

			echo '<div class="col-md-4">';
			echo '<form action="index.php?p=140" method="GET" style="display: inline;">';
			echo '<input type="hidden" name="p" value="140">';
			echo '<div class="input-group">';
			echo '<input type="text" name="search_tag" class="form-control" placeholder="Search by tag..." value="' . htmlspecialchars($searchTag) . '">';
			echo '<span class="input-group-btn">';
			echo '<button type="submit" class="btn btn-info">Search by Tag</button>';
			echo '</span>';
			echo '</div>';
			echo '</form>';
			echo '</div>';

			echo '<div class="col-md-4">';
			echo '<form action="index.php?p=140" method="GET" style="display: inline;">';
			echo '<input type="hidden" name="p" value="140">';
			echo '<div class="input-group">';
			echo '<input type="text" name="search_owner" class="form-control" placeholder="Search by owner..." value="' . htmlspecialchars($searchOwner) . '">';
			echo '<span class="input-group-btn">';
			echo '<button type="submit" class="btn btn-warning">Search by Owner</button>';
			echo '</span>';
			echo '</div>';
			echo '</form>';
			echo '</div>';
			echo '</div>';

			// Clear search button
			if (!empty($searchName) || !empty($searchTag) || !empty($searchOwner)) {
				echo '<p align="center"><a href="index.php?p=140" class="btn btn-default">Clear Search</a></p>';
			}

			echo '<table class="table table-striped table-hover table-75-center">
			<thead>
			<tr>
				<th><i class="fa fa-users"></i>	Name</th>
				<th><i class="fa fa-tag"></i>	Tag</th>
				<th><i class="fa fa-user"></i>	Owner</th>
				<th><i class="fa fa-users"></i>	Members</th>
				<th><i class="fa fa-info-circle"></i>	Status</th>
				<th><i class="fa fa-cog"></i>	Actions</th>
			</tr>
			</thead>
			<tbody>';

			foreach ($clans as $clan) {
				// Status colors
				$statusColors = [
					0 => ['danger', 'Closed'],
					1 => ['success', 'Open'],
					2 => ['warning', 'Invite-Only']
				];
				$status = $statusColors[$clan['status']] ?? ['secondary', 'Unknown'];

				echo '<tr>
					<td><strong>' . htmlspecialchars($clan['name']) . '</strong></td>
					<td><span class="label label-info">' . htmlspecialchars($clan['tag']) . '</span></td>
					<td>' . ($clan['owner_name'] ? htmlspecialchars($clan['owner_name']) : '<em>No owner</em>') . '</td>
					<td>' . $clan['member_count'] . '</td>
					<td><span class="label label-' . $status[0] . '">' . $status[1] . '</span></td>
					<td>
						<a href="index.php?p=141&id=' . $clan['id'] . '" class="btn btn-primary btn-sm">Edit</a>
						<a onclick="reallysuredialog() && deleteClan(' . $clan['id'] . ');" class="btn btn-danger btn-sm">Delete</a>
					</td>
				</tr>';
			}

			echo '</tbody>
			</table>';

			// Show message if no clans found
			if (empty($clans)) {
				echo '<p align="center"><em>No clans found.</em></p>';
			}

			// Pagination controls
			echo '<p align="center">';
			if ($totalPages > 1) {
				// Build query parameters for pagination links
				$queryParams = [];
				if (!empty($searchName)) $queryParams[] = 'search_name=' . urlencode($searchName);
				if (!empty($searchTag)) $queryParams[] = 'search_tag=' . urlencode($searchTag);
				if (!empty($searchOwner)) $queryParams[] = 'search_owner=' . urlencode($searchOwner);
				$queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';

				if ($page > 1) {
					echo '<a href="index.php?p=140&page=' . ($page - 1) . $queryString . '">< Previous page</a>';
				}
				if ($page > 1 && $page < $totalPages) {
					echo ' | ';
				}
				if ($page < $totalPages) {
					echo '<a href="index.php?p=140&page=' . ($page + 1) . $queryString . '">Next page ></a>';
				}
			} else {
				// Show current page info even when there's only one page
				echo '<em>Page 1 of 1</em>';
			}
			echo '</p>';

			// Delete clan form
			echo '<form id="deleteClanForm" action="submit.php" method="POST" style="display:none;">
				<input name="csrf" type="hidden" value="' . csrfToken() . '">
				<input name="action" value="deleteClan" hidden>
				<input name="id" id="deleteClanId" type="hidden">
			</form>';

			echo '<script>
			function deleteClan(clanId) {
				document.getElementById("deleteClanId").value = clanId;
				document.getElementById("deleteClanForm").submit();
			}
			</script>';

			echo '</div>';
			echo '</div>';
		} catch (Exception $e) {
			redirect('index.php?p=140&e=' . $e->getMessage());
		}
	}

	/*
	 * AdminEditClan
	 * Prints the admin panel edit clan page
	 */
	public static function AdminEditClan()
	{
		try {
			// Check if id is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid clan ID!');
			}

			$clanId = (int)$_GET['id'];

			// Get clan data
			$clanData = $GLOBALS['db']->fetch('SELECT * FROM clans WHERE id = ? LIMIT 1', [$clanId]);
			if (!$clanData) {
				throw new Exception("That clan doesn't exist");
			}

			// Get clan members
			$members = $GLOBALS['db']->fetchAll('
				SELECT u.id, u.username, u.privileges, u.register_datetime
				FROM users u
				WHERE u.clan_id = ?
				ORDER BY u.username ASC
			', [$clanId]);

			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}

			echo '<p align="center"><font size=5><i class="fa fa-edit"></i>	Edit Clan: ' . htmlspecialchars($clanData['name']) . '</font></p>';

			// Clan details form
			echo '<div class="row">
				<div class="col-md-6">
					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="fa fa-cog"></i>	Clan Details</h3>
						</div>
						<div class="panel-body">
							<form action="submit.php" method="POST">
								<input name="csrf" type="hidden" value="' . csrfToken() . '">
								<input name="action" value="saveClan" hidden>
								<input name="id" type="hidden" value="' . $clanData['id'] . '">

								<div class="form-group">
									<label>Name</label>
									<input type="text" name="name" class="form-control" value="' . htmlspecialchars($clanData['name']) . '" required>
								</div>

								<div class="form-group">
									<label>Tag</label>
									<input type="text" name="tag" class="form-control" value="' . htmlspecialchars($clanData['tag']) . '" required maxlength="8">
								</div>

								<div class="form-group">
									<label>Description</label>
									<textarea name="description" class="form-control" rows="3" maxlength="256">' . htmlspecialchars($clanData['description']) . '</textarea>
								</div>

								<div class="form-group">
									<label>Icon URL</label>
									<input type="text" name="icon" class="form-control" value="' . htmlspecialchars($clanData['icon']) . '">
								</div>

								<div class="form-group">
									<label>Background URL</label>
									<input type="text" name="background" class="form-control" value="' . htmlspecialchars($clanData['background']) . '">
								</div>

								<div class="form-group">
									<label>Status</label>
									<select name="status" class="form-control">
										<option value="0"' . ($clanData['status'] == 0 ? ' selected' : '') . '>Inactive</option>
										<option value="1"' . ($clanData['status'] == 1 ? ' selected' : '') . '>Active</option>
										<option value="2"' . ($clanData['status'] == 2 ? ' selected' : '') . '>Pending</option>
									</select>
								</div>

								<button type="submit" class="btn btn-primary">Save Changes</button>
							</form>

							<hr>
							<h4><i class="fa fa-trash"></i>	Danger Zone</h4>
							<form action="submit.php" method="POST" style="display:inline;">
								<input name="csrf" type="hidden" value="' . csrfToken() . '">
								<input name="action" value="deleteClan" hidden>
								<input name="id" type="hidden" value="' . $clanData['id'] . '">
								<button type="submit" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this clan? This will remove all members from the clan and cannot be undone.\')">Delete Clan</button>
							</form>
						</div>
					</div>
				</div>

				<div class="col-md-6">
					<div class="panel panel-info">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="fa fa-users"></i>	Clan Members (' . count($members) . ')</h3>
						</div>
						<div class="panel-body">';

			if (empty($members)) {
				echo '<p class="text-muted">No members in this clan.</p>';
			} else {
				echo '<table class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Username</th>
							<th>Join Date</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>';

				foreach ($members as $member) {
					$isOwner = ($member['id'] == $clanData['owner']);
					$ownerBadge = $isOwner ? ' <span class="label label-warning">Owner</span>' : '';

					echo '<tr>
						<td>' . htmlspecialchars($member['username']) . $ownerBadge . '</td>
						<td>' . date('Y-m-d', $member['register_datetime']) . '</td>
						<td>';

					if (!$isOwner) {
						echo '<a onclick="reallysuredialog() && kickMember(' . $clanData['id'] . ', ' . $member['id'] . ');" class="btn btn-danger btn-xs">Kick</a>';
					} else {
						echo '<em>Owner</em>';
					}

					echo '</td>
					</tr>';
				}

				echo '</tbody>
				</table>';

				// Transfer ownership section
				if (!empty($members)) {
					echo '<hr>
					<h4><i class="fa fa-exchange"></i>	Transfer Ownership</h4>
					<form action="submit.php" method="POST">
						<input name="csrf" type="hidden" value="' . csrfToken() . '">
						<input name="action" value="transferClanOwnership" hidden>
						<input name="clan_id" type="hidden" value="' . $clanData['id'] . '">

						<div class="form-group">
							<label>New Owner</label>
							<select name="new_owner_id" class="form-control" required>
								<option value="">Select new owner...</option>';

					foreach ($members as $member) {
						if ($member['id'] != $clanData['owner']) {
							echo '<option value="' . $member['id'] . '">' . htmlspecialchars($member['username']) . '</option>';
						}
					}

					echo '</select>
						</div>

						<button type="submit" class="btn btn-warning" onclick="return confirm(\'Are you sure you want to transfer ownership?\')">Transfer Ownership</button>
					</form>';
				}
			}

			echo '</div>
					</div>
				</div>
			</div>';

			// Kick member form
			echo '<form id="kickMemberForm" action="submit.php" method="POST" style="display:none;">
				<input name="csrf" type="hidden" value="' . csrfToken() . '">
				<input name="action" value="kickClanMember" hidden>
				<input name="clan_id" id="kickClanId" type="hidden">
				<input name="user_id" id="kickUserId" type="hidden">
			</form>';

			echo '<script>
			function kickMember(clanId, userId) {
				document.getElementById("kickClanId").value = clanId;
				document.getElementById("kickUserId").value = userId;
				document.getElementById("kickMemberForm").submit();
			}
			</script>';

			echo '</div>';
			echo '</div>';
		} catch (Exception $e) {
			redirect('index.php?p=140&e=' . $e->getMessage());
		}
	}

	/**
	 * AdminSharedDevices
	 * Prints the admin panel shared devices management page
	 */
	public static function AdminSharedDevices()
	{
		// Get filter parameters
		$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

		// Build query based on filter
		$whereClause = '';
		if ($filter == 'approved') {
			$whereClause = 'HAVING is_shared_device = 1';
		} elseif ($filter == 'unapproved') {
			$whereClause = 'HAVING is_shared_device = 0';
		}

		// Fetch all hardware entries with multiple users
		$multiUserHardware = $GLOBALS['db']->fetchAll("
			SELECT
				hw.mac,
				hw.unique_id,
				hw.disk_id,
				COUNT(DISTINCT hw.userid) AS user_count,
				CASE WHEN sd.id IS NOT NULL THEN 1 ELSE 0 END AS is_shared_device,
				sd.approved_by_admin_id,
				sd.approved_at,
				sd.approval_reason
			FROM hw_user hw
			LEFT JOIN shared_devices sd ON hw.mac = sd.mac AND hw.unique_id = sd.unique_id AND hw.disk_id = sd.disk_id
			GROUP BY hw.mac, hw.unique_id, hw.disk_id
			$whereClause
			ORDER BY user_count DESC, is_shared_device ASC
			LIMIT 200
		");

		// Print page
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';

		// Maintenance check
		self::MaintenanceStuff();

		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}

		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}

		echo '<p align="center"><font size=5><i class="fa fa-laptop"></i> Shared Device Management</font></p>';

		// Filter buttons
		echo '<p align="center">';
		echo '<a href="index.php?p=138&filter=all" class="btn btn-' . ($filter == 'all' ? 'primary' : 'default') . '">All Devices</a> ';
		echo '<a href="index.php?p=138&filter=unapproved" class="btn btn-' . ($filter == 'unapproved' ? 'warning' : 'default') . '">Unapproved Only</a> ';
		echo '<a href="index.php?p=138&filter=approved" class="btn btn-' . ($filter == 'approved' ? 'success' : 'default') . '">Approved Only</a>';
		echo '</p>';

		// Stats panels
		$totalMultiUser = current($GLOBALS['db']->fetch("
			SELECT COUNT(*) FROM (
				SELECT hw.mac, hw.unique_id, hw.disk_id
				FROM hw_user hw
				GROUP BY hw.mac, hw.unique_id, hw.disk_id
				HAVING COUNT(DISTINCT hw.userid) > 1
			) AS subquery
		"));

		$approvedCount = current($GLOBALS['db']->fetch("
			SELECT COUNT(*) FROM (
				SELECT hw.mac, hw.unique_id, hw.disk_id
				FROM hw_user hw
				INNER JOIN shared_devices sd ON hw.mac = sd.mac AND hw.unique_id = sd.unique_id AND hw.disk_id = sd.disk_id
				GROUP BY hw.mac, hw.unique_id, hw.disk_id
				HAVING COUNT(DISTINCT hw.userid) > 1
			) AS subquery
		"));

		echo '<div class="row">';
		printAdminPanel('primary', 'fa fa-laptop fa-5x', $totalMultiUser, 'Hardware with Multiple Users');
		printAdminPanel('success', 'fa fa-check fa-5x', $approvedCount, 'Approved Shared Devices');
		printAdminPanel('warning', 'fa fa-exclamation-triangle fa-5x', $totalMultiUser - $approvedCount, 'Unapproved Devices');
		echo '</div>';

		// Hardware table
		echo '<br>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="text-center">Hardware ID</th>';
		echo '<th class="text-center">User Count</th>';
		echo '<th class="text-center">Status</th>';
		echo '<th class="text-center">Approved By</th>';
		echo '<th class="text-center">Approved At</th>';
		echo '<th class="text-center">Actions</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ($multiUserHardware as $hw) {
			$hwHash = substr($hw['mac'], 0, 8) . '...' . substr($hw['unique_id'], 0, 8) . '...' . substr($hw['disk_id'], 0, 8);
			$isShared = $hw['is_shared_device'] == 1;
			$statusColor = $isShared ? 'success' : 'warning';
			$statusText = $isShared ? 'Approved' : 'Unapproved';

			// Get admin username if approved
			$approvedBy = 'N/A';
			if ($hw['approved_by_admin_id']) {
				$adminData = $GLOBALS['db']->fetch(
					"SELECT username FROM users WHERE id = ? LIMIT 1",
					[$hw['approved_by_admin_id']]
				);
				if ($adminData) {
					$approvedBy = current($adminData);
				}
			}

			$approvedAt = $hw['approved_at'] ? date('Y-m-d H:i', strtotime($hw['approved_at'])) : 'N/A';

			echo '<tr>';
			echo '<td><code>' . htmlspecialchars($hwHash) . '</code></td>';
			echo '<td><a href="#" onclick="showHardwareUsers(\'' .
				htmlspecialchars($hw['mac']) . '\', \'' .
				htmlspecialchars($hw['unique_id']) . '\', \'' .
				htmlspecialchars($hw['disk_id']) .
				'\'); return false;"><strong>' . $hw['user_count'] . ' users</strong></a></td>';
			echo '<td><span class="label label-' . $statusColor . '">' . $statusText . '</span></td>';
			echo '<td>' . htmlspecialchars($approvedBy) . '</td>';
			echo '<td>' . htmlspecialchars($approvedAt) . '</td>';
			echo '<td>';

			if ($isShared) {
				// Show unapprove button
				echo '<button type="button" class="btn btn-sm btn-warning" onclick="unapproveSharedDevice(\'' .
					htmlspecialchars($hw['mac']) . '\', \'' .
					htmlspecialchars($hw['unique_id']) . '\', \'' .
					htmlspecialchars($hw['disk_id']) .
					'\')">Unapprove</button>';
			} else {
				// Show approve button
				echo '<button type="button" class="btn btn-sm btn-success" onclick="showApproveModal(\'' .
					htmlspecialchars($hw['mac']) . '\', \'' .
					htmlspecialchars($hw['unique_id']) . '\', \'' .
					htmlspecialchars($hw['disk_id']) .
					'\')">Approve</button>';
			}

			// View details button
			echo ' <button type="button" class="btn btn-sm btn-info" onclick="showHardwareDetails(\'' .
				htmlspecialchars($hw['mac']) . '\', \'' .
				htmlspecialchars($hw['unique_id']) . '\', \'' .
				htmlspecialchars($hw['disk_id']) . '\', \'' .
				htmlspecialchars($hw['approval_reason'] ?? '') .
				'\')">Details</button>';

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';

		// Modals for approve/details
		self::PrintSharedDeviceModals();

		echo '</div>';
		echo '</div>';
	}

	/**
	 * PrintSharedDeviceModals
	 * Prints modals for shared device management
	 */
	private static function PrintSharedDeviceModals()
	{
		// Approve modal
		echo '
		<div class="modal fade" id="approveSharedDeviceModal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
						<h4 class="modal-title">Approve Shared Device</h4>
					</div>
					<form action="submit.php" method="POST">
						<input name="csrf" type="hidden" value="' . csrfToken() . '">
						<input name="action" value="approveSharedDevice" type="hidden">
						<input name="mac" id="approve_mac" type="hidden">
						<input name="unique_id" id="approve_unique_id" type="hidden">
						<input name="disk_id" id="approve_disk_id" type="hidden">
						<div class="modal-body">
							<p>Are you sure you want to approve this hardware as a shared device?</p>
							<div class="form-group">
								<label>Reason (optional)</label>
								<textarea name="reason" class="form-control" rows="3"
									placeholder="E.g., Internet cafe, family computer, etc."></textarea>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-success">Approve</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Hardware Users Modal -->
		<div class="modal fade" id="hardwareUsersModal" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
						<h4 class="modal-title">Users on This Hardware</h4>
					</div>
					<div class="modal-body">
						<div id="hardwareUsersContent">
							<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Hardware Details Modal -->
		<div class="modal fade" id="hardwareDetailsModal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
						<h4 class="modal-title">Hardware Details</h4>
					</div>
					<div class="modal-body">
						<dl class="dl-horizontal">
							<dt>MAC Hash:</dt>
							<dd id="detail_mac"></dd>
							<dt>Unique ID Hash:</dt>
							<dd id="detail_unique_id"></dd>
							<dt>Disk ID Hash:</dt>
							<dd id="detail_disk_id"></dd>
							<dt>Approval Reason:</dt>
							<dd id="detail_reason"></dd>
						</dl>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>

		<script>
		function showApproveModal(mac, unique_id, disk_id) {
			$("#approve_mac").val(mac);
			$("#approve_unique_id").val(unique_id);
			$("#approve_disk_id").val(disk_id);
			$("#approveSharedDeviceModal").modal("show");
		}

		function unapproveSharedDevice(mac, unique_id, disk_id) {
			if (confirm("Are you sure you want to unapprove this shared device?")) {
				var form = document.createElement("form");
				form.method = "POST";
				form.action = "submit.php";

				var fields = {
					csrf: "' . csrfToken() . '",
					action: "unapproveSharedDevice",
					mac: mac,
					unique_id: unique_id,
					disk_id: disk_id
				};

				for (var key in fields) {
					var input = document.createElement("input");
					input.type = "hidden";
					input.name = key;
					input.value = fields[key];
					form.appendChild(input);
				}

				document.body.appendChild(form);
				form.submit();
			}
		}

		function showHardwareUsers(mac, unique_id, disk_id) {
			$("#hardwareUsersModal").modal("show");
			$("#hardwareUsersContent").html("<p class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> Loading...</p>");

			$.ajax({
				url: "api/get_hardware_users.php",
				method: "GET",
				data: { mac: mac, unique_id: unique_id, disk_id: disk_id },
				success: function(response) {
					$("#hardwareUsersContent").html(response);
				},
				error: function() {
					$("#hardwareUsersContent").html("<p class=\"text-danger\">Error loading users.</p>");
				}
			});
		}

		function showHardwareDetails(mac, unique_id, disk_id, reason) {
			$("#detail_mac").text(mac);
			$("#detail_unique_id").text(unique_id);
			$("#detail_disk_id").text(disk_id);
			$("#detail_reason").text(reason || "N/A");
			$("#hardwareDetailsModal").modal("show");
		}
		</script>';
	}
}
