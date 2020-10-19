<?php

class ChallengeAction extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ChallengeAction' );
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

		$c = new Challenge();
		$action = $request->getVal( 'action' );

		if ( !$action ) {
			// This page isn't supposed to be accessed directly, but who knows
			// what the users will do, so show an informative message in case
			// if some poor soul ends up here directly (bug T152890)
			$out->addWikiMsg( 'challengeaction-go-away' );
			return;
		}

		// status code 2 means "challenge removed by admin" which uses a different anti-CSRF token
		if ( !$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) && $request->getInt( 'status' ) !== 2 ) {
			$out->addWikiMsg( 'sessionfailure' );
			return;
		}

		switch ( $action ) {
			case 1:
				$c->updateChallengeStatus(
					// @todo FIXME: this is a bit subpar...'id' is used by JS but 'challenge_id' by no-JS
					$request->getInt( 'id' ) ?? $request->getInt( 'challenge_id' ),
					$request->getVal( 'status' )
				);
				break;
			case 2:
				if (
					$user->isAllowed( 'challengeadmin' ) &&
					$user->matchEditToken( $request->getVal( 'wpAdminToken' ) )
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
				$loser = User::newFromActorId( $request->getInt( 'loser_actorid' ) );
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

				$dbw = wfGetDB( DB_MASTER );
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

		$out->setArticleBodyOnly( true );
	}
}