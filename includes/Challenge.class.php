<?php
/**
 * @file
 */

use MediaWiki\Extension\Notifications\Model\Event as EchoEvent;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

class Challenge {

	// Constants for describing challenge status
	/**
	 * @var int Challenge was removed by an admin for violating rules
	 */
	const STATUS_REMOVED = -2;

	/**
	 * @var int Challenge was rejected by the challengee
	 */
	const STATUS_REJECTED = -1;

	/**
	 * @var int Default state, the challengee has not yet actioned on the challenge
	 */
	const STATUS_AWAITING = 0;

	/**
	 * @var int Challenge was accepted by the challengee
	 */
	const STATUS_ACCEPTED = 1;

	/**
	 * @var int Challenge terms were countered by the challengee
	 */
	const STATUS_COUNTERED = 2;

	/**
	 * @var int Challenge was completed and the loser should be emailed about their loss
	 */
	const STATUS_COMPLETED = 3;

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
	 * @param MediaWiki\User\User $user User (object) whom to send an email
	 * @param string $subject Email subject
	 * @param string $body Email contents (HTML)
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
	 * Helper for addChallenge(), formats a given date into something MediaWiki can work with.
	 *
	 * @param string $date Event date in the MM/DD/YYYY format (incl. the slashes)
	 * @return string The supplied timestamp formatted as YYYYMMDD and suffixed with six zeros
	 */
	private function formatDateForDB( $date ) {
		$exploded = explode( '/', $date );
		if ( count( $exploded ) !== 3 ) {
			throw new MWException( __METHOD__ . ' given a bogus argument that did not contain three slashes' );
		}

		$date = $exploded[1];
		$month = $exploded[0];
		$year = $exploded[2];

		return $year . $month . $date . '000000';
	}

