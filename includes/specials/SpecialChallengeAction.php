<?php

use MediaWiki\SpecialPage\UnlistedSpecialPage;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class ChallengeAction extends UnlistedSpecialPage {
	private ILoadBalancer $loadBalancer;
	private UserFactory $userFactory;

	public function __construct(
		ILoadBalancer $loadBalancer,
		UserFactory $userFactory
	) {
		parent::__construct( 'ChallengeAction' );
		$this->loadBalancer = $loadBalancer;
		$this->userFactory = $userFactory;
	}

	/**
	 * Show the special page
	 *
	 * @param mixed $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$this->setHeaders();

		$c = new Challenge();
		$action = $request->getVal( 'action' );

		if ( !$action ) {
			// This page isn't supposed to be accessed directly, but who knows
			// what the users will do, so show an informative message in case
			// if some poor soul ends up here directly (bug T152890)
			$out->addWikiMsg( 'challengeaction-go-away' );
			return;
		}

		// status code -2 means "challenge removed by admin" which uses a different anti-CSRF token
		$tokenCheckOK =
			( $request->getInt( 'status' ) === -2 ) ? $user->matchEditToken( $request->getVal( 'wpAdminToken' ) ) :
				$user->matchEditToken( $request->getVal( 'wpEditToken' ) );

		if ( $request->getInt( 'status' ) !== -2 && !$tokenCheckOK ) {
			$out->addWikiMsg( 'sessionfailure' );
			return;
		}

		switch ( $action ) {
			case 1:
				if ( $tokenCheckOK ) {
					$c->updateChallengeStatus(
						// @todo FIXME: this is a bit subpar...'id' is used by JS but 'challenge_id' by no-JS
						// But as subpar as that is, it seems like the only good way to distinguish
						// between JS and no-JS in PHP code.
						// @phan-suppress-next-line PhanCoalescingNeverNull
						$request->getInt( 'id' ) ?? $request->getInt( 'challenge_id' ),
						$request->getInt( 'status' )
					);
				}
				break;
			case 2:
				if (
					$user->isAllowed( 'challengeadmin' ) &&
					$tokenCheckOK
				) {
					$c->updateChallengeWinner(
						$request->getInt( 'challenge_id' ),
						$request->getInt( 'challenge_winner_actorid' )
					);
					$c->updateChallengeStatus( $request->getInt( 'challenge_id' ), Challenge::STATUS_COMPLETED );
				}
				break;
			case 3:
				// Update social stats for both users involved in challenge
				$loser = $this->userFactory->newFromActorId( $request->getInt( 'loser_actorid' ) );
				$stats = new UserStatsTrack(
					$loser->getId(),
					$loser->getName()
				);
				if ( $request->getVal( 'challenge_rate' ) == 1 ) {
					$stats->incStatField( 'challenges_rating_positive' );
				}
				if ( $request->getVal( 'challenge_rate' ) == -1 ) {
					$stats->incStatField( 'challenges_rating_negative' );
				}

				$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
				$dbw->insert(
					'challenge_rate',
					[
						'challenge_id' => $request->getVal( 'id' ) ?? $request->getInt( 'challenge_id' ),
						'challenge_rate_submitter_actor' => $user->getActorId(),
						'challenge_rate_actor' => $request->getInt( 'loser_actorid' ),
						'challenge_rate_date' => $dbw->timestamp(),
						'challenge_rate_score' => $request->getVal( 'challenge_rate' ),
						'challenge_rate_comment' => $request->getVal( 'rate_comment' )
					],
					__METHOD__
				);
				break;
		}

		if ( !$request->getInt( 'challenge_id' ) ) {
			// JS request -> set body only
			$out->setArticleBodyOnly( true );
		} else {
			// No-JS request -> do some further processing since we have no JS to do our magic for us...
			$specialPage = SpecialPage::getTitleFor(
				'ChallengeView',
				// blargh@type cast; needed for phan
				(string)$request->getInt( 'challenge_id' )
			);
			$out->redirect( $specialPage->getFullURL() );
		}
	}
}
