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

	/**
	 * @param User $user
	 * @return string HTML
	 */
	private function displayUserHeader( $user ) {
		$actorId = $user->getActorId();
		$pos = Challenge::getUserFeedbackScoreByType( 1, $actorId );
		$neg = Challenge::getUserFeedbackScoreByType( -1, $actorId );
		$neu = Challenge::getUserFeedbackScoreByType( 0, $actorId );
		$total = ( $pos + $neg + $neu );
		$percent = 0;
		if ( $pos ) {
			$percent = $pos / $total * 100;
		}

		$out = '<b>' . $this->msg( 'challengehistory-overall' )->plain() . '</b>: (' .
			Challenge::getUserChallengeRecord( $actorId ) . ')<br /><br />';
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

		$linkRenderer = $this->getLinkRenderer();
		$output = $this->getOutput();
		$request = $this->getRequest();

		$imgPath = $wgExtensionAssetsPath . '/Challenge/resources/images/';
		$spImgPath = $wgExtensionAssetsPath . '/SocialProfile/images/';

		$u = $request->getVal( 'user', $par );
		$user = User::newFromName( $u );

		$output->addModuleStyles( 'ext.challenge.history' );

		$out = $standings_link = '';
		if ( $user ) {
			$output->setPageTitle(
				$this->msg( 'challengehistory-users-history', $user->getName() )
			);
			$out .= $this->displayUserHeader( $user );
		} else {
			$output->setPageTitle( $this->msg( 'challengehistory-recentchallenges' ) );
			$standings_link = " - <img src=\"{$imgPath}userpageIcon.png\" alt=\"\" /> ";
			$standings_link .= $linkRenderer->makeLink(
				SpecialPage::getTitleFor( 'ChallengeStandings' ),
				$this->msg( 'challengehistory-view-standings' )->plain()
			);
		}

		$challenge_link = SpecialPage::getTitleFor( 'ChallengeUser' );
		$status = (int)$request->getVal( 'status' );
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
		if ( $user ) {
			$msg = $this->msg( 'challengehistory-challenge-user', $user->getName() )->parse();
		} else {
			$msg = $this->msg( 'challengehistory-challenge-someone' )->plain();
		}
		$out .= $linkRenderer->makeLink(
			$challenge_link,
			$msg,
			[],
			( ( $user ) ? [ 'user' => $user->getName() ] : [] )
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

		$page = (int)$request->getVal( 'page', 1 );
		$perPage = 25;

		$c = new Challenge();
		$challengeList = $c->getChallengeList(
			( $user ) ? $user->getName() : false,
			$status,
			$perPage,
			$page
		);
		$totalChallenges = $c->getChallengeCount();

		if ( $challengeList ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );

			foreach ( $challengeList as $challenge ) {
				// Set up avatars and wiki titles for challenge and target
				$challenger = User::newFromActorId( $challenge['challenger_actor'] );
				$challengee = User::newFromActorId( $challenge['challengee_actor'] );
				$avatar1 = new wAvatar( $challenger->getId(), 's' );
				$avatar2 = new wAvatar( $challengee->getId(), 's' );

				$title1 = Title::makeTitle( NS_USER, $challenger->getName() );
				$title2 = Title::makeTitle( NS_USER, $challengee->getName() );

				// Set up titles for pages used in table
				$challengeViewLink = $linkRenderer->makeLink(
					$challenge_view_title,
					$challenge['info'] . ' [' . $challenge['date'] . ']',
					[],
					[ 'id' => $challenge['id'] ]
				);

				$av1 = $avatar1->getAvatarURL( [ 'align' => 'absmiddle' ] ); // @todo FIXME: invalid HTML5
				$av2 = $avatar2->getAvatarURL( [ 'align' => 'absmiddle' ] ); // @todo FIXME: invalid HTML5
				$winnerSymbol = Html::element(
					'img',
					[
						'src' => $imgPath . 'checkmark.svg', // 'winner-check.gif',
						'alt' => '',
						'height' => '20px',
						'width' => '20px',
						'align' => 'absmiddle' // @todo FIXME: invalid HTML5
					]
				);

				$out .= "<tr>
					<td class=\"challenge-data\">{$challengeViewLink}</td>
					<td class=\"challenge-data challenge-data-description\">{$challenge['description']}</td>
					<td class=\"challenge-data\">{$av1}";
				$out .= $linkRenderer->makeLink(
					$title1,
					$challenger->getName()
				);
				$out .= $this->msg( 'word-separator' )->escaped();
				if ( $challenge['winner_actor'] == $challenge['challenger_actor'] ) {
					$out .= $winnerSymbol;
				}
				$out .= "</td>
					<td class=\"challenge-data\">{$av2}";
				$out .= $linkRenderer->makeLink(
					$title2,
					$challengee->getName()
				);
				$out .= $this->msg( 'word-separator' )->escaped();
				if ( $challenge['winner_actor'] == $challenge['challengee_actor'] ) {
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

		if ( $numOfPages > 1 && $user ) {
			$challenge_history_title = SpecialPage::getTitleFor( 'ChallengeHistory' );
			$out .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$out .= $linkRenderer->makeLink(
					$challenge_history_title,
					$this->msg( 'challengehistory-prev' )->plain(),
					[],
					[ 'user' => $user->getName(), 'page' => ( $page - 1 ) ]
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
					$out .= $linkRenderer->makeLink(
						$challenge_history_title,
						$i,
						[],
						[ 'user' => $user->getName(), 'page' => $i ]
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $perPage * $page ) ) > 0 ) {
				$out .= $this->msg( 'word-separator' )->escaped() . $linkRenderer->makeLink(
					$challenge_history_title,
					$this->msg( 'challengehistory-next' )->plain(),
					[],
					[ 'user' => $user->getName(), 'page' => ( $page + 1 ) ]
				);
			}
			$out .= '</div>';
		}

		$output->addModules( 'ext.challenge.js.main' );
		$output->addHTML( $out );
	}
}