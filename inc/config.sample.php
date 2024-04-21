<?php
// Database config
define('DATABASE_HOST', 'localhost');	// MySQL host. usually localhost
define('DATABASE_USER', 'root');		// MySQL username
define('DATABASE_PASS', 'changeme');		// MySQL password
define('DATABASE_NAME', 'akatsuki');		// Database name
define('DATABASE_WHAT', 'host');		// "host" or unix socket path

define('DISCORD_WEBHOOK_URL', 'https://canary.discord.com/api/webhooks/xxxxxxxxx/xxxxxxxxxxxx');

// S3 config
$S3Config = [
	'region' => 'ca-central-1',
	'bucket' => 'akatsuki.pw',
	'endpoint_url' => 'https://s3.ca-central-1.wasabisys.com',
	'access_key_id' => '',
	'secret_access_key' => ''
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
