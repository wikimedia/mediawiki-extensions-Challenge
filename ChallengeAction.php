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

		switch ( $request->getVal( 'action' ) ) {
			case 1:
				$c->updateChallengeStatus(
					$request->getVal( 'id' ),
					$request->getVal( 'status' )
				);
				break;
			case 2:
				//if ( $this->getUser()->isAllowed( 'challengeadmin' ) ) {
					$c->updateChallengeWinner(
						$request->getVal( 'id' ),
						$request->getVal( 'userid' )
					);
					$c->updateChallengeStatus( $request->getVal( 'id' ), 3 );
				//}
				break;
			case 3:
				// Update social stats for both users involved in challenge
				$stats = new UserStatsTrack(
					1,
					$request->getVal( 'loser_userid' ),
					$request->getVal( 'loser_username' )
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
					array(
						'challenge_id' => $request->getVal( 'id' ),
						'challenge_rate_submitter_user_id' => $this->getUser()->getId(),
						'challenge_rate_submitter_username' => $this->getUser()->getName(),
						'challenge_rate_user_id' => $request->getVal( 'loser_userid' ),
						'challenge_rate_username' => $request->getVal( 'loser_username' ),
						'challenge_rate_date' => $dbw->timestamp(),
						'challenge_rate_score' => $request->getVal( 'challenge_rate' ),
						'challenge_rate_comment' => $request->getVal( 'rate_comment' )
					),
					__METHOD__
				);
				break;
		}

		$this->getOutput()->setArticleBodyOnly( true );
	}
}