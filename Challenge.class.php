<?php
/**
 * @file
 */
class Challenge {

	public $rating_names = array(
		1 => 'positive',
		-1 => 'negative',
		0 => 'neutral'
	);

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
	 * @param User $string User (object) whom to send an email
	 * @param string $subject Email subject
	 * @param $string $body Email contents (HTML)
	 * @return Status object
	 */
	public function sendMail( $user, $subject, $body ) {
		global $wgPasswordSender;
		$sender = new MailAddress( $wgPasswordSender,
			wfMessage( 'emailsender' )->inContentLanguage()->text() );
		$to = new MailAddress( $user );
		return UserMailer::send( $to, $sender, $subject, $body, null, 'text/html; charset=UTF-8' );
	}

	/**
	 * Add a challenge to the database and send a challenge request mail to the
	 * challenged user.
	 *
	 * @param User $challenger The user (object) who challenged $user_to
	 * @param string $user_to Name of the person who was challenged
	 * @param $info
	 * @param $event_date
	 * @param string $description User-supplied description of the challenge
	 * @param string $win_terms User-supplied win terms
	 * @param string $lose_terms User-supplied lose terms
	 */
	public function addChallenge( $challenger, $user_to, $info, $event_date, $description, $win_terms, $lose_terms ) {
		$user_id_to = User::idFromName( $user_to );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'challenge',
			array(
				'challenge_user_id_1' => $challenger->getId(),
				'challenge_username1' => $challenger->getName(),
				'challenge_user_id_2' => $user_id_to,
				'challenge_username2' => $user_to,
				'challenge_info' => $info,
				'challenge_description' => $description,
				'challenge_win_terms' => $win_terms,
				'challenge_lose_terms' => $lose_terms,
				'challenge_status' => 0,
				'challenge_date' => $dbw->timestamp(),
				'challenge_event_date' => $event_date
			),
			__METHOD__
		);

