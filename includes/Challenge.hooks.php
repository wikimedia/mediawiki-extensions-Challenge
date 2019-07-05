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

}
