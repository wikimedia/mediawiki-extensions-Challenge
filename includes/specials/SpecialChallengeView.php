<?php

class ChallengeView extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ChallengeView' );
	}

	/**
	 * Under which header this special page is listed in Special:SpecialPages?
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}

	/**
	 * Show the special page
	 *
	 * @param int|null $par Parameter (challenge ID) passed to the page
	 */
	public function execute( $par ) {
		$this->getOutput()->setPageTitle( $this->msg( 'challengeview' ) );

		$id = (int)$this->getRequest()->getVal( 'id', $par );
		if ( $id == '' ) {
			$this->getOutput()->addHTML( $this->msg( 'challengeview-nochallenge' )->plain() );
		} else {
	 		$this->getOutput()->addHTML( $this->displayChallenge( $id ) );
		}
	}

	private function displayChallenge( $id ) {
		$this->getOutput()->addModuleStyles( 'ext.challenge.view' );
		$this->getOutput()->addModules( 'ext.challenge.js.main' );

		$c = new Challenge();
		$challenge = $c->getChallenge( $id );
		if ( empty( $challenge ) ) {
			return $this->msg( 'challengeview-invalidid' )->plain();
		}

		$u = $this->getUser();
		$challenger = User::newFromActorId( $challenge['challenger_actor'] );
		$challengee = User::newFromActorId( $challenge['challengee_actor'] );
		$avatar1 = new wAvatar( $challenger->getId(), 'l' );
		$avatar2 = new wAvatar( $challengee->getId(), 'l' );
		$title1 = Title::makeTitle( NS_USER, $challenger->getName() );
		$title2 = Title::makeTitle( NS_USER, $challengee->getName() );

		$template = new ChallengeViewTemplate();

		$template->set( 'c', $c );
		$template->set( 'class', $this );
		$template->set( 'challenge', $challenge );
		$template->set( 'avatar1', $avatar1 );
		$template->set( 'avatar2', $avatar2 );
		$template->set( 'title1', $title1 );
		$template->set( 'title2', $title2 );
		$template->set( 'user', $u );

		$out = '';
		switch ( $challenge['status'] ) {
			case 0:
				if ( $this->getUser()->getActorId() != $challenge['challengee_actor'] ) {
					$out .= $this->msg( 'challengeview-acceptance' )->plain();
				} else {
					$out .= $this->msg( 'challengeview-sent-to-you' )->plain();
					$out .= '<br /><br />
					<select id="challenge_action">
						<option value="1">' . $this->msg( 'challengeview-accept' )->plain() . '</option>
						<option value="-1">' . $this->msg( 'challengeview-reject' )->plain() . '</option>
						<option value="2">' . $this->msg( 'challengeview-counterterms' )->plain() . "</option>
					</select>
					<input type=\"hidden\" id=\"status\" value=\"{$challenge['status']}\" />
					<input type=\"hidden\" id=\"challenge_id\" value=\"{$challenge['id']}\" />
					<input type=\"button\" class=\"challenge-response-button site-button\" value=\"" . $this->msg( 'challengeview-submit-button' )->plain() .
						'" />';
				}
				break;
			case 1:
			case 2: // treat "counter terms" as "in progress" b/c that's basically what it is
				if (
					!$this->getUser()->isAllowed( 'challengeadmin' ) ||
					$challenge['challenger_actor'] == $this->getUser()->getActorId() ||
					$challenge['challengee_actor'] == $this->getUser()->getActorId()
				)
				{
					$out .= $this->msg( 'challengeview-inprogress' )->plain();
				} else {
					$challengerName = User::newFromActorId( $challenge['challenger_actor'] )->getName();
					$challengeeName = User::newFromActorId( $challenge['challengee_actor'] )->getName();
					$out .= $this->msg( 'challengeview-admintext' )->parse();
					$out .= "<br /><br />
					<select id=\"challenge_winner_actorid\">
					 	<option value=\"{$challenge['challenger_actor']}\">{$challengerName}</option>
						<option value=\"{$challenge['challengee_actor']}\">{$challengeeName}</option>
						<option value=\"-1\">";
					$out .= $this->msg( 'challengeview-push' )->plain();
					$out .= "</option>
					</select>
					<input type=\"hidden\" id=\"status\" value=\"{$challenge['status']}\" />
					<input type=\"hidden\" id=\"challenge_id\" value=\"{$challenge['id']}\" />
					<input type=\"button\" class=\"challenge-approval-button site-button\" value=\"" .
						$this->msg( 'challengeview-submit-button' )->plain() .
					'" />';
				}
				break;
			case -1:
				$out .= $this->msg( 'challengeview-rejected' )->plain();
				break;
			case -2:
				$out .= $this->msg( 'challengeview-removed' )->plain();
				break;
			case 3:
				if ( $challenge['winner_actor'] != -1 ) {
					$winnerName = User::newFromActorId( $challenge['winner_actor'] )->getName();
					$out .= $this->msg( 'challengeview-won-by', $winnerName )->parse();
					$out .= '<br /><br />';
					if ( $challenge['rating'] ) {
						$out .= '<span class="challenge-title">';
						$out .= $this->msg( 'challengeview-rating' )->plain();
						$out .= '</span><br />';
						$out .= $this->msg( 'challengeview-by', $winnerName )->parse();
						$out .= '<br /><br />' . $this->msg( 'challengeview-rating2' )->parse() .
							" <span class=\"challenge-rating-{$c->rating_names[$challenge['rating']]}\">" .
								// For grep: challengeview-rating-negative, challengeview-rating-neutral,
								// challengeview-rating-positive
								$this->msg( 'challengeview-rating-' . $c->rating_names[$challenge['rating']] )->escaped() .
								'</span>
							<br />';
						$out .= $this->msg( 'challengeview-comment', $challenge['rating_comment'] )->parse();
					} else {
						if ( $this->getUser()->getActorId() == $challenge['winner_actor'] ) {
							$out .= '<span class="challenge-title">';
							$out .= $this->msg( 'challengeview-rating' )->plain();
							$out .= '</span><br />
								<span class="challenge-won">';
							$out .= $this->msg( 'challengeview-you-won' )->plain();
							$out .= '</span><br /><br />
								<span class="challenge-form">';
							$out .= $this->msg( 'challengeview-rateloser' )->plain();
							$out .= '</span><br />
								<select id="challenge_rate">
									<option value="1">' . $this->msg( 'challengeview-positive' )->plain() . '</option>
									<option value="-1">' . $this->msg( 'challengeview-negative' )->plain() . '</option>
									<option value="0">' . $this->msg( 'challengeview-neutral' )->plain() . "</option>
								</select>
								<input type=\"hidden\" id=\"status\" value=\"{$challenge['status']}\" />
								<input type=\"hidden\" id=\"challenge_id\" value=\"{$challenge['id']}\" />";
							if ( $challenge['winner_actor'] == $challenge['challenger_actor'] ) {
								$loser_id = $challenge['challengee_actor'];
							} else {
								$loser_id = $challenge['challenger_actor'];
							}
							$out .= "<input type=\"hidden\" id=\"loser_actorid\" value=\"{$loser_id}\" />
							<br /><br /><span class=\"challenge-form\">";
							$out .= $this->msg( 'challengeview-additionalcomments' )->plain();
							$out .= '</span><br />
								<textarea class="createbox" rows="2" cols="50" id="rate_comment"></textarea><br /><br />
								<input type="button" class="challenge-rate-button site-button" value="' .
									$this->msg( 'challengeview-submit-button' )->plain() .
									'" />';
						} else {
							$out .= $this->msg( 'challengeview-notyetrated' )->plain();
						}
					}
				} else {
					$out .= $this->msg( 'challengeview-was-push' )->plain() . '<br /><br />';
				}
			break;
		}

		$template->set( 'challenge-status-html', $out );

		return $template->getHTML();
	}
}
