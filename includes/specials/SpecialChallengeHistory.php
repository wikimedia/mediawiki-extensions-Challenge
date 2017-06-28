<?php

class ChallengeHistory extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ChallengeHistory' );
	}

	/**
	 * Under which header this special page is listed in Special:SpecialPages?
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}

	private function displayUserHeader( $user_name, $userId ) {
		//$avatar = new wAvatar( $userId, 'l' );
		$pos = Challenge::getUserFeedbackScoreByType( 1, $userId );
		$neg = Challenge::getUserFeedbackScoreByType( -1, $userId );
		$neu = Challenge::getUserFeedbackScoreByType( 0, $userId );
		$total = ( $pos + $neg + $neu );
		$percent = 0;
		if ( $pos ) {
			$percent = $pos / $total * 100;
		}

		$out = '<b>' . $this->msg( 'challengehistory-overall' )->plain() . '</b>: (' .
			Challenge::getUserChallengeRecord( $userId ) . ')<br /><br />';
		$out .= '<b>' . $this->msg( 'challengehistory-ratings-loser' )->plain() . '</b>: <br />';
		$out .= '<span class="challenge-rate-positive">' .
			$this->msg( 'challengehistory-positive' )->plain() . '</span>: ' . $pos . ' (' . $percent . '%)<br />';
		$out .= '<span class="challenge-rate-negative">' .
			$this->msg( 'challengehistory-negative' )->plain() . '</span>: ' . $neg . '<br />';
		$out .= '<span class="challenge-rate-neutral">' .
			$this->msg( 'challengehistory-neutral' )->plain() . '</span>: ' . $neu . '<br /><br />';
		return $out;
	}

	/**
	 * Show the special page
	 *
	 * @param mixed $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgExtensionAssetsPath;

		$request = $this->getRequest();
		$imgPath = $wgExtensionAssetsPath . '/Challenge/resources/images/';
		$spImgPath = $wgExtensionAssetsPath . '/SocialProfile/images/';
		$u = $request->getVal( 'user', $par );

		$this->getOutput()->addModuleStyles( 'ext.challenge.history' );

		$out = $standings_link = '';
		if ( $u ) {
			$userTitle = Title::newFromDBkey( $u );
			if ( $userTitle ) {
				$userId = User::idFromName( $userTitle->getText() );
			} else {
				// invalid user
				// @todo FIXME: in this case, what is $userId when it gets passed
				// to displayUserHeader() below?
			}

			$this->getOutput()->setPageTitle(
				$this->msg( 'challengehistory-users-history',
					$userTitle->getText() )
			);
			$out .= $this->displayUserHeader( $userTitle->getText(), $userId );
		} else {
			$this->getOutput()->setPageTitle( $this->msg( 'challengehistory-recentchallenges' ) );
			$standings_link = " - <img src=\"{$imgPath}userpageIcon.png\" alt=\"\" /> ";
			$standings_link .= Linker::link(
				SpecialPage::getTitleFor( 'ChallengeStandings' ),
				$this->msg( 'challengehistory-view-standings' )->plain()
			);
		}

		$challenge_link = SpecialPage::getTitleFor( 'ChallengeUser' );
		$status = (int) $request->getVal( 'status' );
		$out .= '
		<div class="challenge-nav">
			<div class="challenge-history-filter">' . $this->msg( 'challengehistory-filter' )->plain();
		// @todo CHECKME: is this secure enough?
		$sanitizedUser = htmlspecialchars( $u, ENT_QUOTES );
		$out .= "<select name=\"status-filter\" data-username=\"{$sanitizedUser}\">
				<option value='' " . ( $status == '' && strlen( $status ) == 0 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-all' )->plain() . "</option>
				<option value=0 " . ( $status == 0 && strlen( $status ) == 1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-awaiting' )->plain() . '</option>
				<option value="1"' . ( $status == 1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-inprogress' )->plain() . '</option>
				<option value="-1"' . ( $status == -1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-rejected' )->plain() . '</option>
				<option value="3"' . ( $status == 3 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-completed' )->plain() . "</option>
			</select>
			</div>
			<div class=\"challenge-link\">
				<img src=\"{$spImgPath}challengeIcon.png\" alt=\"\" /> ";
		if ( $u ) {
			$msg = $this->msg( 'challengehistory-challenge-user', $userTitle->getText() )->parse();
		} else {
			$msg = $this->msg( 'challengehistory-challenge-someone' )->plain();
		}
		$out .= Linker::link(
			$challenge_link,
			$msg,
			array(),
			( ( $u ) ? array( 'user' => $u ) : array() )
		);
		$out .= $this->msg( 'word-separator' )->escaped();
		$out .= "{$standings_link}
			</div>
			<div class=\"visualClear\"></div>
		</div>

		<table class=\"challenge-history-table\">
			<tr>
				<td class=\"challenge-history-header\">" . $this->msg( 'challengehistory-event' )->plain() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-challenger-desc' )->plain() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-challenger' )->plain() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-target' )->plain() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-status' )->plain() . '</td>
			</tr>';

		$page = (int) $request->getVal( 'page', 1 );
		$perPage = 25;

		$c = new Challenge();
		$challengeList = $c->getChallengeList(
			$u,
			$status,
			$perPage,
			$page
		);
		$totalChallenges = $c->getChallengeCount();

		if ( $challengeList ) {
			foreach ( $challengeList as $challenge ) {
				// Set up avatars and wiki titles for challenge and target
				$avatar1 = new wAvatar( $challenge['user_id_1'], 's' );
				$avatar2 = new wAvatar( $challenge['user_id_2'], 's' );

				$title1 = Title::makeTitle( NS_USER, $challenge['user_name_1'] );
				$title2 = Title::makeTitle( NS_USER, $challenge['user_name_2'] );

				// Set up titles for pages used in table
				$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
				$challengeViewLink = Linker::link(
					$challenge_view_title,
					htmlspecialchars( $challenge['info'] . ' [' . $challenge['date'] . ']' ),
					array(),
					array( 'id' => $challenge['id'] )
				);

				$av1 = $avatar1->getAvatarURL( array( 'align' => 'absmiddle' ) ); // @todo FIXME: invalid HTML5
				$av2 = $avatar2->getAvatarURL( array( 'align' => 'absmiddle' ) ); // @todo FIXME: invalid HTML5
				$winnerSymbol = Html::element(
					'img',
					array(
						'src' => $imgPath . 'winner-check.gif',
						'alt' => '',
						'align' => 'absmiddle' // @todo FIXME: invalid HTML5
					)
				);

				$out .= "<tr>
					<td class=\"challenge-data\">{$challengeViewLink}</td>
					<td class=\"challenge-data challenge-data-description\">{$challenge['description']}</td>
					<td class=\"challenge-data\">{$av1}";
				$out .= Linker::link(
					$title1,
					$challenge['user_name_1']
				);
				$out .= $this->msg( 'word-separator' )->escaped();
				if ( $challenge['winner_user_id'] == $challenge['user_id_1'] ) {
					$out .= $winnerSymbol;
				}
				$out .= "</td>
					<td class=\"challenge-data\">{$av2}";
				$out .= Linker::link(
					$title2,
					$challenge['user_name_2']
				);
				$out .= $this->msg( 'word-separator' )->escaped();
				if ( $challenge['winner_user_id'] == $challenge['user_id_2'] ) {
					$out .= $winnerSymbol;
				}
				$out .= "</td>
					<td class=\"challenge-data\">{$c->getChallengeStatusName( $challenge['status'] )}</td>
				</tr>";
			}
		} else {
			$out .= '<tr><td class="challenge-history-empty"><br />';
			$out .= $this->msg( 'challengehistory-empty' )->parse();
			$out .= '</td></tr>';
		}

		$out .= '</table>';

		// Build next/prev navigation
		$numOfPages = $totalChallenges / $perPage;

		if ( $numOfPages > 1 && !$u ) {
			$challenge_history_title = SpecialPage::getTitleFor( 'ChallengeHistory' );
			$out .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$out .= Linker::link(
					$challenge_history_title,
					$this->msg( 'challengehistory-prev' )->plain(),
					array(),
					array( 'user' => $user_name, 'page' => ( $page - 1 ) )
				) . $this->msg( 'word-separator' )->escaped();
			}

			if ( ( $total % $perPage ) != 0 ) {
				$numOfPages++;
			}
			if ( $numOfPages >= 9 ) {
				$numOfPages = 9 + $page;
			}

			for ( $i = 1; $i <= $numOfPages; $i++ ) {
				if ( $i == $page ) {
					$out .= ( $i . ' ' );
				} else {
					$out .= Linker::link(
						$challenge_history_title,
						$i,
						array(),
						array( 'user' => $user_name, 'page' => $i )
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $perPage * $page ) ) > 0 ) {
				$out .= $this->msg( 'word-separator' )->escaped() . Linker::link(
					$challenge_history_title,
					$this->msg( 'challengehistory-next' )->plain(),
					array(),
					array( 'user' => $user_name, 'page' => ( $page + 1 ) )
				);
			}
			$out .= '</div>';
		}

		$this->getOutput()->addModules( 'ext.challenge.js.main' );
		$this->getOutput()->addHTML( $out );
	}
}