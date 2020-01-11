<?php

class ChallengeStandings extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ChallengeStandings' );
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
	 * @param mixed $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		$this->getOutput()->setPageTitle( $this->msg( 'challengestandings-title' ) );

		$out = '<table class="challenge-standings-table">
			<tr>
				<td class="challenge-standings-title">#</td>
				<td class="challenge-standings-title">' . $this->msg( 'challengestandings-user' )->plain() . '</td>
				<td class="challenge-standings-title">' . $this->msg( 'challengestandings-w' )->plain() . '</td>
				<td class="challenge-standings-title">' . $this->msg( 'challengestandings-l' )->plain() . '</td>
				<td class="challenge-standings-title">' . $this->msg( 'challengestandings-t' )->plain() . '</td>
				<td class="challenge-standings-title">%</td>
				<td class="challenge-standings-title"></td>
			</tr>';

		$dbr = wfGetDB( DB_REPLICA );
		$sql = "SELECT challenge_record_actor, challenge_wins, challenge_losses, challenge_ties, (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) AS winning_percentage FROM {$dbr->tableName( 'challenge_user_record' )} ORDER BY (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) DESC, challenge_wins DESC";
		$res = $dbr->query( $dbr->limitResult( $sql, 25 /* $limit */, 0 /* $offset */ ), __METHOD__ );
		$x = 1;

		$linkRenderer = $this->getLinkRenderer();

		foreach ( $res as $row ) {
			$recordHolder = User::newFromActorId( $row->challenge_record_actor );
			$recordHolderName = $recordHolder->getName();
		 	$avatar1 = new wAvatar( $recordHolder->getId(), 's' );
		 	$out .= '<tr>
				<td class="challenge-standings">' . $x . '</td>
				<td class="challenge-standings">';
			$out .= $avatar1->getAvatarURL( [ 'align' => 'absmiddle' ] ); // @todo FIXME: invalid HTML5
			$out .= $linkRenderer->makeLink(
				SpecialPage::getTitleFor( 'ChallengeHistory' ),
				$recordHolderName,
				[ 'class' => 'challenge-standings-history-link' ],
				[ 'user' => $recordHolderName ]
			);
			$out .= $this->msg( 'word-separator' )->escaped();
			$out .= $user1Icon . '</td>';

			$out .= '<td class="challenge-standings">' . $row->challenge_wins . '</td>
					<td class="challenge-standings">' . $row->challenge_losses . '</td>
					<td class="challenge-standings">' . $row->challenge_ties . '</td>
					<td class="challenge-standings">' .
						// @todo FIXME: not i18n-compatible, should use $this->getLanguage()->formatNum( $row->winning_percentage ) or something instead...
						str_replace( '.0', '.', number_format( $row->winning_percentage, 3 ) ) .
					'</td>';

			if ( $recordHolderName != $this->getUser()->getName() ) {
				$out .= '<td class="challenge-standings">';
				$out .= $linkRenderer->makeLink(
					SpecialPage::getTitleFor( 'ChallengeUser' ),
					$this->msg( 'challengestandings-challengeuser' )->plain(),
					[ 'class' => 'challenge-standings-user-link' ],
					[ 'user' => $recordHolderName ]
				);
				$out .= '</td>';
			}

			$out .= '</td></tr>';
			$x++;
		}

		$out .= '</table>';

		// No rows = nothing to display. This is the case usually when the
		// extension was recently installed, as the users haven't yet had the
		// time to challenge each other. Display an informational message in
		// that case.
		if ( $dbr->numRows( $res ) === 0 ) {
			$out = $this->msg( 'challengestandings-empty' )->parse();
		}

		$this->getOutput()->addHTML( $out );
	}
}