	/**
	 * Add a challenge to the database and send a challenge request mail to the
	 * challenged user.
	 *
	 * @param MediaWiki\User\User $challenger The user (object) who challenged $user_to
	 * @param MediaWiki\User\User $challengee User object representing the person who was challenged
	 * @param string $info User-supplied challenge title
	 * @param string $event_date Event date in the MM/DD/YYYY format (incl. the slashes)
	 * @param string $description User-supplied description of the challenge
	 * @param string $win_terms User-supplied win terms
	 * @param string $lose_terms User-supplied lose terms
	 */
	public function addChallenge( $challenger, $challengee, $info, $event_date, $description, $win_terms, $lose_terms ) {
		$services = MediaWikiServices::getInstance();
		$contLang = $services->getContentLanguage();

		$dbw = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert(
			'challenge',
			[
				'challenge_challenger_actor' => $challenger->getActorId(),
				'challenge_challengee_actor' => $challengee->getActorId(),
				'challenge_info' => $contLang->truncateForDatabase( $info, 200 ),
				'challenge_description' => $description,
				'challenge_win_terms' => $contLang->truncateForDatabase( $win_terms, 200 ),
				'challenge_lose_terms' => $contLang->truncateForDatabase( $lose_terms, 200 ),
				'challenge_status' => self::STATUS_AWAITING,
				'challenge_date' => $dbw->timestamp(),
				'challenge_event_date' => $dbw->timestamp( $this->formatDateForDB( $event_date ) )
			],
			__METHOD__
		);

		$challenge_id = $dbw->insertId();

		$this->sendChallengeRequestEmail(
			$challenger->getActorId(),
			$challengee->getActorId(),
			$challenge_id
		);

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			EchoEvent::create( [
				'type' => 'challenge-received',
				'agent' => $challenger,
				'extra' => [
					'notifyAgent' => false,
					'target' => $challengee->getId(),
					'challenge-id' => $challenge_id
				]
			] );
		}
	}

	/**
	 * If the challengee has a confirmed email and they've opted into receiving
	 * challenge-related emails, inform them that they've been challenged.
	 *
	 * @param int $challengerActorId Actor ID of the challenger user
	 * @param int $challengeeActorId Actor ID of the user who was challenged
	 * @param int $id Challenge ID
	 */
	public function sendChallengeRequestEmail( $challengerActorId, $challengeeActorId, $id ) {
		$services = MediaWikiServices::getInstance();
		$userFactory = $services->getUserFactory();

		$user = $userFactory->newFromActorId( $challengeeActorId );
		$user->loadFromDatabase();

		$userOptionsLookup = $services->getUserOptionsLookup();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$wantsEmail = $userOptionsLookup->getBoolOption(
				$user,
				'echo-subscriptions-email-challenge-received'
			);
		} else {
			$wantsEmail = $userOptionsLookup->getIntOption(
				$user,
				'notifychallenge',
				1
			);
		}

		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = $userFactory->newFromActorId( $challengerActorId )->getName();
			$subject = wfMessage( 'challenge_request_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_request_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			// @phan-suppress-next-line SecurityCheck-XSS UserMailer::send defaults to text/plain if passed a string
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeAcceptEmail( $challengerActorId, $challengeeActorId, $id ) {
		$services = MediaWikiServices::getInstance();
		$userFactory = $services->getUserFactory();

		$user = $userFactory->newFromActorId( $challengerActorId );
		$user->loadFromDatabase();

		$userOptionsLookup = $services->getUserOptionsLookup();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$wantsEmail = $userOptionsLookup->getBoolOption(
				$user,
				'echo-subscriptions-email-challenge-accepted'
			);
		} else {
			$wantsEmail = $userOptionsLookup->getIntOption(
				$user,
				'notifychallenge',
				1
			);
		}

		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = $userFactory->newFromActorId( $challengeeActorId )->getName();
			$subject = wfMessage( 'challenge_accept_subject', $user_from )->text();
			$body = wfMessage(
				'challenge_accept_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			// @phan-suppress-next-line SecurityCheck-XSS UserMailer::send defaults to text/plain if passed a string
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeLoseEmail( $loserActorId, $winnerActorId, $id ) {
		$services = MediaWikiServices::getInstance();
		$userFactory = $services->getUserFactory();

		$user = $userFactory->newFromActorId( $loserActorId );
		$user->loadFromDatabase();

		$userOptionsLookup = $services->getUserOptionsLookup();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$wantsEmail = $userOptionsLookup->getBoolOption(
				$user,
				'echo-subscriptions-email-challenge-lost'
			);
		} else {
			$wantsEmail = $userOptionsLookup->getIntOption(
				$user,
				'notifychallenge',
				1
			);
		}

		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = $userFactory->newFromActorId( $winnerActorId )->getName();
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
			// @phan-suppress-next-line SecurityCheck-XSS UserMailer::send defaults to text/plain if passed a string
			$this->sendMail( $user, $subject, $body );
		}
	}

	public function sendChallengeWinEmail( $winnerActorId, $loserActorId, $id ) {
		$services = MediaWikiServices::getInstance();
		$userFactory = $services->getUserFactory();

		$user = $userFactory->newFromActorId( $winnerActorId );
		$user->loadFromDatabase();

		$userOptionsLookup = $services->getUserOptionsLookup();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$wantsEmail = $userOptionsLookup->getBoolOption(
				$user,
				'echo-subscriptions-email-challenge-won'
			);
		} else {
			$wantsEmail = $userOptionsLookup->getIntOption(
				$user,
				'notifychallenge',
				1
			);
		}

		if ( $user->isEmailConfirmed() && $wantsEmail ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$user_from = $userFactory->newFromActorId( $loserActorId )->getName();
			$subject = wfMessage( 'challenge_win_subject', $user_from, $id )->parse();
			$body = wfMessage(
				'challenge_win_body',
				$user->getName(),
				$user_from,
				$challenge_view_title->getFullURL( [ 'id' => $id ] ),
				$update_profile_link->getFullURL()
			)->text();
			// @phan-suppress-next-line SecurityCheck-XSS UserMailer::send defaults to text/plain if passed a string
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
		$services = MediaWikiServices::getInstance();
		$userFactory = $services->getUserFactory();
		$dbw = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->update(
			'challenge',
			[ 'challenge_status' => $status ],
			[ 'challenge_id' => $challenge_id ],
			__METHOD__
		);
		$c = $this->getChallenge( $challenge_id );

		switch ( $status ) {
			case self::STATUS_ACCEPTED: // challenge was accepted
				// Update social stats for both users involved in challenge
				$challenger = $userFactory->newFromActorId( $c['challenger_actor'] );
				$stats = new UserStatsTrack( $challenger->getId(), $challenger->getName() );
				$stats->incStatField( 'challenges' );

				$challengee = $userFactory->newFromActorId( $c['challengee_actor'] );
				$stats = new UserStatsTrack( $challengee->getId(), $challengee->getName() );
				$stats->incStatField( 'challenges' );

				if ( $email ) {
					$this->sendChallengeAcceptEmail(
						$c['challenger_actor'],
						$c['challengee_actor'],
						$challenge_id
					);
				}

				if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
					EchoEvent::create( [
						'type' => 'challenge-accepted',
						'agent' => $challengee,
						'extra' => [
							'notifyAgent' => false,
							'target' => $challenger->getId(),
							'challenge-id' => $challenge_id
						]
					] );
				}

				break;
			case self::STATUS_COMPLETED: // challenge was completed, send email to loser
				$winner = $userFactory->newFromActorId( $c['winner_actor'] );
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

				if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
					EchoEvent::create( [
						'type' => 'challenge-won',
						'agent' => $winner,
						'extra' => [
							'notifyAgent' => true,
							'target' => $winner->getId(),
							'challenge-id' => $challenge_id
						]
					] );

					EchoEvent::create( [
						'type' => 'challenge-lost',
						'agent' => $winner,
						'extra' => [
							'notifyAgent' => false,
							'target' => $loser_id,
							'challenge-id' => $challenge_id
						]
					] );
				}

				break;

			case self::STATUS_REJECTED:
				// Inform the challenger that their challenge was rejected
				if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
					$challengee = $userFactory->newFromActorId( $c['challengee_actor'] );
					$challenger = $userFactory->newFromActorId( $c['challenger_actor'] );

					EchoEvent::create( [
						'type' => 'challenge-rejected',
						'agent' => $challengee,
						'extra' => [
							'notifyAgent' => false,
							'target' => $challenger->getId(),
							'challenge-id' => $challenge_id
						]
					] );
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
		$services = MediaWikiServices::getInstance();
		$user = $services->getUserFactory()->newFromActorId( $id );

		$lb = $services->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$dbw = $lb->getConnection( DB_PRIMARY );
		$wins = 0;
		$losses = 0;
		$ties = 0;

		$row = $dbr->selectRow(
			'challenge_user_record',
			[ 'challenge_wins', 'challenge_losses', 'challenge_ties' ],
			[ 'challenge_record_actor' => $id ],
			__METHOD__
		);
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
	public static function getOpenChallengeCount( $actorId ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
	 * @todo FIXME: This is only called from SpecialChallengeHistory.php, which
	 * calls it _non-statically_ and never passes anything as the param...
	 *
	 * @param int $actorId Actor ID
	 * @return int Challenge count for the given user
	 */
	public static function getChallengeCount( $actorId = 0 ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
	 * @param int|null $status Challenge status code (or null for all challenges)
	 * @param int $limit SQL query LIMIT, i.e. get this many results
	 * @param int $page SQL query OFFSET, i.e. skip this many results
	 * @return array
	 */
	public function getChallengeList( $user_name, $status = null, $limit = 0, $page = 0 ) {
		$services = MediaWikiServices::getInstance();
		$status_sql = $user_sql = '';
		$offset = 0;

		if ( $limit > 0 && is_int( $limit ) ) {
			if ( $page && is_int( $page ) ) {
				$offset = $page * $limit - ( $limit );
			}
		}

		if ( $status != null && is_int( $status ) ) {
			$status_sql = " AND challenge_status = {$status}";
		}
		if ( $user_name ) {
			$actorId = $services->getUserFactory()->newFromName( $user_name )->getActorId();
			$user_sql = " AND (challenge_challenger_actor = {$actorId} OR challenge_challengee_actor = {$actorId}) ";
		}

		$dbr = $services->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$sql = "SELECT {$dbr->tableName( 'challenge' )}.challenge_id AS id,
			challenge_challenger_actor, challenge_challengee_actor, challenge_info,
			challenge_description, challenge_event_date, challenge_status, challenge_winner_actor,
			challenge_win_terms, challenge_lose_terms, challenge_rate_score, challenge_rate_comment
			FROM {$dbr->tableName( 'challenge' )} LEFT JOIN {$dbr->tableName( 'challenge_rate' )} ON
				{$dbr->tableName( 'challenge_rate' )}.challenge_id = {$dbr->tableName( 'challenge' )}.challenge_id
			WHERE 1=1
			{$user_sql}
			{$status_sql}
			ORDER BY challenge_date DESC";

		$res = $dbr->query( $dbr->limitResult( $sql, $limit, $offset ), __METHOD__ );

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
	 * @return-taint none
	 */
	public static function getUserChallengeRecord( $actorId ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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
				$out .= wfMessage( 'challenge-status-rejected' )->escaped();
				break;
			case -2:
				$out .= wfMessage( 'challenge-status-removed' )->escaped();
				break;
			case 0:
				$out .= wfMessage( 'challenge-status-awaiting' )->escaped();
				break;
			case 1:
				$out .= wfMessage( 'challenge-status-in-progress' )->escaped();
				break;
			case 2:
				$out .= wfMessage( 'challenge-status-countered' )->escaped();
				break;
			case 3:
				$out .= wfMessage( 'challenge-status-completed' )->escaped();
				break;
		}
		return $out;
	}
}
