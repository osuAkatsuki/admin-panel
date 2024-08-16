<?php

// We aren't calling the class Do because otherwise it would conflict with do { } while ();
class D
{
	/*
	 * SaveSystemSettings
	 * Save system settings function (ADMIN CP)
	 */
	public static function SaveSystemSettings()
	{
		try {
			// Get values
			if (isset($_POST['wm'])) {
				$wm = $_POST['wm'];
			} else {
				$wm = 0;
			}
			if (isset($_POST['gm'])) {
				$gm = $_POST['gm'];
			} else {
				$gm = 0;
			}
			if (isset($_POST['r'])) {
				$r = $_POST['r'];
			} else {
				$r = 0;
			}
			if (!empty($_POST['ga'])) {
				$ga = $_POST['ga'];
			} else {
				$ga = '';
			}
			if (!empty($_POST['ha'])) {
				$ha = $_POST['ha'];
			} else {
				$ha = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'website_maintenance' LIMIT 1", [$wm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'game_maintenance' LIMIT 1", [$gm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'registrations_enabled' LIMIT 1", [$r]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_global_alert' LIMIT 1", [$ga]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_home_alert' LIMIT 1", [$ha]);
			// RAP log
			postWebhookMessage("has updated system settings.\n\n> :gear: Visit [System Settings](https://old.akatsuki.gg/index.php?p=101) page on **Admin Panel**");
			rapLog("has updated system settings");
			// Done, redirect to success page
			redirect('index.php?p=101&s=Settings saved!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=101&e=' . $e->getMessage());
		}
	}

	/*
	 * SaveBanchoSettings
	 * Save bancho settings function (ADMIN CP)
	 */
	public static function SaveBanchoSettings()
	{
		try {
			// Get values
			if (isset($_POST['bm'])) {
				$bm = $_POST['bm'];
			} else {
				$bm = 0;
			}
			if (isset($_POST['od'])) {
				$od = $_POST['od'];
			} else {
				$od = 0;
			}
			if (isset($_POST['rm'])) {
				$rm = $_POST['rm'];
			} else {
				$rm = 0;
			}
			if (!empty($_POST['lm'])) {
				$lm = $_POST['lm'];
			} else {
				$lm = '';
			}
			if (!empty($_POST['ln'])) {
				$ln = $_POST['ln'];
			} else {
				$ln = '';
			}
			if (!empty($_POST['cv'])) {
				$cv = $_POST['cv'];
			} else {
				$cv = '';
			}
			if (!empty($_POST['cmd5'])) {
				$cmd5 = $_POST['cmd5'];
			} else {
				$cmd5 = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'bancho_maintenance' LIMIT 1", [$bm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'free_direct' LIMIT 1", [$od]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'restricted_joke' LIMIT 1", [$rm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_messages' LIMIT 1", [$lm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_notification' LIMIT 1", [$ln]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_versions' LIMIT 1", [$cv]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_md5s' LIMIT 1", [$cmd5]);
			// Pubsub
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:reload_settings", "reload");
			// Rap log
			postWebhookMessage("has updated Bancho settings.\n\n> :gear: Visit [Bancho Settings](https://old.akatsuki.gg/index.php?p=111) page on **Admin Panel**");
			rapLog("has updated Bancho settings");
			// Done, redirect to success page
			redirect('index.php?p=111&s=Settings saved!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=111&e=' . $e->getMessage());
		}
	}

	/*
	 * SaveEditUser
	 * Save edit user function (ADMIN CP)
	 */
	public static function SaveEditUser()
	{
		try {
			// Check if everything is set (username color, username style, rank, allowed and notes can be empty)
			if (!isset($_POST['id']) || !isset($_POST['u']) || !isset($_POST['up']) || !isset($_POST['aka']) || empty($_POST['id']) || empty($_POST['u'])) {
				throw new Exception('Nice troll');
			}
			// Check if this user exists and get old data
			$oldData = $GLOBALS["db"]->fetch("SELECT privileges, country FROM users WHERE id = ?", [$_POST["id"]]);
			if (!$oldData) {
				throw new Exception("That user doesn\'t exist");
			}
			// Check if we can edit this user
			if ((($oldData["privileges"] & Privileges::AdminManageUsers) > 0) && $_POST["u"] != $_SESSION["username"] && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to edit this user");
			}

			// Check if silence end has changed. if so, we have to kick the client
			// in order to silence him
			//$oldse = current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE username = ?", array($_POST["u"])));

			// Save new data (email, and cm notes)
			$GLOBALS['db']->execute('UPDATE users SET notes = ? WHERE id = ? LIMIT 1', [$_POST['ncm'], $_POST['id']]);
			// Edit silence time if we can silence users
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				$GLOBALS['db']->execute('UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1', [$_POST['se'], $_POST['sr'], $_POST['id']]);
			}
			// Edit privileges if we can
			if (hasPrivilege(Privileges::AdminManagePrivileges) && ($_POST["id"] != $_SESSION["userid"])) {
				$GLOBALS['db']->execute('UPDATE users SET privileges = ? WHERE id = ? LIMIT 1', [$_POST['priv'], $_POST['id']]);
				updateBanBancho($_POST["id"], $_POST['priv'] & Privileges::UserPublic == 0);
			}
			// Save new userpage
			$GLOBALS['db']->execute('UPDATE users SET userpage_content = ? WHERE id = ? LIMIT 1', [$_POST['up'], $_POST['id']]);
			// Update country flag if set
			if (isset($_POST['country']) && countryCodeToReadable($_POST['country']) != 'unknown country' && $oldData["country"] != $_POST['country']) {
				$GLOBALS['db']->execute('UPDATE users SET country = ? WHERE id = ?', [$_POST['country'], $_POST['id']]);
				redisConnect();
				$GLOBALS["redis"]->publish('api:change_flag', $_POST['id']);

				postWebhookMessage(sprintf("has changed [%s](https://akatsuki.gg/u/%s)'s flag to :flag_%s:", $_POST["u"], $_POST['id'], strtolower($_POST['country'])));
				rapLog(sprintf("has changed %s's flag to %s", $_POST["u"], $_POST['country']));
			}
			// Set username style/color/aka
			$GLOBALS['db']->execute('UPDATE users SET username_aka = ? WHERE id = ?', [$_POST['aka'], $_POST['id']]);
			// RAP log
			postWebhookMessage(sprintf("has edited [%s](https://akatsuki.gg/u/%s)\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $_POST["u"], $_POST['id'], $_POST['id']));
			rapLog(sprintf("has edited user %s", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User edited!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * BanUnbanUser
	 * Ban/Unban user function (ADMIN CP)
	 */
	public static function BanUnbanUser()
	{
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if (($userData["privileges"] & Privileges::UserNormal) > 0) {
				// Ban, reset UserNormal and UserPublic bits
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] & ~Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Unban, set UserNormal and UserPublic bits
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
			}
			//$newPrivileges = $userData["privileges"] ^ Privileges::UserBasic;
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"], $newPrivileges & Privileges::UserPublic == 0);
			// Rap log
			postWebhookMessage(sprintf("has %s user [%s](https://akatsuki.gg/u/%s).\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", ($newPrivileges & Privileges::UserNormal) > 0 ? "unbanned" : "banned", $userData["username"], $_GET['id'], $_POST['id']));
			rapLog(sprintf("has %s user %s", ($newPrivileges & Privileges::UserNormal) > 0 ? "unbanned" : "banned", $userData["username"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User banned/unbanned/activated!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * QuickEditUser
	 * Redirects to the edit user page for the user with $_POST["u"] username
	 */
	public static function QuickEditUser($email = false)
	{
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch(sprintf('SELECT id FROM users WHERE %s = ? LIMIT 1', $email ? 'email' : 'username'), [$_POST['u']]));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=103&id=' . $id);
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * QuickEditUserBadges
	 * Redirects to the edit user badges page for the user with $_POST["u"] username
	 */
	public static function QuickEditUserBadges()
	{
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=110&id=' . $id);
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}

	/*
	 * ChangeIdentity
	 * Change identity function (ADMIN CP)
	 */
	public static function ChangeIdentity()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['oldu']) || !isset($_POST['newu']) || empty($_POST['id']) || empty($_POST['oldu']) || empty($_POST['newu'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we can edit this user
			$privileges = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$privileges) {
				throw new Exception("User doesn't exist");
			}
			$privileges = current($privileges);
			if ((($privileges & Privileges::AdminManageUsers) > 0) && $_POST['oldu'] != $_SESSION['username'] && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to edit this user");
			}
			// No username with mixed spaces
			if (strpos($_POST["newu"], " ") !== false && strpos($_POST["newu"], "_") !== false) {
				throw new Exception('Usernames with both spaces and underscores are not supported.');
			}
			// Check if username is already in db
			$safe = safeUsername($_POST["newu"]);
			$trimmedName = trim($_POST["newu"]);
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE username_safe = ? AND id != ? LIMIT 1', [$safe, $_POST["id"]])) {
				throw new Exception('Username already used by another user. No changes have been made.');
			}

			// Send username change work to pep.py
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:change_username", json_encode([
				"userID" => intval($_POST["id"]),
				"newUsername" => $trimmedName
			]));

			$GLOBALS["redis"]->publish("api:change_username", $_POST["id"]);

			// log this username change to the users rap notes
			appendNotes($_POST["id"], sprintf("Username change: '%s' -> '%s'", $_POST["oldu"], $trimmedName));

			// rap log
			postWebhookMessage(sprintf("has changed %s's username to [%s](https://akatsuki.gg/u/%s).\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $_POST["oldu"], $trimmedName, $_POST["id"]));
			rapLog(sprintf("has changed %s's username to %s", $_POST["oldu"], $_POST["newu"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User identity changed! It might take a while to change the username if the user is online on Bancho.');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * ChangeWhitelist
	 * Change whitelist function (ADMIN CP)
	 */
	public static function ChangeWhitelist()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['newwhitelist']) || empty($_POST['id'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we can edit this user
			$privileges = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$privileges) {
				throw new Exception("User doesn't exist");
			}
			$privileges = current($privileges);
			if ((($privileges & Privileges::AdminManageUsers) > 0) && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to edit this user");
			}

			// whitelist must be a value between 0 and 3
			if ($_POST["newwhitelist"] < 0 || $_POST["newwhitelist"] > 3) {
				throw new Exception("Invalid whitelist value");
			}

			$GLOBALS['db']->execute('UPDATE users SET whitelist = ? WHERE id = ?', [$_POST['newwhitelist'], $_POST["id"]]);

			// log this whitelist change to the users rap notes
			appendNotes($_POST["id"], sprintf("Whitelist change: '%s' -> '%s'", $_SESSION['whitelist'], $_POST['newwhitelist']));

			// rap log
			postWebhookMessage(sprintf("has changed %s's whitelist to [%s](https://akatsuki.gg/u/%s).\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $_SESSION['whitelist'], $_POST['newwhitelist'], $_POST["id"]));
			rapLog(sprintf("has changed %s's whitelist to %s", $_SESSION['whitelist'], $_POST["newwhitelist"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User whitelist changed! It might take a while to change the whitelist if the user is online on Bancho.');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * SaveBadge
	 * Save badge function (ADMIN CP)
	 */
	public static function SaveBadge()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['n']) || !isset($_POST['i']) || !isset($_POST['c']) || empty($_POST['n']) || empty($_POST['i'])) {
				throw new Exception('Nice troll.');
			}

			// Check if we are creating or editing a doc page
			if ($_POST['id'] == 0) {
				if (empty($_POST['c'])) {
					$GLOBALS['db']->execute('INSERT INTO badges (id, name, icon, colour) VALUES (NULL, ?, ?, NULL)', [$_POST['n'], $_POST['i']]);
				} else {
					$GLOBALS['db']->execute('INSERT INTO badges (id, name, icon, colour) VALUES (NULL, ?, ?, ?)', [$_POST['n'], $_POST['i'], $_POST['c']]);
				}
			} else {
				if (empty($_POST['c'])) {
					$GLOBALS['db']->execute('UPDATE badges SET name = ?, icon = ?, colour = NULL WHERE id = ? LIMIT 1', [$_POST['n'], $_POST['i'], $_POST['id']]);
				} else {
					$GLOBALS['db']->execute('UPDATE badges SET name = ?, icon = ?, colour = ? WHERE id = ? LIMIT 1', [$_POST['n'], $_POST['i'], $_POST['c'], $_POST['id']]);
				}
			}
			// RAP log
			postWebhookMessage(sprintf("has %s badge %s.\n\n> :gear: [View all badges](https://old.akatsuki.gg/index.php?p=109) on **Admin Panel**", $_POST['id'] == 0 ? "created" : "edited", $_POST["n"], $_POST['id']));
			rapLog(sprintf("has %s badge %s", $_POST['id'] == 0 ? "created" : "edited", $_POST["n"]));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}

	/*
	 * SaveUserBadges
	 * Save user badges function (ADMIN CP)
	 */
	public static function SaveUserBadges()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['b01']) || !isset($_POST['b02']) || !isset($_POST['b03']) || !isset($_POST['b04']) || !isset($_POST['b05']) || !isset($_POST['b06']) || empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			$user = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']);
			// Make sure that this user exists
			if (!$user) {
				throw new Exception("That user doesn't exist.");
			}
			// delete current badges
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ?", [$user["id"]]);
			// add badges
			for ($i = 0; $i <= 6; $i++) {
				$x = $_POST["b0" . $i];
				if ($x == 0) continue;
				$GLOBALS["db"]->execute("INSERT INTO user_badges(user, badge) VALUES (?, ?);", [$user["id"], $x]);
			}
			// RAP log
			postWebhookMessage(sprintf("has edited [%s](https://akatsuki.gg/u/%s)'s badges.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $_POST["u"], $user["id"], $_POST['id']));
			rapLog(sprintf("has edited %s's badges", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}

	/*
	 * RemoveBadge
	 * Remove badge function (ADMIN CP)
	 */
	public static function RemoveBadge()
	{
		try {
			// Make sure that this is not the "None badge"
			if (empty($_GET['id'])) {
				throw new Exception("You can't delete this badge.");
			}
			// Make sure that this badge exists
			$name = $GLOBALS['db']->fetch('SELECT name FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// Badge doesn't exists wtf
			if (!$name) {
				throw new Exception("This badge doesn't exists");
			}
			// Delete badge
			$GLOBALS['db']->execute('DELETE FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// delete badge from relationships table
			$GLOBALS['db']->execute('DELETE FROM user_badges WHERE badge = ?', $_GET['id']);
			// RAP log
			postWebhookMessage(sprintf("has deleted badge %s.\n\n> :gear: [View all badges](https://old.akatsuki.gg/index.php?p=109) on **Admin Panel**", current($name)));
			rapLog(sprintf("has deleted badge %s", current($name)));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge deleted!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}

	/*
	 * SilenceUser
	 * Silence someone (ADMIN CP)
	 */
	public static function silenceUser()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['c']) || !isset($_POST['un']) || !isset($_POST['r']) || !isset($_POST["r"]) || empty($_POST['u']) || empty($_POST['un']) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = getUserID($_POST["u"]);
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Calculate silence period length
			$sl = $_POST['c'] * $_POST['un'];
			// Make sure silence time is less than 30 days
			if ($sl > 2592000) {
				throw new Exception('Invalid silence length. Maximum silence length is 30 days.');
			}
			// Silence and reconnect that user
			$GLOBALS["db"]->execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1", [time() + $sl, $_POST["r"], $id]);
			updateSilenceBancho($id);
			// RAP log and redirect
			if ($sl > 0) {
				postWebhookMessage(sprintf("has silenced user [%s](https://akatsuki.gg/u/%s) for %s.\n**Reason**: \"%s\"\n\n\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $_POST['u'], $id, timeDifference(time() + $sl, time(), false), $_POST["r"], $_POST['id']));
				rapLog(sprintf("has silenced user %s for %s for the following reason: \"%s\"", $_POST['u'], timeDifference(time() + $sl, time(), false), $_POST["r"]));
				$msg = 'index.php?p=102&s=User silenced!';
			} else {
				postWebhookMessage(sprintf("has removed [%s](https://akatsuki.gg/u/%s)'s silence", $_POST['u'], $id));
				rapLog(sprintf("has removed %s's silence", $_POST['u']));
				$msg = 'index.php?p=102&s=User silence removed!';
			}
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=' . $msg);
			} else {
				redirect('index.php?p=102&s=' . $msg);
			}
		} catch (Exception $e) {
			// Redirect to Exception page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e=' . $e->getMessage());
			} else {
				redirect('index.php?p=102&e=' . $e->getMessage());
			}
		}
	}

	/*
	 * KickUser
	 * Kick someone from bancho (ADMIN CP)
	 */
	public static function KickUser()
	{
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || empty($_POST['u']) || !isset($_POST["r"]) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Kick that user
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:disconnect", json_encode([
				"userID" => intval($id),
				"reason" => $_POST["r"]
			]));
			// Rap log
			postWebhookMessage(sprintf("has kicked [%s](https://akatsuki.gg/u/%s) from the server.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", getUserUsername($_GET['id']), $_GET['id'], $_POST['id']));
			rapLog(sprintf("has kicked %s from the server", getUserUsername($_GET['id'])));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User kicked!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * ResetAvatar
	 * Reset soneone's avatar (ADMIN CP)
	 */
	public static function ResetAvatar()
	{
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid request');
			}
			global $S3Config;
			// Remove the avatar file from S3
			$GLOBALS["s3"]->deleteObject([
				"Bucket" => $S3Config["bucket"],
				"Key" => "avatars/" . $_GET["id"] . ".png"
			]);
			// Rap log
			postWebhookMessage(sprintf("has reset [%s](https://akatsuki.gg/u/%s)'s Avatar\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", getUserUsername($_GET['id']), $_GET['id'], $_POST['id']));
			rapLog(sprintf("has reset %s's Avatar", getUserUsername($_GET['id'])));
			// Done, redirect to success page
			redirect('index.php?p=102&s=Avatar reset!');
		} catch (Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	/*
	 * Logout
	 * Logout and return to home
	 */
	public static function Logout()
	{
		// Logging out without being logged in doesn't make much sense
		if (checkLoggedIn()) {
			startSessionIfNotStarted();
			if (isset($_COOKIE['sli'])) {
				$rch = new RememberCookieHandler();
				$rch->Destroy();
			}
			$_SESSION = [];
			session_unset();
			session_destroy();
		} else {
			// Uhm, some kind of error/h4xx0r. Let's return to login page just because yes.
			redirect('index.php?p=2');
		}
	}


	/*
	 * WipeAccount
	 * Wipes an account
	 */
	public static function WipeAccount()
	{
		try {
			if (!isset($_POST['id']) || empty($_POST['id'])) {
				throw new Exception('Invalid request');
			}
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception("User doesn't exist.");
			}
			$username = $userData["username"];
			// Check if we can wipe this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to wipe this account");
			}

			if ($_POST["gm"] == -1) { // All modes
				$modes = ['std', 'taiko', 'ctb', 'mania'];
			} else { // Single mode
				if ($_POST["gm"] == 0) {
					$modes = ['std'];
				} else if ($_POST["gm"] == 1) {
					$modes = ['taiko'];
				} else if ($_POST["gm"] == 2) {
					$modes = ['ctb'];
				} else if ($_POST["gm"] == 3) {
					$modes = ['mania'];
				}
			}

			if ($_POST["rx"] == 1) {
				$scores_table = "scores_relax";
			} else if ($_POST["rx"] == 2) {
				$scores_table = "scores_ap";
			} else if ($_POST["rx"] == 0) {
				$scores_table = "scores";
			}

			redisConnect();

			// Delete scores
			if ($_POST["gm"] == -1) {

				if ($_POST["rx"] != 3) {
					$GLOBALS['db']->execute('DELETE FROM ' . $scores_table . ' WHERE userid = ?', [$_POST['id']]);
					foreach (range(0, 3) as $i) {
						$GLOBALS["redis"]->publish("peppy:wipe", $_POST['id'] . ',' . $_POST['rx'] . ',' . $i);
					}
				} else {
					$dt = ['scores', 'scores_relax', 'scores_ap'];
					foreach ($dt as $st) {
						$GLOBALS['db']->execute('DELETE FROM' . $st . ' WHERE userid = ?', [$_POST['id']]);
						foreach (range(0, 3) as $i) {
							foreach ([0, 1, 2] as $m) {
								$GLOBALS["redis"]->publish("peppy:wipe", $_POST['id'] . ',' . $m . ',' . $i);
							}
						}
					}
				}
			} else {
				if ($_POST["rx"] == 3) {
					$dt = ['scores', 'scores_relax', 'scores_ap'];
					$ms = [0, 1, 2];
				} else {
					$dt = [$scores_table];
					$ms = [$_POST["rx"]];
				}

				foreach ($dt as $st) {
					// TODO: we should not be hard deleting scores, but either marking them as "inactive"
					// or moving them to another table (e.g. insert into select * from ...)
					$GLOBALS['db']->execute('DELETE FROM ' . $st . ' WHERE userid = ? AND play_mode = ?', [$_POST['id'], $_POST["gm"]]);
				}

				foreach ($ms as $m) {
					$GLOBALS["redis"]->publish("peppy:wipe", $_POST['id'] . ',' . $m . ',' . $_POST['gm']);
				}
			}

			// Next, on the new tables
			if ($_POST["gm"] == -1) { // All modes
				if ($_POST["rx"] == 0) {
					$modeInts = [0, 1, 2, 3];
				} else if ($_POST["rx"] == 1) {
					$modeInts = [4, 5, 6];
				} else if ($_POST["rx"] == 2) {
					$modeInts = [8];
				}
				$modeInts = [0, 1, 2, 3];
			} else if ((0 <= $_POST["gm"]) && ($_POST["gm"] <= 3)) { // Single mode
				if ($_POST["rx"] == 0) {
					$modeInts = [$_POST["gm"]];
				} else if ($_POST["rx"] == 1) {
					if ($_POST["gm"] == 3) {
						throw new Exception("Relax does not support mania");
					}
					$modeInts = [$_POST["gm"] + 4];
				} else if ($_POST["rx"] == 2) {
					if ($_POST["gm"] != 0) {
						throw new Exception("Autopilot only supports standard");
					}
					$modeInts = [$_POST["gm"] + 8];
				}
			}
			foreach ($modeInts as $modeInt) {
				$GLOBALS['db']->execute(
					'
					UPDATE user_stats
					   SET max_combo = 0,
					       ranked_score = 0,
					       total_score = 0,
					       replays_watched = 0,
					       playcount = 0,
					       avg_accuracy = 0.0,
					       total_hits = 0,
						   level = 0,
						   pp = 0
					 WHERE user_id = ?
					   AND mode = ?',
					[$_POST['id'], $modeInt]
				);
			}

			// RAP log
			postWebhookMessage(sprintf("has wiped [%s](https://akatsuki.gg/u/%s)'s account.", $username, $_POST["id"]));
			rapLog(sprintf("has wiped %s's account", $username));

			// Done
			$wipeText = "Vanilla";

			if ($_POST["rx"] == 3) {
				$wipeText = "Vanilla, Relax and Autopilot";
			} else if ($_POST["rx"] == 2) {
				$wipeText = "Autopilot";
			} else if ($_POST["rx"] == 1) {
				$wipeText = "Relax";
			}

			redirect('index.php?p=102&s=User ' . $wipeText . ' scores and stats have been wiped!');
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}


	/*
	 * ProcessRankRequest
	 * Rank/unrank a beatmap
	 */
	public static function ProcessRankRequest()
	{
		global $INTERNAL_BANCHO_SERVICE_BASE_URL;
		global $ScoresConfig;
		try {
			if (!isset($_GET["id"]) || !isset($_GET["r"]) || empty($_GET["id"]))
				throw new Exception("no");

			// Get beatmapset id
			$requestData = $GLOBALS["db"]->fetch("SELECT * FROM rank_requests WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$requestData)
				throw new Exception("Rank request not found");

			if ($requestData["type"] == "s") {
				// We already have the beatmapset id
				$bsid = $requestData["bid"];
			} else {
				// We have the beatmap but we don't have the beatmap set id.
				$result = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$requestData["bid"]]);
				if (!$result)
					throw new Exception("Beatmap set id not found. Load the beatmap ingame and try again.");
				$bsid = current($result);
			}

			// TODO: Save all beatmaps from a set in db with a given beatmap set id

			if ($_GET["r"] == 0) {
				// Unrank the map set and force osu!api update by setting latest update to 01/01/1970 top stampa piede
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0, latest_update = 0 WHERE beatmapset_id = ?", [$bsid]);
			} else {
				// Rank the map set and freeze status rank
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmapset_id = ?", [$bsid]);

				// send a message to #announce
				$bm = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name FROM beatmaps WHERE beatmapset_id = ? LIMIT 1", [$bsid]);

				$msg = "[https://osu.ppy.sh/s/" . $bsid . " " . $bm["song_name"] . "] is now ranked!";
				$to = "#announce";
				$requesturl = $INTERNAL_BANCHO_SERVICE_BASE_URL . "/api/v1/fokabotMessage?k=" . urlencode($ScoresConfig["api_key"]) . "&to=" . urlencode($to) . "&msg=" . urlencode($msg);
				$resp = getJsonCurl($requesturl);

				if ($resp["message"] != "ok") {
					postWebhookMessage("failed to send FokaBot message :( Error: " . print_r($resp["message"], true));
					rapLog("failed to send FokaBot message :( Error: " . print_r($resp["message"], true));
				}
			}

			// RAP log
			postWebhookMessage(sprintf("has %s beatmap set %s", $_GET["r"] == 0 ? "unranked" : "ranked", $bsid));
			rapLog(sprintf("has %s beatmap set %s", $_GET["r"] == 0 ? "unranked" : "ranked", $bsid), $_SESSION["userid"]);

			// Done
			redirect("index.php?p=117&s=野生のちんちんが現れる");
		} catch (Exception $e) {
			redirect("index.php?p=117&e=" . $e->getMessage());
		}
	}


	public static function savePrivilegeGroup()
	{
		try {
			// Args check
			if (!isset($_POST["id"]) || !isset($_POST["n"]) || !isset($_POST["priv"]) || !isset($_POST["c"]))
				throw new Exception("DON'T YOU TRYYYY!!");

			if ($_POST["id"] == 0) {
				// New group
				// Make sure name is unique
				$other = $GLOBALS["db"]->fetch("SELECT id FROM privileges_groups WHERE name = ?", [$_POST["n"]]);
				if ($other) {
					throw new Exception("There's another group with the same name");
				}

				// Insert new group
				$GLOBALS["db"]->execute("INSERT INTO privileges_groups (id, name, privileges, color) VALUES (NULL, ?, ?, ?)", [$_POST["n"], $_POST["priv"], $_POST["c"]]);
			} else {
				// Get old privileges and make sure group exists
				$oldPriv = $GLOBALS["db"]->fetch("SELECT privileges FROM privileges_groups WHERE id = ? LIMIT 1", [$_POST["id"]]);
				if (!$oldPriv) {
					throw new Exception("That privilege group doesn't exist");
				}
				$oldPriv = current($oldPriv);
				// Update existing group
				$GLOBALS["db"]->execute("UPDATE privileges_groups SET name = ?, privileges = ?, color = ? WHERE id = ? LIMIT 1", [$_POST["n"], $_POST["priv"], $_POST["c"], $_POST["id"]]);
				// Get users in this group
				// I genuinely want to kill myself right now.
				$users = $GLOBALS["db"]->fetchAll("SELECT id FROM users WHERE privileges = " . $oldPriv . " OR privileges = " . $oldPriv . " | " . Privileges::UserDonor);
				foreach ($users as $user) {
					// Remove privileges from previous group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~" . $oldPriv . " WHERE id = ? LIMIT 1", [$user["id"]]);
					// Add privileges from new group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | " . $_POST["priv"] . " WHERE id = ? LIMIT 1", [$user["id"]]);
				}
			}

			// Fin.
			redirect("index.php?p=118&s=Saved!");
		} catch (Exception $e) {
			// There's a memino divertentino
			redirect("index.php?p=118&e=" . $e->getMessage());
		}
	}


	/*
	 * RestrictUnrestrictUserReason
     * (Un)restrict a user with a reason (ADMIN CP)
	 */
	public static function RestrictUnrestrictUserReason()
	{
		try {
			// Check if everything is set
			if (empty($_POST['id']) || empty($_POST['reason'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_POST['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to ban this user");
			}

			// Toggle restriction status depending on it's current value
			if (!isRestricted($_POST["id"])) {
				// Restrict, set UserNormal and reset UserPublic
				$newPrivileges = ($userData["privileges"] | Privileges::UserNormal) & ~Privileges::UserPublic;
				$banDateTime = time();

				// Remove from cache & redis leaderboards
				updateBanBancho($_POST["id"], true);
				removeFromLeaderboard($_POST['id']);

				appendNotes($_POST['id'], $_SESSION["username"] . ' (' . $_SESSION["userid"] . ') restricted for: ' . $_POST['reason']);

				postWebhookMessage(sprintf("has restricted [%s](https://akatsuki.gg/u/%s)\n**Reason**: %s\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $userData["username"], $_POST['id'], $_POST["reason"], $_POST['id']));
				rapLog(sprintf("restricted %s for '%s'.", $userData["username"], $_POST["reason"]));
			} else {
				// Remove restrictions, set both UserPublic and UserNormal
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal | Privileges::UserPublic;
				$banDateTime = 0;

				// Re-add to cache leaderboards
				updateBanBancho($_POST["id"], false);

				appendNotes($_POST['id'], $_SESSION["username"] . ' (' . $_SESSION["userid"] . ') unrestricted for: ' . $_POST['reason']);

				postWebhookMessage(sprintf("has unrestricted [%s](https://akatsuki.gg/u/%s)\n**Reason**: %s\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $userData["username"], $_POST['id'], $_POST["reason"], $_POST['id']));
				rapLog(sprintf("unrestricted %s for '%s'.", $userData["username"], $_POST["reason"]));
			}

			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_POST['id']]);

			// Done, redirect to success page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=User restricted/unrestricted!');
			} else {
				redirect('index.php?p=102&s=User restricted/unrestricted!');
			}
		} catch (Exception $e) {
			// Redirect to Exception page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e=' . $e->getMessage());
			} else {
				redirect('index.php?p=102&e=' . $e->getMessage());
			}
		}
	}

	/*
	 * RestrictUnrestrictUser
	 * (Un)restrict user function (ADMIN CP)
	 */
	public static function RestrictUnrestrictUser()
	{
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if (!isRestricted($_GET["id"])) {
				// Restrict, set UserNormal and reset UserPublic
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Remove restrictions, set both UserPublic and UserNormal
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges |= Privileges::UserPublic;
			}
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"], $newPrivileges & Privileges::UserPublic == 0);

			$msg = ($newPrivileges & Privileges::UserPublic) > 0 ? "unrestricted" : "restricted";

			// Rap log
			postWebhookMessage(sprintf("has %s user [%s](https://akatsuki.gg/u/%s).\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $msg, $userData["username"], $_GET['id'], $_POST['id']));
			rapLog(sprintf("has %s user %s", $msg, $userData["username"]));
			// Done, redirect to success page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=User ' . $msg . '!');
			} else {
				redirect('index.php?p=102&s=User ' . $msg . '!');
			}
		} catch (Exception $e) {
			// Redirect to Exception page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e=' . $e->getMessage());
			} else {
				redirect('index.php?p=102&e=' . $e->getMessage());
			}
		}
	}

	/*
	 * BanUnbanUserReason
     * (Un)ban a user with a reason (ADMIN CP)
	 */
	public static function BanUnbanUserReason()
	{
		try {
			// Check if everything is set
			if (empty($_POST['id']) || empty($_POST['reason'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges, ban_datetime FROM users WHERE id = ? LIMIT 1', $_POST['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to ban this user");
			}

			// Toggle ban status depending on it's current value
			if (!isBanned($_POST["id"])) {
				// Remove normal & public privileges
				$newPrivileges = ($userData["privileges"] & ~Privileges::UserNormal) & ~Privileges::UserPublic;
				$banDateTime = time();

				// Remove from cache & redis leaderboards
				updateBanBancho($_POST["id"], true);
				removeFromLeaderboard($_POST['id']);

				appendNotes($_POST['id'], $_SESSION["username"] . ' (' . $_SESSION["userid"] . ') banned for: ' . $_POST['reason']);

				postWebhookMessage(sprintf("has banned user [%s](https://akatsuki.gg/u/%s).\n**Reason**: %s\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $userData["username"], $_POST['id'], $_POST['reason'], $_POST['id'], $_POST['id']));
				rapLog(sprintf("banned %s for '%s'.", $userData["username"], $_POST["reason"]));
			} else {
				// Remove ban, set UserNormal
				$newPrivileges = ($userData["privileges"] | Privileges::UserNormal);
				$banDateTime = $userData["ban_datetime"];

				appendNotes($_POST['id'], $_SESSION["username"] . ' (' . $_SESSION["userid"] . ') unbanned (set to restricted) for: ' . $_POST['reason']);

				postWebhookMessage(sprintf("has unbanned (set to restricted) user [%s](https://akatsuki.gg/u/%s).\n**Reason**: %s\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $userData["username"], $_POST['id'], $_POST['reason'], $_POST['id'], $_POST['id']));
				rapLog(sprintf("unbanned (set to restricted) %s for '%s'.", $userData["username"], $_POST["reason"]));
			}

			// Change privilege
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ?', [$newPrivileges, $banDateTime, $_POST['id']]);

			// Done, redirect to success page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=User banned/unbanned!');
			} else {
				redirect('index.php?p=102&s=User banned/unbanned!');
			}
		} catch (Exception $e) {
			// Redirect to Exception page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e=' . $e->getMessage());
			} else {
				redirect('index.php?p=102&e=' . $e->getMessage());
			}
		}
	}

	public static function GiveDonor()
	{
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["m"]) || empty($_POST["m"]))
				throw new Exception("Invalid user");

			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_POST["id"]]);
			if (!$username) {
				throw new Exception("That user doesn't exist");
			}
			$username = current($username);

			$months = giveDonor($_POST["id"], $_POST["m"], $_POST["type"] == 0, $_POST["stype"] == 1);

			if ($_POST["stype"] == 1) {
				postWebhookMessage(sprintf("has given [%s](https://akatsuki.gg/u/%s) %s month(s) of [**Premium**](https://akatsuki.gg/premium) :credit_card:", $username, $_POST["id"], $_POST["m"]));
				rapLog(sprintf("has given %s (%s) %s month(s) of premium", $username, $_POST["id"], $_POST["m"]), $_SESSION["userid"]);
				redirect("index.php?p=102&s=Premium status changed. Premium for that user now expires in " . $months . " months!");
			} else {
				postWebhookMessage(sprintf("has given [%s](https://akatsuki.gg/u/%s) %s month(s) of [**Supporter**](https://akatsuki.gg/supporter) :blue_heart:", $username, $_POST["id"], $_POST["m"]));
				rapLog(sprintf("has given %s (%s) %s month(s) of supporter", $username, $_POST["id"], $_POST["m"]), $_SESSION["userid"]);
				redirect("index.php?p=102&s=Supporter status changed. Supporter for that user now expires in " . $months . " months!");
			}
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function RemoveDonor()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");

			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("That user doesn't exist");
			}
			$username = current($username);
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~8388612, donor_expire = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);

			// Remove supporter badge
			// 36 = supporter badge id
			// 59 = premium badge id
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ? AND (badge = ? OR badge = ?)", [$_GET["id"], 36, 59]);

			postWebhookMessage(sprintf("has removed [%s](https://akatsuki.gg/u/%s)'s Supporter/Premium", $username, $_GET["id"]));
			rapLog(sprintf("has removed %s's donation status", $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Supporter status changed!");
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function Rollback()
	{
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can rollback this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to rollback this account");
			}
			switch ($_POST["period"]) {
				case "d":
					$periodSeconds = 86400;
					$periodName = "Day";
					break;
				case "w":
					$periodSeconds = 86400 * 7;
					$periodName = "Week";
					break;
				case "m":
					$periodSeconds = 86400 * 30;
					$periodName = "Month";
					break;
				case "y":
					$periodSeconds = 86400 * 365;
					$periodName = "Year";
					break;
			}

			//$removeAfterOsuTime = UNIXTimestampToOsuDate(time()-($_POST["length"]*$periodSeconds));
			$removeAfter = time() - ($_POST["length"] * $periodSeconds);
			$rollbackString = $_POST["length"] . " " . $periodName;
			if ($_POST["length"] > 1) {
				$rollbackString .= "s";
			}

			$GLOBALS["db"]->execute("DELETE FROM scores_relax WHERE userid = ? AND time >= ?", [$_POST["id"], $removeAfter]);
			$GLOBALS["db"]->execute("DELETE FROM scores_ap WHERE userid = ? AND time >= ?", [$_POST["id"], $removeAfter]);
			$GLOBALS["db"]->execute("DELETE FROM scores WHERE userid = ? AND time >= ?", [$_POST["id"], $removeAfter]);

			postWebhookMessage(sprintf("has rolled back %s [%s](https://akatsuki.gg/u/%s)'s account.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $rollbackString, $username, $_POST["id"], $_POST['id']));
			rapLog(sprintf("has rolled back %s %s's account", $rollbackString, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User account has been rolled back!");
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function ToggleCustomBadge()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can edit this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to grant/revoke custom badge privilege on this account");
			}

			// Grant/revoke custom badge privilege
			$can = current($GLOBALS["db"]->fetch("SELECT can_custom_badge FROM users WHERE id = ?", [$_GET["id"]]));
			$grantRevoke = ($can == 0) ? "granted" : "revoked";
			$can = !$can;
			$GLOBALS["db"]->execute("UPDATE users SET can_custom_badge = ? WHERE id = ?", [$can, $_GET["id"]]);

			postWebhookMessage(sprintf("has %s custom badge privilege on [%s](https://akatsuki.gg/u/%s)'s account.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $grantRevoke, $username, $_GET["id"], $_POST['id']));
			rapLog(sprintf("has %s custom badge privilege on %s's account", $grantRevoke, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Custom badge privilege " . $grantRevoke . "!");
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function ToggleUserpage()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges, userpage_allowed FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can edit this user
			if (($userData["privileges"] & Privileges::AdminSilenceUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to grant/revoke userpages on this account");
			}

			// Grant/revoke userpage privilege
			$can = $userData["userpage_allowed"];
			$grantRevoke = ($can == 0) ? "enabled" : "disabled";
			$can = ($can == 1) ? 0 : 1;
			$GLOBALS["db"]->execute("UPDATE users SET userpage_allowed = ? WHERE id = ? LIMIT 1", [$can, $_GET["id"]]);

			rapLog(sprintf("has %s userpage on %s's account", $grantRevoke, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Userpage revoked/granted!");
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}


	public static function lockUnlockUser()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT id, privileges, username FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we can edit this user
			if (($userData["privileges"] & Privileges::AdminManageUsers) > 0 && $_SESSION["userid"] != 1001) {
				throw new Exception("You don't have enough permissions to lock this account");
			}
			// Make sure the user is not banned/restricted
			if (!hasPrivilege(Privileges::UserPublic, $_GET["id"])) {
				throw new Exception("The user is banned or restricted. You can't lock an account if it's banned or restricted. Only normal accounts can be locked.");
			}

			// Grant/revoke custom badge privilege
			$lockUnlock = (hasPrivilege(Privileges::UserNormal, $_GET["id"])) ? "locked" : "unlocked";
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges ^ 2 WHERE id = ? LIMIT 1", [$_GET["id"]]);

			postWebhookMessage(sprintf("has %s [%s](https://akatsuki.gg/u/%s)'s account.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", $lockUnlock, $userData["username"], $_GET["id"], $_POST['id']));
			rapLog(sprintf("has %s %s's account", $lockUnlock, $userData["username"]), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User " . $lockUnlock . "!");
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function RankBeatmapNew()
	{
		global $INTERNAL_BANCHO_SERVICE_BASE_URL;
		global $ScoresConfig;
		try {
			if (!isset($_POST["beatmaps"])) {
				throw new Exception("Invalid form data");
			}

			$bsid = -1;
			$result = "";
			$updateCache = false;

			// Do stuff for each beatmap
			foreach ($_POST["beatmaps"] as $beatmapID => $status) {
				$logToRap = true;

				// Get beatmap set id if not set yet
				if ($bsid == -1) {
					$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
					if (!$bsid) {
						throw new Exception("Beatmap set not found! Please load one diff from this set ingame and try again.");
					}
					$bsid = current($bsid);
				}

				// Change beatmap status
				switch ($status) {
						// Rank beatmap
					case "rank":
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);

						// Restore old scores
						$GLOBALS["db"]->execute("UPDATE scores s JOIN (SELECT userid, MAX(score) maxscore FROM scores JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 WHERE beatmaps.beatmap_md5 = (SELECT beatmap_md5 FROM beatmaps WHERE beatmap_id = ? LIMIT 1) GROUP BY userid) s2 ON s.score = s2.maxscore AND s.userid = s2.userid SET completed = 3", [$beatmapID]);
						$result .= "$beatmapID has been ranked and its scores have been restored. | ";
						break;

						// Force osu!api update (unfreeze)
					case "update":
						$updateCache = true;
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
						$result .= "$beatmapID's ranked status is the same from official osu!. | ";
						break;

						// No changes
					case "no":
						$logToRap = false;
						$result .= "$beatmapID's ranked status has not been edited!. | ";
						break;

						// EH! VOLEVI!
					default:
						throw new Exception("Unknown ranked status value.");
						break;
				}

				// RAP Log
				if ($logToRap) {
					postWebhookMessage(sprintf("has %s beatmap set %s", $status == "rank" ? "ranked" : "unranked", $bsid));
					rapLog(sprintf("has %s beatmap set %s", $status == "rank" ? "ranked" : "unranked", $bsid), $_SESSION["userid"]);
				}
			}

			// Send a message to #announce
			$bm = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name FROM beatmaps WHERE beatmapset_id = ? LIMIT 1", [$bsid]);
			$msg = "[https://osu.ppy.sh/s/" . $bsid . " " . $bm["song_name"] . "] is now ranked!";
			$to = "#announce";
			$requesturl = $INTERNAL_BANCHO_SERVICE_BASE_URL . "/api/v1/fokabotMessage?k=" . urlencode($ScoresConfig["api_key"]) . "&to=" . urlencode($to) . "&msg=" . urlencode($msg);
			$resp = getJsonCurl($requesturl);
			if ($resp["message"] != "ok") {
				postWebhookMessage(sprintf("failed to send FokaBot message :( Error: %s", print_r($resp["message"], true)));
				rapLog("failed to send FokaBot message :( Error: " . print_r($resp["message"], true));
			}

			// Done
			redirect("index.php?p=117&s=" . $result);
		} catch (Exception $e) {
			redirect('index.php?p=117&e=' . $e->getMessage());
		}
	}

	public static function RedirectRankBeatmap()
	{
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["type"]) || empty($_POST["type"])) {
				throw new Exception("Invalid beatmap id or type");
			}
			if ($_POST["type"] == "bsid") {
				$bsid = htmlspecialchars($_POST["id"]);
			} else {
				$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$_POST["id"]]);
				if (!$bsid) {
					throw new Exception("Beatmap set not found in Akatsuki's database. Please use beatmap set id or load at least one difficulty in game before trying to rank a beatmap by its id.");
				}
				$bsid = current($bsid);
			}
			redirect("index.php?p=124&bsid=" . $bsid);
		} catch (Exception $e) {
			redirect('index.php?p=125&e=' . $e->getMessage());
		}
	}

	public static function ClearHWIDMatches()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Invalid user ID");
			}
			$GLOBALS["db"]->execute("DELETE FROM hw_user WHERE userid = ?", [$_GET["id"]]);
			postWebhookMessage(sprintf("has cleared [%s](https://akatsuki.gg/u/%s)'s **HWID matches**.\n\n> :bust_in_silhouette: [View this user](https://old.akatsuki.gg/index.php?p=103&id=%s) on **Admin Panel**", getUserUsername($_GET["id"]), $_GET["id"], $_POST['id']));
			rapLog(sprintf("has cleared %s's HWID matches.", getUserUsername($_GET["id"])));
			redirect('index.php?p=102&s=HWID matches cleared! Make sure to clear multiaccounts\' HWID too, or the user might get restricted for multiaccounting!');
		} catch (Exception $e) {
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}

	public static function TakeReport()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0) {
				throw new Exception("This report is closed");
			} else if ($status["assigned"] == $_SESSION["userid"]) {
				// Unassign
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Assign to current user
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = ? WHERE id = ? LIMIT 1", [$_SESSION["userid"], $_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Assignee changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function SolveUnsolveReport()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -1) {
				throw new Exception("This report is closed or it's marked as useless");
			}
			if ($status["assigned"] == -1) {
				// Unsolve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Solve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -1 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Solved status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function UselessUsefulReport()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -2) {
				throw new Exception("This report is closed");
			}
			if ($status["assigned"] == -2) {
				// Useful (open)
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Useless
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -2 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Useful status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function UploadMainMenuIcon()
	{
		try {
			if (!isset($_POST["name"]) || empty($_POST["name"]) || !isset($_POST["url"]) || empty($_POST["url"])) {
				throw new Exception("Missing required parameter(s).");
			}
			if (!isset($_FILES["file"]) || empty($_FILES["file"]) || $_FILES["file"]["error"] != 0) {
				throw new Exception("Nothing uploaded");
			}
			$path = "main_menu_icons";
			$verifyImg = getimagesize($_FILES["file"]["tmp_name"]);
			if ($verifyImg["mime"] !== "image/png") {
				throw new Exception("Only png images are allowed");
			}
			$fileName = randomFileName($path, ".png");
			$finalFilePath = $path . "/" . $fileName . ".png";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], $finalFilePath)) {
				throw new Exception("File upload failed. Check server's permissions.");
			}
			$defaultCount = current($GLOBALS["db"]->fetch("SELECT COUNT(*) FROM main_menu_icons WHERE is_default = 1"));
			$GLOBALS["db"]->execute("INSERT INTO main_menu_icons (name, file_id, url, is_default) VALUES (?, ?, ?, ?)", [$_POST["name"], $fileName, $_POST["url"], (int)($defaultCount == 0)]);
			$msg = "Main menu icon uploaded successfully";
			$msg .= $defaultCount == 0 ? " and set as default image." : "!";
			redirect("index.php?p=111&s=" . $msg);
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function DeleteMainMenuIcon()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$icon = $GLOBALS["db"]->fetch("SELECT file_id FROM main_menu_icons WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$icon) {
				throw new Exception("The icon doesn't exist.");
			}
			unlink("main_menu_icons/" . $icon["file_id"] . ".png");
			$GLOBALS["db"]->execute("DELETE FROM main_menu_icons WHERE id = ? LIMIT 1", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon deleted successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function SetDefaultMainMenuIcon()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_default = IF(id = ?, 1, 0)", [$_GET["id"]]);
			redirect("index.php?p=111&s=Default main menu icon set successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function SetMainMenuIcon()
	{
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = IF(id = ?, 1, 0)", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon set successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function RestoreMainMenuIcon()
	{
		try {
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = IF((SELECT id FROM (SELECT * FROM main_menu_icons) AS x WHERE x.is_default = 1 AND x.id = main_menu_icons.id LIMIT 1), 1, 0)", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon restored successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function RemoveMainMenuIcon()
	{
		try {
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = 0", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon removed successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function BulkBan()
	{
		try {
			if (!isset($_POST["uid"]) || empty($_POST["uid"])) {
				throw new Exception("No user ids provided.");
			}
			$result = "";
			$errors = "";
			foreach ($_POST["uid"] as $uid) {
				$uid = (int)$uid;
				$user = $GLOBALS["db"]->fetch("SELECT privileges, username FROM users WHERE id = ? LIMIT 1", [$uid]);
				if (!$user) {
					$errors .= "$uid doesn't exist | ";
					continue;
				}
				if (($user["privileges"] & Privileges::AdminManageUsers) > 0) {
					$errors .= "No privileges to ban $uid. | ";
					continue;
				}
				$GLOBALS["db"]->execute("UPDATE users SET privileges = (privileges & ~3) WHERE id = ? LIMIT 1", [$uid]);
				if (isset($_POST["notes"]) && !empty($_POST["notes"])) {
					appendNotes($uid, $_POST["notes"]);
				}
				$result .= "$uid OK! | ";
				$result = trim($result, " | ");
				$errors = trim($errors, " | ");
				updateBanBancho($uid, TRUE);
				postWebhookMessage(sprintf("has banned user [%s](https://akatsuki.gg/u/%s). (bulk ban)", $user["username"], $uid));
				rapLog(sprintf("has banned user %s", $user["username"]));
			}
			redirect("index.php?p=102&e=" . $errors . "&s=" . $result);
		} catch (Exception $e) {
			redirect("index.php?p=102&e=" . $e->getMessage());
		}
	}
}
