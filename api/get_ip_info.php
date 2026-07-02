<?php
require_once '../inc/functions.php';

startSessionIfNotStarted();

header('Content-Type: application/json; charset=utf-8');

function ipInfoError($statusCode, $summary, $title)
{
	http_response_code($statusCode);
	echo json_encode(['summary' => $summary, 'title' => $title]);
	exit;
}

function proxycheckBool($value)
{
	return $value === true ? 'yes' : 'no';
}

function proxycheckString($value, $fallback = 'unknown')
{
	return is_string($value) && $value !== '' ? $value : $fallback;
}

if (!isset($_SESSION['username']) || !isset($_SESSION['userid']) || !isset($_SESSION['password'])) {
	ipInfoError(401, 'auth required', 'You must be logged in to view IP intelligence');
}

$sessionPassword = current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username']));
if ($sessionPassword != $_SESSION['password']) {
	ipInfoError(401, 'session expired', 'Your admin session has expired');
}

if (
	!hasPrivilege(Privileges::AdminAccessRAP) ||
	!hasPrivilege(Privileges::UserPublic) ||
	!hasPrivilege(Privileges::UserNormal) ||
	!hasPrivilege(Privileges::AdminManagePrivileges)
) {
	ipInfoError(403, 'forbidden', 'You do not have permission to view IP intelligence');
}

if (!isset($_GET['ip']) || !filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
	ipInfoError(400, 'dunno', 'Invalid IP address');
}

$ip = $_GET['ip'];
$queryParams = [];
if (!empty($_ENV['PROXYCHECK_API_KEY'])) {
	$queryParams['key'] = $_ENV['PROXYCHECK_API_KEY'];
}

$url = 'https://proxycheck.io/v3/' . rawurlencode($ip);
if (!empty($queryParams)) {
	$url .= '?' . http_build_query($queryParams);
}

$response = get_contents_http($url);
$data = json_decode((string) $response, true);
$ipInfo = is_array($data) && isset($data[$ip]) && is_array($data[$ip]) ? $data[$ip] : null;

if (!$ipInfo) {
	echo json_encode(['summary' => 'dunno', 'title' => 'IP intelligence lookup failed']);
	exit;
}

$location = isset($ipInfo['location']) && is_array($ipInfo['location']) ? $ipInfo['location'] : [];
$network = isset($ipInfo['network']) && is_array($ipInfo['network']) ? $ipInfo['network'] : [];
$detections = isset($ipInfo['detections']) && is_array($ipInfo['detections']) ? $ipInfo['detections'] : [];
$operator = isset($ipInfo['operator']) && is_array($ipInfo['operator']) ? $ipInfo['operator'] : [];

$countryCode = proxycheckString($location['country_code'] ?? null, '??');
$networkType = proxycheckString($network['type'] ?? null);
$risk = isset($detections['risk']) ? $detections['risk'] : '?';

$summary = sprintf(
	'%s | %s | Proxy %s | VPN %s | Tor %s | Risk %s',
	$countryCode,
	$networkType,
	proxycheckBool($detections['proxy'] ?? null),
	proxycheckBool($detections['vpn'] ?? null),
	proxycheckBool($detections['tor'] ?? null),
	$risk
);

$titleParts = [
	'Country: ' . proxycheckString($location['country_name'] ?? null, $countryCode),
	'Region: ' . proxycheckString($location['region_name'] ?? null),
	'City: ' . proxycheckString($location['city_name'] ?? null),
	'Provider: ' . proxycheckString($network['provider'] ?? null),
	'Network type: ' . $networkType,
	'Hosting: ' . proxycheckBool($detections['hosting'] ?? null),
	'Anonymous: ' . proxycheckBool($detections['anonymous'] ?? null),
	'Confidence: ' . (isset($detections['confidence']) ? $detections['confidence'] : '?'),
];

if (!empty($operator['name'])) {
	$titleParts[] = 'Operator: ' . $operator['name'];
}
if (!empty($operator['services']) && is_array($operator['services'])) {
	$titleParts[] = 'Operator services: ' . implode(', ', $operator['services']);
}

echo json_encode([
	'summary' => $summary,
	'title' => implode(' | ', $titleParts),
]);
