<?php
/**
 * Challenge extension - allows challenging other users
 *
 * @file
 * @ingroup Extensions
 * @author Aaron Wright <aaron.wright{ at }gmail{ dot }com>
 * @author David Pean <david.pean{ at }gmail{ dot }com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @version 1.0
 * @link https://www.mediawiki.org/wiki/Extension:Challenge Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Extension credits that show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'Challenge',
	'version' => '1.0',
	'author' => array( 'Aaron Wright', 'David Pean', 'Jack Phoenix' ),
	'description' => 'Allows challenging other users',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Challenge'
);

// ResourceLoader support for MediaWiki 1.17+
$commonCSSModuleProperties = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Challenge',
	'position' => 'top'
);

$wgResourceModules['ext.challenge.history'] = $commonCSSModuleProperties + array(
	'styles' => 'resources/css/ext.challenge.history.css'
);

$wgResourceModules['ext.challenge.user'] = $commonCSSModuleProperties + array(
	'styles' => 'resources/css/ext.challenge.user.css'
);

$wgResourceModules['ext.challenge.standings'] = $commonCSSModuleProperties + array(
	'styles' => 'resources/css/ext.challenge.standings.css'
);

$wgResourceModules['ext.challenge.view'] = $commonCSSModuleProperties + array(
	'styles' => 'resources/css/ext.challenge.view.css'
);

$wgResourceModules['ext.challenge.js.main'] = array(
	'scripts' => 'resources/js/Challenge.js',
	'messages' => array(
		'challenge-js-event-required', 'challenge-js-date-required',
		'challenge-js-description-required', 'challenge-js-win-terms-required',
		'challenge-js-lose-terms-required', 'challenge-js-challenge-removed',
		'challenge-js-accepted', 'challenge-js-rejected', 'challenge-js-countered',
		'challenge-js-winner-recorded', 'challenge-js-rating-submitted'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Challenge'
);

$wgResourceModules['ext.challenge.js.datevalidator'] = array(
	'scripts' => 'resources/js/ValidateDate.js',
	'messages' => array(
		'challenge-js-error-date-format', 'challenge-js-error-invalid-month',
		'challenge-js-error-invalid-day', 'challenge-js-error-invalid-year',
		'challenge-js-error-invalid-date', 'challenge-js-error-future-date',
		'challenge-js-error-is-backwards'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Challenge'
);

$wgResourceModules['ext.challenge.js.datepicker'] = array(
	'scripts' => 'resources/js/DatePicker.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Challenge'
);

// Don't leak our temporary variable into global scope
unset( $commonCSSModuleProperties );

// New user right, required to pick the challenge winner via Special:ChallengeView
$wgAvailableRights[] = 'challengeadmin';
$wgGroupPermissions['sysop']['challengeadmin'] = true;

// i18n
$wgMessagesDirs['Challenge'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['ChallengeAliases'] = __DIR__ . '/Challenge.alias.php';

// Classes to be autoloaded
$wgAutoloadClasses['Challenge'] = __DIR__ . '/Challenge.class.php';
$wgAutoloadClasses['ChallengeAction'] = __DIR__ . '/ChallengeAction.php';
$wgAutoloadClasses['ChallengeHistory'] = __DIR__ . '/ChallengeHistory.php';
$wgAutoloadClasses['ChallengeStandings'] = __DIR__ . '/ChallengeStandings.php';
$wgAutoloadClasses['ChallengeUser'] = __DIR__ . '/ChallengeUser.php';
$wgAutoloadClasses['ChallengeView'] = __DIR__ . '/ChallengeView.php';

// New special pages
$wgSpecialPages['ChallengeAction'] = 'ChallengeAction';
$wgSpecialPages['ChallengeHistory'] = 'ChallengeHistory';
$wgSpecialPages['ChallengeStandings'] = 'ChallengeStandings';
$wgSpecialPages['ChallengeUser'] = 'ChallengeUser';
$wgSpecialPages['ChallengeView'] = 'ChallengeView';

/**
 * Adds the three new required database tables into the database when the
 * user runs /maintenance/update.php (the core database updater script).
 *
 * @param DatabaseUpdater $updater
 * @return bool
 */
$wgHooks['LoadExtensionSchemaUpdates'][] = function( $updater ) {
	$dir = __DIR__;

	$dbType = $updater->getDB()->getType();

	$filename = 'challenge.sql';
	// For non-MySQL/MariaDB/SQLite DBMSes, use the appropriately named file
	/*
	if ( !in_array( $dbType, array( 'mysql', 'sqlite' ) ) ) {
		$filename = "schema.{$dbType}.sql";
	}
	*/

	$updater->addExtensionUpdate( array( 'addTable', 'challenge', "{$dir}/{$filename}", true ) );
	$updater->addExtensionUpdate( array( 'addTable', 'challenge_rate', "{$dir}/{$filename}", true ) );
	$updater->addExtensionUpdate( array( 'addTable', 'challenge_user_record', "{$dir}/{$filename}", true ) );

	return true;
};