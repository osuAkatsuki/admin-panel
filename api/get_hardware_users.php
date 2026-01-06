<?php
require_once '../inc/functions.php';

startSessionIfNotStarted();
sessionCheckAdmin(Privileges::AdminManageUsers);

if (!isset($_GET['mac']) || !isset($_GET['unique_id']) || !isset($_GET['disk_id'])) {
	die('<p class="text-danger">Invalid parameters</p>');
}

$mac = $_GET['mac'];
$unique_id = $_GET['unique_id'];
$disk_id = $_GET['disk_id'];

$users = $GLOBALS['db']->fetchAll("
	SELECT
		u.id,
		u.username,
		u.privileges,
		SUM(hw.occurencies) AS total_occurrences,
		MAX(hw.activated) AS has_activated,
		MAX(hw.created_at) AS last_used
	FROM hw_user hw
	INNER JOIN users u ON hw.userid = u.id
	WHERE hw.mac = ? AND hw.unique_id = ? AND hw.disk_id = ?
	GROUP BY u.id, u.username, u.privileges
	ORDER BY total_occurrences DESC
", [$mac, $unique_id, $disk_id]);

if (empty($users)) {
	echo '<p class="text-center">No users found for this hardware.</p>';
	exit;
}

echo '<table class="table table-striped table-hover">';
echo '<thead><tr><th>User ID</th><th>Username</th><th>Occurrences</th><th>Last Used</th><th>Status</th></tr></thead>';
echo '<tbody>';

foreach ($users as $user) {
	$statusColor = ($user['privileges'] & 1) ? 'success' : 'danger';
	$statusText = ($user['privileges'] & 1) ? 'Active' : 'Restricted/Banned';
	$lastUsed = date('Y-m-d H:i', strtotime($user['last_used']));

	echo '<tr>';
	echo '<td><a href="index.php?p=103&id=' . $user['id'] . '">' . $user['id'] . '</a></td>';
	echo '<td>' . htmlspecialchars($user['username']) . '</td>';
	echo '<td>' . $user['total_occurrences'] . '</td>';
	echo '<td>' . $lastUsed . '</td>';
	echo '<td><span class="label label-' . $statusColor . '">' . $statusText . '</span></td>';
	echo '</tr>';
}

echo '</tbody></table>';
