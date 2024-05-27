<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database config
define('DATABASE_HOST', getenv('DATABASE_HOST'));	// MySQL host. usually localhost
define('DATABASE_USER', getenv('DATABASE_USER'));		// MySQL username
define('DATABASE_PASS', getenv('DATABASE_PASS'));		// MySQL password
define('DATABASE_NAME', getenv('DATABASE_NAME'));		// Database name
define('DATABASE_WHAT', getenv('DATABASE_WHAT'));		// "host" or unix socket path

define('DISCORD_WEBHOOK_URL', getenv("DISCORD_WEBHOOK_URL"));

// Server urls, no slash
$URL = [
	'avatar' => getenv('AVATAR_BASE_URL'), // 'https://a.akatsuki.pw'
	'server' => getenv('FRONTEND_BASE_URL'), // 'https://akatsuki.pw',
	'bancho' => getenv('BANCHO_BASE_URL'), // 'http://c.akatsuki.pw',
];

// S3 config
$S3Config = [
	'access_key' => getenv('AWS_ACCESS_KEY_ID'),
	'secret_key' => getenv('AWS_SECRET_ACCESS_KEY'),
	'region' => getenv('AWS_DEFAULT_REGION'),
	'bucket' => getenv('AWS_BUCKET_NAME'),
	'endpoint_url' => getenv('AWS_ENDPOINT_URL'),
];

// Scores/PP config
$ScoresConfig = [
	"enablePP" => true,
	"useNewBeatmapsTable" => true,		// 0: get beatmaps names from beatmaps_names (old php scores server)
										// 1: get beatmaps names from beatmaps (LETS)
	"api_key" => "",
	"rankRequestsQueueSize" => 20,
	"rankRequestsPerUser" => 2
];

// ip env (ip fix with caddy)
$ipEnv = 'REMOTE_ADDR';	// HTTP_X_FORWARDED_FOR
