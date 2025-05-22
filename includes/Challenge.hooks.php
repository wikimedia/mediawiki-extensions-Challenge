<?php
/**
 * @file
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

class ChallengeHooks {

	/**
	 * Set up Echo (notifications extension) integration: define the new notification
	 * categories and what notifications go there + their custom icons.
	 *
	 * @param array[] &$notifications Echo notifications
	 * @param array[] &$notificationCategories Echo notification categories
	 * @param array[] &$icons Icon details
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['challenge-received'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-challenge-received',
		];

		$notifications['challenge-received'] = [
			'category' => 'challenge-received',
			'group' => 'interactive',
			'section' => 'alert',
			'presentation-model' => 'EchoReceivedChallengePresentationModel',
			MediaWiki\Extension\Notifications\AttributeManager::ATTR_LOCATORS => [
			],

			'icon' => 'challenge-received',

			'bundle' => [
				'web' => true,
				'email' => true
			]
		];

		$icons['challenge-received'] = [
			'path' => '../resources/lib/ooui/themes/wikimediaui/images/icons/die-progressive.svg'
		];

		$notificationCategories['challenge-accepted'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-challenge-accepted',
		];

		$notifications['challenge-accepted'] = [
			'category' => 'challenge-accepted',
			'group' => 'positive',
			'presentation-model' => 'EchoAcceptedChallengePresentationModel',
			MediaWiki\Extension\Notifications\AttributeManager::ATTR_LOCATORS => [
			],

			'icon' => 'challenge-accepted',

			'bundle' => [
				'web' => true,
				'email' => true
			]
		];

		// 'success' instead of 'progressive' because I like the look of the green
		// checkmark more than that of the blue one
		$icons['challenge-accepted'] = [
			'path' => '../resources/lib/ooui/themes/wikimediaui/images/icons/check-success.svg'
		];

		$notificationCategories['challenge-rejected'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-challenge-rejected',
		];

		$notifications['challenge-rejected'] = [
			'category' => 'challenge-rejected',
			'group' => 'negative',
			'presentation-model' => 'EchoRejectedChallengePresentationModel',
			MediaWiki\Extension\Notifications\AttributeManager::ATTR_LOCATORS => [
			],

			'icon' => 'challenge-rejected',

			'bundle' => [
				'web' => true,
				'email' => true
			]
		];

		$icons['challenge-rejected'] = [
			'path' => '../resources/lib/ooui/themes/wikimediaui/images/icons/cancel-destructive.svg'
		];

		$notificationCategories['challenge-lost'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-challenge-lost',
		];

		$notifications['challenge-lost'] = [
			'category' => 'challenge-lost',
			'group' => 'negative',
			'presentation-model' => 'EchoLostChallengePresentationModel',
			MediaWiki\Extension\Notifications\AttributeManager::ATTR_LOCATORS => [
			],

			'icon' => 'challenge-lost',

			'bundle' => [
				'web' => true,
				'email' => true
			]
		];

		$icons['challenge-lost'] = [
			'path' => '../resources/lib/ooui/themes/wikimediaui/images/icons/heart-progressive.svg'
		];

		$notificationCategories['challenge-won'] = [
			'priority' => 3,
			'tooltip' => 'echo-pref-tooltip-challenge-won',
		];

		$notifications['challenge-won'] = [
			'category' => 'challenge-won',
			'group' => 'positive',
			'presentation-model' => 'EchoWonChallengePresentationModel',
			MediaWiki\Extension\Notifications\AttributeManager::ATTR_LOCATORS => [
			],

			'icon' => 'challenge-won',

			'bundle' => [
				'web' => true,
				'email' => true
			]
		];

		$icons['challenge-won'] = [
			'path' => '../resources/lib/ooui/themes/wikimediaui/images/icons/notice.svg'
		];
	}

	/**
	 * Add user to be notified on Echo event - in our case, notify the 'target' user
	 * of a notification event, e.g. loser/winner of a challenge or the user who was
	 * challenged, etc.
	 *
	 * This is needed to actually make the notifications show up for the desired user(s). :^)
	 *
	 * @param MediaWiki\Extension\Notifications\Model\Event $event
	 * @param User[] &$users
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		switch ( $event->getType() ) {
			case 'challenge-received':
			case 'challenge-accepted':
			case 'challenge-rejected':
			case 'challenge-lost':
			case 'challenge-won':
				$extra = $event->getExtra();
				$targetId = $extra['target'];
				$users[] = MediaWikiServices::getInstance()->getUserFactory()->newFromId( $targetId );
				break;
		}
	}

	/**
	 * Set bundle for message
	 *
	 * @param MediaWiki\Extension\Notifications\Model\Event $event
	 * @param string &$bundleString
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'challenge-received':
				$bundleString = 'challenge-received';
				break;

			case 'challenge-accepted':
				$bundleString = 'challenge-accepted';
				break;

			case 'challenge-rejected':
				$bundleString = 'challenge-rejected';
				break;

			case 'challenge-lost':
				$bundleString = 'challenge-lost';
				break;

			case 'challenge-won':
				$bundleString = 'challenge-won';
				break;
		}
	}

	/**
	 * Adds the three new required database tables into the database when the
	 * user runs /maintenance/update.php (the core database updater script).
	 *
	 * @param MediaWiki\Installer\DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__ . '/../sql';

		$dbType = $updater->getDB()->getType();

		// For non-MySQL/MariaDB/SQLite DBMSes, pull the files from the appropriate
		// subdirectory
		if ( !in_array( $dbType, [ 'mysql', 'sqlite' ] ) ) {
			$dir .= "/{$dbType}";
		}

		$updater->addExtensionTable( 'challenge', "{$dir}/challenge.sql" );
		$updater->addExtensionTable( 'challenge_rate', "{$dir}/challenge_rate.sql" );
		$updater->addExtensionTable( 'challenge_user_record', "{$dir}/challenge_user_record.sql" );
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
		if ( $ctx->getUser()->isRegistered() && !$upp->isOwner() ) {
			$challengeUser = SpecialPage::getTitleFor( 'ChallengeUser', $upp->user_name );
			$profileLinks['challenge-user'] =
				'<a href="' . htmlspecialchars( $challengeUser->getFullURL(), ENT_QUOTES ) . '" rel="nofollow">' .
					$ctx->msg( 'challenge-this-user' )->escaped() .
				'</a>';
		}
	}

}
