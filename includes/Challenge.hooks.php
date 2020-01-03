<?php
/**
 * @file
 */
class ChallengeHooks {

	/**
	 * Adds the three new required database tables into the database when the
	 * user runs /maintenance/update.php (the core database updater script).
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__;

		$dbType = $updater->getDB()->getType();

		$filename = '../sql/challenge.sql';
		// For non-MySQL/MariaDB/SQLite DBMSes, use the appropriately named file
		/*
		if ( !in_array( $dbType, [ 'mysql', 'sqlite' ] ) ) {
			$filename = "challenge.{$dbType}.sql";
		}
		*/

		$updater->addExtensionTable( 'challenge', "{$dir}/{$filename}" );
		$updater->addExtensionTable( 'challenge_rate', "{$dir}/{$filename}" );
		$updater->addExtensionTable( 'challenge_user_record', "{$dir}/{$filename}" );
	}

	/**
	 * Adds a "challenge this user" link to the social user profile pages
	 *
	 * @param UserProfilePage $upp
	 * @param array &$profileLinks Array of existing profile links
	 */
	public static function onUserProfileGetProfileHeaderLinks( $upp, &$profileLinks ) {
		// $upp->getContext()->getUser() refers to the currently _viewing_ user, not
		// to the user whose profile is being viewed
		// Show this link only to registered users who are *not* viewing their own profile
		$ctx = $upp->getContext();
		if ( $ctx->getUser()->isLoggedIn() && !$upp->isOwner() ) {
			$challengeUser = SpecialPage::getTitleFor( 'ChallengeUser', $upp->user_name );
			$profileLinks['challenge-user'] =
				'<a href="' . htmlspecialchars( $challengeUser->getFullURL(), ENT_QUOTES ) . '" rel="nofollow">' .
					$ctx->msg( 'challenge-this-user' )->escaped() .
				'</a>';
		}
	}

}
