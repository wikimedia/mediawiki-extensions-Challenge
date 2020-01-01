<?php
/**
 * @file
 */
class Challenge {

	public $rating_names = [
		1 => 'positive',
		-1 => 'negative',
		0 => 'neutral'
	];

	/**
	 * Quickie wrapper function for sending out an email as properly rendered
	 * HTML instead of plaintext.
	 *
	 * The functions in this class that call this function used to use
	 * User::sendMail(), but it was causing the mentioned bug, hence why this
	 * function had to be introduced.
	 *
	 * @see https://bugzilla.wikimedia.org/show_bug.cgi?id=68045
	 * @see https://gerrit.wikimedia.org/r/#/c/146514/
	 *
	 * @param User $user User (object) whom to send an email
	 * @param string $subject Email subject
	 * @param $string $body Email contents (HTML)
	 * @return Status object
	 */
	public function sendMail( $user, $subject, $body ) {
		global $wgPasswordSender;
		$sender = new MailAddress( $wgPasswordSender,
			wfMessage( 'emailsender' )->inContentLanguage()->text() );
		$to = new MailAddress( $user->getEmail(), $user->getName(), $user->getRealName() );
		return UserMailer::send( $to, $sender, $subject, $body, [ 'contentType' => 'text/html; charset=UTF-8' ] );
	}

	/**
	 * Add a challenge to the database and send a challenge request mail to the
	 * challenged user.
	 *
	 * @param User $challenger The user (object) who challenged $user_to
	 * @param User $challengee Name of the person who was challenged
	 * @param string $info
	 * @param $event_date
	 * @param string $description User-supplied description of the challenge
	 * @param string $win_terms User-supplied win terms
	 * @param string $lose_terms User-supplied lose terms
	 */
	public function addChallenge( $challenger, $challengee, $info, $event_date, $description, $win_terms, $lose_terms ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'challenge',
			[
				'challenge_challenger_actor' => $challenger->getActorId(),
				'challenge_challengee_actor' => $challengee->getActorId(),
				'challenge_info' => $info,
				'challenge_description' => $description,
				'challenge_win_terms' => $win_terms,
				'challenge_lose_terms' => $lose_terms,
				'challenge_status' => 0,
				'challenge_date' => $dbw->timestamp(),
				'challenge_event_date' => $event_date
			],
			__METHOD__
		);

