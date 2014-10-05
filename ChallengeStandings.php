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

		$dbr = wfGetDB( DB_SLAVE );
		$sql = "SELECT challenge_record_username, challenge_record_user_id, challenge_wins, challenge_losses, challenge_ties, (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) AS winning_percentage FROM {$dbr->tableName( 'challenge_user_record' )} ORDER BY (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) DESC, challenge_wins DESC LIMIT 0,25";
		$res = $dbr->query( $sql, __METHOD__ );
		$x = 1;

		foreach ( $res as $row ) {
		 	$avatar1 = new wAvatar( $row->challenge_record_user_id, 's' );
		 	$out .= '<tr>
				<td class="challenge-standings">' . $x . '</td>
				<td class="challenge-standings">';
			$out .= $avatar1->getAvatarURL( array( 'align' => 'absmiddle' ) ); // @todo FIXME: invalid HTML5
			$out .= Linker::link(
				SpecialPage::getTitleFor( 'ChallengeHistory' ),
				$row->challenge_record_username,
				array( 'class' => 'challenge-standings-history-link' ),
				array( 'user' => $row->challenge_record_username )
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

			if ( $row->challenge_record_username != $this->getUser()->getName() ) {
				$out .= '<td class="challenge-standings">';
				$out .= Linker::link(
					SpecialPage::getTitleFor( 'ChallengeUser' ),
					$this->msg( 'challengestandings-challengeuser' )->plain(),
					array( 'class' => 'challenge-standings-user-link' ),
					array( 'user' => $row->challenge_record_username )
				);
				$out .= '</td>';
			}

			$out .= '</td></tr>';
			$x++;
		}

		$out .= '</table>';

		$this->getOutput()->addHTML( $out );
	}
}