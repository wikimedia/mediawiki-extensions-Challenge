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
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__;

		$dbType = $updater->getDB()->getType();

		$filename = '../sql/challenge.sql';
		// For non-MySQL/MariaDB/SQLite DBMSes, use the appropriately named file
		/*
		if ( !in_array( $dbType, array( 'mysql', 'sqlite' ) ) ) {
			$filename = "challenge.{$dbType}.sql";
		}
		*/

		$updater->addExtensionUpdate( array( 'addTable', 'challenge', "{$dir}/{$filename}", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'challenge_rate', "{$dir}/{$filename}", true ) );
		$updater->addExtensionUpdate( array( 'addTable', 'challenge_user_record', "{$dir}/{$filename}", true ) );

		return true;
	}

}
