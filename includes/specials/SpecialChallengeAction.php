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
		$request = $this->getRequest();
		$c = new Challenge();
		$action = $request->getVal( 'action' );

		if ( !$action ) {
			// This page isn't supposed to be accessed directly, but who knows
			// what the users will do, so show an informative message in case
			// if some poor soul ends up here directly (bug T152890)
			$this->getOutput()->addWikiMsg( 'challengeaction-go-away' );
			return;
		}

		switch ( $action ) {
			case 1:
				$c->updateChallengeStatus(
					$request->getInt( 'id' ),
					$request->getVal( 'status' )
				);
				break;
			case 2:
				//if ( $this->getUser()->isAllowed( 'challengeadmin' ) ) {
					$c->updateChallengeWinner(
						$request->getInt( 'id' ),
						$request->getInt( 'actorid' )
					);
					$c->updateChallengeStatus( $request->getInt( 'id' ), 3 );
				//}
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
						'challenge_id' => $request->getVal( 'id' ),
						'challenge_rate_submitter_actor' => $this->getUser()->getActorId(),
						'challenge_rate_actor' => $request->getInt( 'loser_actorid' ),
						'challenge_rate_date' => $dbw->timestamp(),
						'challenge_rate_score' => $request->getVal( 'challenge_rate' ),
						'challenge_rate_comment' => $request->getVal( 'rate_comment' )
					],
					__METHOD__
				);
				break;
		}

		$this->getOutput()->setArticleBodyOnly( true );
	}
}