		$this->challenge_id = $dbw->insertId();
		$this->sendChallengeRequestEmail( $user_id_to, $challenger->getName(), $this->challenge_id );
	}

	public function sendChallengeRequestEmail( $user_id_to, $user_from, $id ) {
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'challenge_request_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_request_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( array( 'id' => $id ) ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeAcceptEmail( $user_id_to, $user_from, $id ) {
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'challenge_accept_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_accept_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( array( 'id' => $id ) ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeLoseEmail( $user_id_to, $user_from, $id ) {
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();

		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage(
				'challenge_lose_subject',
				$user_from,
				$id
			)->parse();
			$body = wfMessage(
				'challenge_lose_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( array( 'id' => $id ) ),
				$update_profile_link->getFullURL()
			)->text();
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeWinEmail( $user_id_to, $user_from, $id ) {
		$user = User::newFromId( $user_id_to );
		$user->loadFromDatabase();
		if ( $user->isEmailConfirmed() && $user->getIntOption( 'notifychallenge', 1 ) ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'challenge_win_subject', $user_from, $id )->parse();
			$body = wfMessage(
				'challenge_win_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( array( 'id' => $id ) ),
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
			array( 'challenge_status' => $status ),
			array( 'challenge_id' => $challenge_id ),
			__METHOD__
		);
		$c = $this->getChallenge( $challenge_id );

		switch ( $status ) {
			case 1: // challenge was accepted
				// Update social stats for both users involved in challenge
				$stats = new UserStatsTrack( 1, $c['user_id_1'], $c['user_name_1'] );
				$stats->incStatField( 'challenges' );

				$stats = new UserStatsTrack( 1, $c['user_id_2'], $c['user_name_2'] );
				$stats->incStatField( 'challenges' );

				if ( $email ) {
					$this->sendChallengeAcceptEmail( $c['user_id_1'], $c['user_name_2'], $challenge_id );
				}

				break;
			case 3: // challenge was completed, send email to loser
				$stats = new UserStatsTrack( 1, $c['winner_user_id'], $c['winner_user_name'] );
				$stats->incStatField( 'challenges_won' );

				$this->updateUserStandings( $challenge_id );
				if ( $c['winner_user_id'] == $c['user_id_1'] ) {
					$loser_id = $c['user_id_2'];
					$loser_name = $c['user_name_2'];
				} else {
					$loser_id = $c['user_id_1'];
					$loser_name = $c['user_name_1'];
				}

				if ( $email ) {
					$this->sendChallengeLoseEmail( $loser_id, $c['winner_user_name'], $challenge_id );
					$this->sendChallengeWinEmail( $c['winner_user_id'], $loser_name, $challenge_id );
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
			array(
				'challenge_user_id_1', 'challenge_username1', 'challenge_user_id_2',
				'challenge_username2', 'challenge_info', 'challenge_event_date',
				'challenge_description', 'challenge_win_terms',
				'challenge_lose_terms', 'challenge_winner_user_id',
				'challenge_winner_username', 'challenge_status'
			),
			array( 'challenge_id' => $id ),
			__METHOD__
		);

		if ( $s !== false ) {
			if ( $s->challenge_winner_user_id != -1 ) { // if it's not a tie
				if ( $s->challenge_user_id_1 == $s->challenge_winner_user_id ) {
					$winner_id = $s->challenge_user_id_1;
					$loser_id = $s->challenge_user_id_2;
				} else {
					$winner_id = $s->challenge_user_id_2;
					$loser_id = $s->challenge_user_id_1;
				}
				$this->updateUserRecord( $winner_id, 1 );
				$this->updateUserRecord( $loser_id, -1 );
			} else {
				$this->updateUserRecord( $s->challenge_user_id_1, 0 );
				$this->updateUserRecord( $s->challenge_user_id_2, 0 );
			}
		}
	}

	public function updateChallengeWinner( $id, $user_id ) {
		$user = User::newFromId( $user_id );
		$user_name = $user->getName();
		$dbr = wfGetDB( DB_MASTER );
		$dbr->update(
			'challenge',
			array(
				'challenge_winner_user_id' => $user_id,
				'challenge_winner_username' => $user_name
			),
			array( 'challenge_id' => $id ),
			__METHOD__
		);
	}

	public function updateUserRecord( $id, $type ) {
		$user = User::newFromId( $id );
		$username = $user->getName();

		$dbr = wfGetDB( DB_SLAVE );
		$dbw = wfGetDB( DB_MASTER );
		$wins = 0;
		$losses = 0;
		$ties = 0;

		$res = $dbr->select(
			'challenge_user_record',
			array( 'challenge_wins', 'challenge_losses', 'challenge_ties' ),
			array( 'challenge_record_user_id' => $id ),
			__METHOD__,
			array( 'LIMIT' => 1 )
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
				array(
					'challenge_record_user_id' => $id,
					'challenge_record_username' => $username,
					'challenge_wins' => $wins,
					'challenge_losses' => $losses,
					'challenge_ties' => $ties
				),
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
				array(
					'challenge_wins' => $wins,
					'challenge_losses' => $losses,
					'challenge_ties' => $ties
				),
				array( 'challenge_record_user_id' => $id ),
				__METHOD__
			);
		}
	}

	/**
	 * Is the supplied user (ID) a participant in the challenge, identified by
	 * its ID?
	 *
	 * @param int $userId User ID
	 * @param int $challengeId Challenge ID
	 * @return bool
	 */
	public function isUserInChallenge( $userId, $challengeId ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'challenge',
			array( 'challlenge_user_id_1', 'challlenge_user_id_2' ),
			array( 'challenge_id' => $challengeId ),
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $userId == $s->challlenge_user_id_1 || $userId == $s->challlenge_user_id_2 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the amount of open challenges for the given user (ID).
	 *
	 * @param int $userId User ID
	 * @return int Challenge count for the given user (ID)
	 */
	static function getOpenChallengeCount( $userId ) {
		$dbr = wfGetDB( DB_MASTER );
		$openChallengeCount = 0;
		$s = $dbr->selectRow(
			'challenge',
			array( 'COUNT(*) AS count' ),
			array( 'challenge_user_id_2' => $userId, 'challenge_status' => 0 ),
			__METHOD__
		);
		if ( $s !== false ) {
			$openChallengeCount = $s->count;
		}
		return $openChallengeCount;
	}

	/**
	 * Get the amount of total challenges for the given user (ID).
	 *
	 * @param int $userId User ID
	 * @return int Challenge count for the given user (ID)
	 */
	static function getChallengeCount( $userId = 0 ) {
		$dbr = wfGetDB( DB_SLAVE );
		$challengeCount = 0;

		$userSQL = array();
		if ( $userId ) {
			$userSQL = array( 'challenge_user_id_1' => $userId );
		}

		$s = $dbr->selectRow(
			'challenge',
			array( 'COUNT(*) AS count' ),
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
		$id = (int) $id; // paranoia!
		$dbr = wfGetDB( DB_MASTER );
		$sql = "SELECT {$dbr->tableName( 'challenge' )}.challenge_id AS id, challenge_user_id_1, challenge_username1, challenge_user_id_2, challenge_username2, challenge_info, challenge_description, challenge_event_date, challenge_status, challenge_winner_username, challenge_winner_user_id,
			challenge_win_terms, challenge_lose_terms, challenge_rate_score, challenge_rate_comment
			FROM {$dbr->tableName( 'challenge' )} LEFT JOIN {$dbr->tableName( 'challenge_rate' )}
				ON {$dbr->tableName( 'challenge_rate' )}.challenge_id = {$dbr->tableName( 'challenge' )}.challenge_id
			WHERE {$dbr->tableName( 'challenge' )}.challenge_id = {$id}";
		$res = $dbr->query( $sql, __METHOD__ );

		$challenge = array();
		foreach ( $res as $row ) {
			$challenge[] = array(
				'id' => $row->id,
				'status' => $row->challenge_status,
				'user_id_1' => $row->challenge_user_id_1,
				'user_name_1' => $row->challenge_username1,
				'user_id_2' => $row->challenge_user_id_2,
				'user_name_2' => $row->challenge_username2,
				'info' => $row->challenge_info,
				'description' => $row->challenge_description,
				'date' => $row->challenge_event_date,
				'win_terms' => $row->challenge_win_terms,
				'lose_terms' => $row->challenge_lose_terms,
				'winner_user_id' => $row->challenge_winner_user_id,
				'winner_user_name' => $row->challenge_winner_username,
				'rating' => $row->challenge_rate_score,
				'rating_comment' => $row->challenge_rate_comment
			);
		}

		return $challenge[0];
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
			$limitvalue = 0;
			if ( $page && is_int( $page ) ) {
				$limitvalue = $page * $limit - ( $limit );
			}
			$limit_sql = " LIMIT {$limitvalue},{$limit} ";
		}

		if ( $status != null && is_int( $status ) ) {
			$status_sql = " AND challenge_status = {$status}";
		}
		if ( $user_name ) {
			$user_id = User::idFromName( $user_name );
			$user_sql = " AND (challenge_user_id_1 = {$user_id} OR challenge_user_id_2 = {$user_id}) ";
		}

		$dbr = wfGetDB( DB_MASTER );
		$sql = "SELECT {$dbr->tableName( 'challenge' )}.challenge_id AS id, challenge_user_id_1, challenge_username1, challenge_user_id_2, challenge_username2, challenge_info, challenge_description, challenge_event_date, challenge_status, challenge_winner_username, challenge_winner_user_id,
			challenge_win_terms, challenge_lose_terms, challenge_rate_score, challenge_rate_comment
			FROM {$dbr->tableName( 'challenge' )} LEFT JOIN {$dbr->tableName( 'challenge_rate' )} ON
				{$dbr->tableName( 'challenge_rate' )}.challenge_id = {$dbr->tableName( 'challenge' )}.challenge_id
			WHERE 1=1
			{$user_sql}
			{$status_sql}
			ORDER BY challenge_date DESC
			{$limit_sql}";

		$res = $dbr->query( $sql, __METHOD__ );

		$challenges = array();
		foreach ( $res as $row ) {
			$challenges[] = array(
				'id' => $row->id,
				'status' => $row->challenge_status,
				'user_id_1' => $row->challenge_user_id_1,
				'user_name_1' => $row->challenge_username1,
				'user_id_2' => $row->challenge_user_id_2,
				'user_name_2' => $row->challenge_username2,
				'info' => $row->challenge_info,
				'description' => $row->challenge_description,
				'date' => $row->challenge_event_date,
				'win_terms' => $row->challenge_win_terms,
				'lose_terms' => $row->challenge_lose_terms,
				'winner_user_id' => $row->challenge_winner_user_id,
				'winner_user_name' => $row->challenge_winner_username,
				'rating' => $row->challenge_rate_score,
				'rating_comment' => $row->challenge_rate_comment
			);
		}

		return $challenges;
	}

	/**
	 * Get the challenge record for a given user ID.
	 *
	 * @param int $userId User ID
	 * @return string Wins, losses and ties separated by a dash
	 */
	public static function getUserChallengeRecord( $userId ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'challenge_user_record',
			array( 'challenge_wins', 'challenge_losses', 'challenge_ties' ),
			array( 'challenge_record_user_id' => $userId ),
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
	 * @param int $userId
	 * @return int
	 */
	public static function getUserFeedbackScoreByType( $rateType, $userId ) {
		$dbr = wfGetDB( DB_MASTER );
		return (int) $dbr->selectField(
			'challenge_rate',
			'COUNT(*) AS total',
			array(
				'challenge_rate_user_id' => $userId,
				'challenge_rate_score' => $rateType
			),
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
			case 3:
				$out .= wfMessage( 'challenge-status-completed' )->plain();
				break;
		}
		return $out;
	}
}