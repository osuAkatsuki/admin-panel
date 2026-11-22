<?php

/**
 * RememberCookieHandler
 * A simple way to remember an user over time.
 *
 * @author Howl <the@howl.moe>
 * @version 1.1
 */
class RememberCookieHandler
{
	private const LIFETIME_SECONDS = 90 * 24 * 60 * 60;

	private function SetCookie(string $value, int $expires): void
	{
		setcookie("sli", $value, [
			'expires' => $expires,
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
	}
	/**
	 * Check
	 * Checks whether the user is currently not logged in and has the required
	 * cookies for the "stay logged in" thing.
	 *
	 * @return bool true if the cookies are valid, false otherwise.
	 */
	public function Check()
	{
		startSessionIfNotStarted();
		return !isset($_SESSION["username"]) && is_string($_COOKIE["sli"] ?? null) && $_COOKIE["sli"] !== "";
	}

	/**
	 * Validate
	 * Checks if a remember cookie is ok, and logs in the user if it is.
	 *
	 * @return int
	 * @see ValidateValue
	 */
	public function Validate()
	{
		$parts = explode("|", $_COOKIE["sli"], 3);
		if (count($parts) !== 3 || !ctype_digit($parts[1]) || (int)$parts[1] <= time()) {
			$this->UnsetCookies();
			return ValidateValue::Failure;
		}
		$r = $GLOBALS["db"]->fetch(
			"SELECT r.id, r.userid, r.token_sha, u.password_md5, u.username, u.privileges
			 FROM remember r JOIN users u ON u.id = r.userid WHERE r.series_identifier = ? LIMIT 1",
			[$parts[0]]
		);
		if (!$r) {
			$this->UnsetCookies();
			return ValidateValue::Failure;
		}
		if (hash_equals($r["token_sha"], hash("sha256", $parts[1] . "|" . $parts[2] . "|" . $r["password_md5"]))) {
			return $this->Login($r);
		}
		$GLOBALS["db"]->execute("DELETE FROM remember WHERE id = ? LIMIT 1", [$r['id']]);
		$this->UnsetCookies();
		return ValidateValue::Thieving;
	}

	/**
	 * IssueNew
	 * Issue new permanent cookie for auto-login.
	 */
	public function IssueNew($userID, string $passwordHash)
	{
		$series = unpack("L", random_bytes(4))[1];
		$token = base64_encode(random_bytes(75));
		$expires = time() + self::LIFETIME_SECONDS;
		$tokenHash = hash("sha256", $expires . "|" . $token . "|" . $passwordHash);
		$GLOBALS["db"]->execute("INSERT INTO remember(userid, series_identifier, token_sha) VALUES (?, ?, ?)",
			[$userID, $series, $tokenHash]);
		$this->SetCookie($series . "|" . $expires . "|" . $token, $expires);
	}

	/**
	 * Destroy
	 * Destroys a particular sid and token in the database.
	 */
	public function Destroy()
	{
		if (!isset($_SESSION["userid"]) || !is_string($_COOKIE["sli"] ?? null))
			return;
		$GLOBALS["db"]->execute(
			"DELETE FROM remember WHERE userid = ? AND series_identifier = ? LIMIT 1;",
			[$_SESSION["userid"], explode("|", $_COOKIE["sli"])[0]]
		);
		$this->UnsetCookies();
	}

	/**
	 * DestroyAll
	 * Destroys all sids and tokens for the user in the database.
	 *
	 * @param int $u UserID
	 */
	public function DestroyAll($u)
	{
		$GLOBALS["db"]->execute("DELETE FROM remember WHERE userid = ?;", [$u]);
	}

	/**
	 * Login
	 * Login into user's account, onto successful validation.
	 *
	 * @return int ValidateValue
	 */
	private function Login(array $u)
	{
		if (($u["privileges"] & Privileges::UserNormal) === 0 || ($u["privileges"] & Privileges::AdminAccessRAP) === 0) {
			$this->UnsetCookies();
			return ValidateValue::UserBanned;
		}
		startSessionIfNotStarted();
		session_regenerate_id(true);
		$_SESSION['csrf'] = generateCsrfToken();
		$_SESSION['username'] = $u['username'];
		$_SESSION['userid'] = $u['userid'];
		$_SESSION['password'] = $u['password_md5'];
		$_SESSION['passwordChanged'] = false;
		logIP($u['userid']);
		// Save latest activity
		updateLatestActivity($u['userid']);
		return ValidateValue::NowLoggedIn;
	}

	/**
	 * UnsetCookies
	 * Unset the sli cookie in the user's browser.
	 */
	public function UnsetCookies()
	{
		$this->SetCookie("", time() - 3600);
	}
}

abstract class ValidateValue
{
	const UserBanned  = -2; // user is banned, show warning
	const Thieving    = -1; // someone appears to be thieving. the token is deleted from db for security
	const Failure     = 0;  // cookie has been removed
	const NowLoggedIn = 1;  // the user is now logged in successfully
}
