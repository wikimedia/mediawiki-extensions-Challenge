<?php

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class ChallengeStandings extends SpecialPage {
	private ILoadBalancer $loadBalancer;
	private UserFactory $userFactory;

	public function __construct(
		ILoadBalancer $loadBalancer,
		UserFactory $userFactory
	) {
		parent::__construct( 'ChallengeStandings' );
		$this->loadBalancer = $loadBalancer;
		$this->userFactory = $userFactory;
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
		$this->setHeaders();

		$this->getOutput()->setPageTitle( $this->msg( 'challengestandings-title' )->parse() );

		$this->getOutput()->addModuleStyles( 'ext.challenge.standings' );

		$out = '<table class="challenge-standings-table">
			<tr>
				<td class="challenge-standings-title">#</td>
				<td class="challenge-standings-title">' . $this->msg( 'challengestandings-user' )->escaped() . '</td>
				<td class="challenge-standings-title explain" title="' . $this->msg( 'challengestandings-tooltip-wins' )->escaped() . '">' . $this->msg( 'challengestandings-w' )->escaped() . '</td>
				<td class="challenge-standings-title explain" title="' . $this->msg( 'challengestandings-tooltip-losses' )->escaped() . '">' . $this->msg( 'challengestandings-l' )->escaped() . '</td>
				<td class="challenge-standings-title explain" title="' . $this->msg( 'challengestandings-tooltip-ties' )->escaped() . '">' . $this->msg( 'challengestandings-t' )->escaped() . '</td>
				<td class="challenge-standings-title">%</td>
				<td class="challenge-standings-title"></td>
			</tr>';

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$sql = "SELECT challenge_record_actor, challenge_wins, challenge_losses, challenge_ties, (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) AS winning_percentage FROM {$dbr->tableName( 'challenge_user_record' )} ORDER BY (challenge_wins / (challenge_wins + challenge_losses + challenge_ties) ) DESC, challenge_wins DESC";
		$res = $dbr->query( $dbr->limitResult( $sql, 25 /* $limit */, 0 /* $offset */ ), __METHOD__ );
		$x = 1;

		$linkRenderer = $this->getLinkRenderer();

		foreach ( $res as $row ) {
			$recordHolder = $this->userFactory->newFromActorId( $row->challenge_record_actor );
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
			$out .= '</td>';

			$out .= '<td class="challenge-standings">' . (int)$row->challenge_wins . '</td>
					<td class="challenge-standings">' . (int)$row->challenge_losses . '</td>
					<td class="challenge-standings">' . (int)$row->challenge_ties . '</td>
					<td class="challenge-standings">' .
						// @todo FIXME: not i18n-compatible, should use $this->getLanguage()->formatNum( $row->winning_percentage ) or something instead...
						str_replace( '.0', '.', number_format( (int)$row->winning_percentage, 3 ) ) .
					'</td>';

			if ( $recordHolderName != $this->getUser()->getName() ) {
				$out .= '<td class="challenge-standings">';
				$out .= $linkRenderer->makeLink(
					SpecialPage::getTitleFor( 'ChallengeUser' ),
					$this->msg( 'challengestandings-challengeuser' )->text(),
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
		if ( $res->numRows() === 0 ) {
			$out = $this->msg( 'challengestandings-empty' )->parse();
		}

		$this->getOutput()->addHTML( $out );
	}
}
