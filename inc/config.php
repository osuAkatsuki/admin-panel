<?php
$df = dirname(__FILE__);
require_once $df . '/../vendor/vlucas/phpdotenv/src/Dotenv.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Database config
define('DATABASE_HOST', $_ENV['DATABASE_HOST']);	// MySQL host. usually localhost
define('DATABASE_USER', $_ENV['DATABASE_USER']);		// MySQL username
define('DATABASE_PASS', $_ENV['DATABASE_PASS']);		// MySQL password
define('DATABASE_NAME', $_ENV['DATABASE_NAME']);		// Database name
define('DATABASE_WHAT', $_ENV['DATABASE_WHAT']);		// "host" or unix socket path

define('REDIS_HOST', $_ENV['REDIS_HOST']);
define('REDIS_PORT', $_ENV['REDIS_PORT']);

define('DISCORD_WEBHOOK_URL', $_ENV["DISCORD_WEBHOOK_URL"]);

// Server urls, no slash
$PUBLIC_AVATARS_SERVICE_BASE_URL = $_ENV['PUBLIC_AVATARS_SERVICE_BASE_URL'];
$INTERNAL_BANCHO_SERVICE_BASE_URL = $_ENV['INTERNAL_BANCHO_SERVICE_BASE_URL'];
$INTERNAL_USERS_SERVICE_BASE_URL = $_ENV['INTERNAL_USERS_SERVICE_BASE_URL'];

// S3 config
$S3Config = [
	'access_key_id' => $_ENV['AWS_ACCESS_KEY_ID'],
	'secret_access_key' => $_ENV['AWS_SECRET_ACCESS_KEY'],
	'region' => $_ENV['AWS_DEFAULT_REGION'],
	'bucket' => $_ENV['AWS_BUCKET_NAME'],
	'endpoint_url' => $_ENV['AWS_ENDPOINT_URL'],
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

// Session configuration - use Redis for session storage
try {
    // Redis is available, use it for sessions
    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', 'tcp://' . REDIS_HOST . ':' . REDIS_PORT);
} catch (Exception $e) {
    // Redis not available, fall back to files
    error_log('Redis not available for sessions, falling back to files: ' . $e->getMessage());
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', '/tmp/php_sessions');
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
