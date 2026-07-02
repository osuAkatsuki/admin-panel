<?php
require_once '../inc/functions.php';

startSessionIfNotStarted();
sessionCheckAdmin(Privileges::AdminManagePrivileges);

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['ip']) || !filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
	http_response_code(400);
	die('dunno');
}

$ip = $_GET['ip'];
$country = trim((string) get_contents_http('https://ip.zxq.co/' . rawurlencode($ip) . '/country'));

echo preg_match('/^[A-Z]{2}$/', $country) ? $country : 'dunno';