		$this->challenge_id = $dbw->insertId();
		$this->sendChallengeRequestEmail(
			$challenger->getActorId(),
			$challengee->getActorId(),
			$this->challenge_id
		);
	}

	/**
	 * If the challengee has a confirmed email and they've opted into receiving
	 * challenge-related emails, inform them that they've been challenged.
	 *
	 * @param int $challengerActorId Actor ID of the challenger user
	 * @param int $challengee Actor ID of the user who was challenged
	 * @param int $id Challenge ID
	 */
	public function sendChallengeRequestEmail( $challengerActorId, $challengeeActorId, $id ) {
		$user = User::newFromActorId( $challengeeActorId );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = User::newFromActorId( $challengerActorId )->getName();
			$subject = wfMessage( 'challenge_request_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_request_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeAcceptEmail( $challengerActorId, $challengeeActorId, $id ) {
		$user = User::newFromActorId( $challengerActorId );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = User::newFromActorId( $challengeeActorId )->getName();
			$subject = wfMessage( 'challenge_accept_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_accept_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeLoseEmail( $loserActorId, $winnerActorId, $id ) {
		$user = User::newFromActorId( $loserActorId );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = User::newFromActorId( $winnerActorId )->getName();
			$subject = wfMessage(
				'challenge_lose_subject',
				$user_from,
				$id
			)->parse();
			$body = wfMessage(
				'challenge_lose_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeWinEmail( $winnerActorId, $loserActorId, $id ) {
		$user = User::newFromActorId( $winnerActorId );
		$user->loadFromDatabase();
		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = User::newFromActorId( $loserActorId )->getName();
			$subject = wfMessage( 'challenge_win_subject', $user_from, $id )->parse();
			$body = wfMessage(
				'challenge_win_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	/**
	 * Update the status of the given challenge (via its ID) to $status.
	 *
	 * @param int $challenge_id Challenge identifier
	 * @param int $status Status code
	 * @param bool $email Send emails to challenge participants (if they have confirmed their addresses)?
	 */
	public function updateChallengeStatus( $challenge_id, $status, $email = true ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'challenge',
			[ 'challenge_status' => $status ],
			[ 'challenge_id' => $challenge_id ],
			__METHOD__
		);
		$c = $this->getChallenge( $challenge_id );

		switch ( $status ) {
			case 1: // challenge was accepted
				// Update social stats for both users involved in challenge
				$challenger = User::newFromActorId( $c['challenger_actor'] );
				$stats = new UserStatsTrack( $challenger->getId(), $challenger->getName() );
				$stats->incStatField( 'challenges' );

				$challengee = User::newFromActorId( $c['challengee_actor'] );
				$stats = new UserStatsTrack( $challengee->getId(), $challengee->getName() );
				$stats->incStatField( 'challenges' );

				if ( $email ) {
					$this->sendChallengeAcceptEmail(
						$c['challenger_actor'],
						$c['challengee_actor'],
						$challenge_id
					);
				}

				break;
			case 3: // challenge was completed, send email to loser
				$winner = User::newFromActorId( $c['winner_actor'] );
				$stats = new UserStatsTrack( $winner->getId(), $winner->getName() );
				$stats->incStatField( 'challenges_won' );

				$this->updateUserStandings( $challenge_id );
				if ( $c['winner_actor'] == $c['challenger_actor'] ) {
					$loser_id = $c['challengee_actor'];
				} else {
					$loser_id = $c['challenger_actor'];
				}

				if ( $email ) {
					$this->sendChallengeLoseEmail( $loser_id, $c['winner_actor'], $challenge_id );
					$this->sendChallengeWinEmail( $c['winner_actor'], $loser_id, $challenge_id );
				}
				break;
		}
	}

	/**
	 * Update challenge standings for both participants.
	 *
	 * @param int $id Challenge identifier
	 */
	public function updateUserStandings( $id ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'challenge',
			[
				'challenge_challenger_actor', 'challenge_challengee_actor',
				'challenge_info', 'challenge_event_date', 'challenge_description',
				'challenge_win_terms', 'challenge_lose_terms', 'challenge_winner_actor',
				'challenge_status'
			],
			[ 'challenge_id' => $id ],
			__METHOD__
		);

		if ( $s !== false ) {
			if ( $s->challenge_winner_actor != -1 ) { // if it's not a tie
				if ( $s->challenge_challenger_actor == $s->challenge_winner_actor ) {
					$winner_id = $s->challenge_challenger_actor;
					$loser_id = $s->challenge_challengee_actor;
				} else {
					$winner_id = $s->challenge_challengee_actor;
					$loser_id = $s->challenge_challenger_actor;
				}
				$this->updateUserRecord( $winner_id, 1 );
				$this->updateUserRecord( $loser_id, -1 );
			} else {
				$this->updateUserRecord( $s->challenge_challenger_actor, 0 );
				$this->updateUserRecord( $s->challenge_challengee_actor, 0 );
			}
		}
	}

	/**
	 * Set the winner of a given challenge.
	 *
	 * @param int $id Challenge ID
	 * @param int $actorId Winning user's actor ID
	 */
	public function updateChallengeWinner( $id, $actorId ) {
		$dbr = wfGetDB( DB_MASTER );
		$dbr->update(
			'challenge',
			[
				'challenge_winner_actor' => $actorId
			],
			[ 'challenge_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * Update challenge records for the given user.
	 *
	 * @param int $id User's actor ID
	 * @param int $type 0 for a tie, 1 if they won, -1 if they lost
	 */
	public function updateUserRecord( $id, $type ) {
		$user = User::newFromActorId( $id );

		$dbr = wfGetDB( DB_REPLICA );
		$dbw = wfGetDB( DB_MASTER );
		$wins = 0;
		$losses = 0;
		$ties = 0;

		$res = $dbr->select(
			'challenge_user_record',
			[ 'challenge_wins', 'challenge_losses', 'challenge_ties' ],
			[ 'challenge_record_actor' => $id ],
			__METHOD__,
			[ 'LIMIT' => 1 ]
		);
		$row = $dbr->fetchObject( $res );
		if ( !$row ) {
			switch ( $type ) {
				case -1:
					$losses = 1;
					break;
				case 0:
					$ties = 1;
					break;
				case 1:
					$wins = 1;
					break;
			}
			$dbw->insert(
				'challenge_user_record',
				[
					'challenge_record_actor' => $id,
					'challenge_wins' => $wins,
					'challenge_losses' => $losses,
					'challenge_ties' => $ties
				],
				__METHOD__
			);
		} else {
			$wins = $row->challenge_wins;
			$losses = $row->challenge_losses;
			$ties = $row->challenge_ties;
			switch ( $type ) {
				case -1:
					$losses++;
					break;
				case 0:
					$ties++;
					break;
				case 1:
					$wins++;
					break;
			}
			$dbw->update(
				'challenge_user_record',
				[
					'challenge_wins' => $wins,
					'challenge_losses' => $losses,
					'challenge_ties' => $ties
				],
				[ 'challenge_record_actor' => $id ],
				__METHOD__
			);
		}
	}

	/**
	 * Is the supplied user a participant in the given challenge?
	 *
	 * @note Completely unused as of 1 January 2020.
	 *
	 * @param int $actorId Actor ID
	 * @param int $challengeId Challenge ID
	 * @return bool
	 */
	public function isUserInChallenge( $actorId, $challengeId ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'challenge',
			[ 'challenge_challenger_actor', 'challenge_challengee_actor' ],
			[ 'challenge_id' => $challengeId ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $actorId == $s->challenge_challenger_actor || $actorId == $s->challenge_challengee_actor ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the amount of open challenges for the given user, identified via their
	 * actor ID.
	 *
	 * @note Completely unused as of 1 January 2020.
	 *
	 * @param int $actorId Actor ID
	 * @return int Open challenge count for the given user
	 */
	static function getOpenChallengeCount( $actorId ) {
		$dbr = wfGetDB( DB_MASTER );
		$openChallengeCount = 0;
		$s = $dbr->selectRow(
			'challenge',
			[ 'COUNT(*) AS count' ],
			[ 'challenge_challengee_actor' => $actorId, 'challenge_status' => 0 ],
			__METHOD__
		);
		if ( $s !== false ) {
			$openChallengeCount = $s->count;
		}
		return $openChallengeCount;
	}

	/**
	 * Get the amount of total challenges for the given user, identified via
	 * their actor ID.
	 *
	 * @todo FIXME: This is only called frm SpecialChallengeHistory.php, which
	 * calls it _non-statically_ and never passes anything as the param...
	 *
	 * @param int $actorId Actor ID
	 * @return int Challenge count for the given user
	 */
	static function getChallengeCount( $actorId = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$challengeCount = 0;

		$userSQL = [];
		if ( $actorId ) {
			$userSQL = [ 'challenge_challenger_actor' => $actorId ];
		}

		$s = $dbr->selectRow(
			'challenge',
			[ 'COUNT(*) AS count' ],
			$userSQL,
			__METHOD__
		);

		if ( $s !== false ) {
			$challengeCount = $s->count;
		}

		return $challengeCount;
	}

	/**
	 * Fetch everything we know about a challenge from the database when given
	 * a challenge identifier.
	 *
	 * @param int $id Challenge identifier
	 * @return array
	 */
	public function getChallenge( $id ) {
		$id = (int)$id; // paranoia!
		$dbr = wfGetDB( DB_MASTER );
		$sql = "SELECT {$dbr->tableName( 'challenge' )}.challenge_id AS id,
			challenge_challenger_actor, challenge_challengee_actor, challenge_info,
			challenge_description, challenge_event_date, challenge_status, challenge_winner_actor,
			challenge_win_terms, challenge_lose_terms, challenge_rate_score, challenge_rate_comment
			FROM {$dbr->tableName( 'challenge' )} LEFT JOIN {$dbr->tableName( 'challenge_rate' )}
				ON {$dbr->tableName( 'challenge_rate' )}.challenge_id = {$dbr->tableName( 'challenge' )}.challenge_id
			WHERE {$dbr->tableName( 'challenge' )}.challenge_id = {$id}";
		$res = $dbr->query( $sql, __METHOD__ );

		$challenge = [];
		foreach ( $res as $row ) {
			$challenge[] = [
				'id' => $row->id,
				'status' => $row->challenge_status,
				'challenger_actor' => $row->challenge_challenger_actor,
				'challengee_actor' => $row->challenge_challengee_actor,
				'info' => $row->challenge_info,
				'description' => $row->challenge_description,
				'date' => $row->challenge_event_date,
				'win_terms' => $row->challenge_win_terms,
				'lose_terms' => $row->challenge_lose_terms,
				'winner_actor' => $row->challenge_winner_actor,
				'rating' => $row->challenge_rate_score,
				'rating_comment' => $row->challenge_rate_comment
			];
		}

		return isset( $challenge[0] ) && $challenge[0] ? $challenge[0] : [];
	}

	/**
	 * Get the list of challenges that match the given conditions for a given
	 * user (via their user name).
	 *
	 * @param string $user_name User name
	 * @param int $status Challenge status code (or null for all challenges)
	 * @param int $limit SQL query LIMIT, i.e. get this many results
	 * @param int $page SQL query OFFSET, i.e. skip this many results
	 * @return array
	 */
	public function getChallengeList( $user_name, $status = null, $limit = 0, $page = 0 ) {
		$limit_sql = $status_sql = $user_sql = '';
		if ( $limit > 0 && is_int( $limit ) ) {
			$offset = 0;
			if ( $page && is_int( $page ) ) {
				$offset = $page * $limit - ( $limit );
			}
			$limit_sql = " LIMIT {$offset},{$limit} ";
		}

		if ( $status != null && is_int( $status ) ) {
			$status_sql = " AND challenge_status = {$status}";
		}
		if ( $user_name ) {
			$actorId = User::newFromName( $user_name )->getActorId();
			$user_sql = " AND (challenge_challenger_actor = {$actorId} OR challenge_challengee_actor = {$actorId}) ";
		}

		$dbr = wfGetDB( DB_MASTER );
		$sql = "SELECT {$dbr->tableName( 'challenge' )}.challenge_id AS id,
			challenge_challenger_actor, challenge_challengee_actor, challenge_info,
			challenge_description, challenge_event_date, challenge_status, challenge_winner_actor,
			challenge_win_terms, challenge_lose_terms, challenge_rate_score, challenge_rate_comment
			FROM {$dbr->tableName( 'challenge' )} LEFT JOIN {$dbr->tableName( 'challenge_rate' )} ON
				{$dbr->tableName( 'challenge_rate' )}.challenge_id = {$dbr->tableName( 'challenge' )}.challenge_id
			WHERE 1=1
			{$user_sql}
			{$status_sql}
			ORDER BY challenge_date DESC
			{$limit_sql}";

		$res = $dbr->query( $sql, __METHOD__ );

		$challenges = [];
		foreach ( $res as $row ) {
			$challenges[] = [
				'id' => $row->id,
				'status' => $row->challenge_status,
				'challenger_actor' => $row->challenge_challenger_actor,
				'challengee_actor' => $row->challenge_challengee_actor,
				'info' => $row->challenge_info,
				'description' => $row->challenge_description,
				'date' => $row->challenge_event_date,
				'win_terms' => $row->challenge_win_terms,
				'lose_terms' => $row->challenge_lose_terms,
				'winner_actor' => $row->challenge_winner_actor,
				'rating' => $row->challenge_rate_score,
				'rating_comment' => $row->challenge_rate_comment
			];
		}

		return $challenges;
	}

	/**
	 * Get the challenge record for a given user, identified via their actor ID.
	 *
	 * @param int $actorId User's actor ID
	 * @return string Wins, losses and ties separated by a dash
	 */
	public static function getUserChallengeRecord( $actorId ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'challenge_user_record',
			[ 'challenge_wins', 'challenge_losses', 'challenge_ties' ],
			[ 'challenge_record_actor' => $actorId ],
			__METHOD__
		);
		if ( $s !== false ) {
			return $s->challenge_wins . '-' . $s->challenge_losses . '-' . $s->challenge_ties;
		} else {
			return '0-0-0';
		}
	}

	/**
	 * @param int $rateType
	 * @param int $actorId
	 * @return int
	 */
	public static function getUserFeedbackScoreByType( $rateType, $actorId ) {
		$dbr = wfGetDB( DB_MASTER );
		return (int)$dbr->selectField(
			'challenge_rate',
			'COUNT(*) AS total',
			[
				'challenge_rate_actor' => $actorId,
				'challenge_rate_score' => $rateType
			],
			__METHOD__
		);
	}

	/**
	 * Given a numeric status code, returns the corresponding human-readable
	 * status name.
	 *
	 * @param int $status Challenge status code
	 * @return string
	 */
	public static function getChallengeStatusName( $status ) {
		$out = '';
		switch ( $status ) {
			case -1:
				$out .= wfMessage( 'challenge-status-rejected' )->plain();
				break;
			case -2:
				$out .= wfMessage( 'challenge-status-removed' )->plain();
				break;
			case 0:
				$out .= wfMessage( 'challenge-status-awaiting' )->plain();
				break;
			case 1:
				$out .= wfMessage( 'challenge-status-in-progress' )->plain();
				break;
			case 2:
				$out .= wfMessage( 'challenge-status-countered' )->plain();
				break;
			case 3:
				$out .= wfMessage( 'challenge-status-completed' )->plain();
				break;
		}
		return $out;
	}
}