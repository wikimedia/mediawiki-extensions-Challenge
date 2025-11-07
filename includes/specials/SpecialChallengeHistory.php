<?php

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;

class ChallengeHistory extends SpecialPage {
	private UserFactory $userFactory;

	public function __construct(
		UserFactory $userFactory
	) {
		parent::__construct( 'ChallengeHistory' );
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

		$out = '<b>' . $this->msg( 'challengehistory-overall' )->escaped() . '</b>: (' .
			Challenge::getUserChallengeRecord( $actorId ) . ')<br /><br />';
		$out .= '<b>' . $this->msg( 'challengehistory-ratings-loser' )->escaped() . '</b>: <br />';
		$out .= '<span class="challenge-rate-positive">' .
			$this->msg( 'challengehistory-positive' )->escaped() . '</span>: ' . $pos . ' (' . $percent . '%)<br />';
		$out .= '<span class="challenge-rate-negative">' .
			$this->msg( 'challengehistory-negative' )->escaped() . '</span>: ' . $neg . '<br />';
		$out .= '<span class="challenge-rate-neutral">' .
			$this->msg( 'challengehistory-neutral' )->escaped() . '</span>: ' . $neu . '<br /><br />';
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
		$challenge_history_title = $this->getPageTitle();

		$this->setHeaders();

		$imgPath = $wgExtensionAssetsPath . '/Challenge/resources/images/';
		$spImgPath = $wgExtensionAssetsPath . '/SocialProfile/images/';

		$u = $request->getVal( 'user', $par );
		$user = null;
		$sanitizedUser = '';
		if ( $u ) {
			$user = $this->userFactory->newFromName( $u );
			// @todo CHECKME: is this secure enough?
			$sanitizedUser = htmlspecialchars( $u, ENT_QUOTES );
		}

		$output->addModuleStyles( 'ext.challenge.history' );

		$out = $standings_link = '';
		if ( $user && !$user->isAnon() ) {
			$output->setPageTitle(
				$this->msg( 'challengehistory-users-history', $user->getName() )->escaped()
			);
			$out .= $this->displayUserHeader( $user );
		} else {
			$output->setPageTitle( $this->msg( 'challengehistory-recentchallenges' )->escaped() );
			$standings_link = " - <img src=\"{$imgPath}userpageIcon.png\" alt=\"\" /> ";
			$standings_link .= $linkRenderer->makeLink(
				SpecialPage::getTitleFor( 'ChallengeStandings' ),
				$this->msg( 'challengehistory-view-standings' )->text()
			);
		}

		$challenge_link = SpecialPage::getTitleFor( 'ChallengeUser' );
		$status = (int)$request->getVal( 'status' );
		$out .= '
		<div class="challenge-nav">
			<div class="challenge-history-filter">' . $this->msg( 'challengehistory-filter' )->escaped();
		$submitBtn = $this->msg( 'challengehistory-submit-btn' )->escaped();
		$out .= '<form method="get" action="' . htmlspecialchars( $challenge_history_title->getFullURL(), ENT_QUOTES ) . '">';
		$out .= Html::hidden( 'user', $u );
		$out .= Html::hidden( 'title', $challenge_history_title );
		$out .= "<select name=\"status\" data-username=\"{$sanitizedUser}\">
				<option value='' " . ( $status == '' && strlen( $status ) == 0 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-all' )->escaped() . "</option>
				<option value=0 " . ( $status == 0 && strlen( (string)$status ) == 1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-awaiting' )->escaped() . '</option>
				<option value="1"' . ( $status == 1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-inprogress' )->escaped() . '</option>
				<option value="-1"' . ( $status == -1 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-rejected' )->escaped() . '</option>
				<option value="3"' . ( $status == 3 ? ' selected="selected"' : '' ) . '>' . $this->msg( 'challengehistory-completed' )->escaped() . "</option>
			</select>
			<input type=\"submit\" class=\"site-button nojs-submit-btn\" value=\"{$submitBtn}\" />
			</form>
			</div>
			<div class=\"challenge-link\">
				<img src=\"{$spImgPath}challengeIcon.png\" alt=\"\" /> ";
		if ( $user && !$user->isAnon() ) {
			$msg = $this->msg( 'challengehistory-challenge-user', $user->getName() )->text();
		} else {
			$msg = $this->msg( 'challengehistory-challenge-someone' )->text();
		}
		$out .= $linkRenderer->makeLink(
			$challenge_link,
			$msg,
			[],
			( ( $user && !$user->isAnon() ) ? [ 'user' => $user->getName() ] : [] )
		);
		$out .= $this->msg( 'word-separator' )->escaped();
		$out .= "{$standings_link}
			</div>
			<div class=\"visualClear\"></div>
		</div>

		<table class=\"challenge-history-table\">
			<tr>
				<td class=\"challenge-history-header\">" . $this->msg( 'challengehistory-event' )->escaped() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-challenger-desc' )->escaped() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-challenger' )->escaped() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-target' )->escaped() . '</td>
				<td class="challenge-history-header">' . $this->msg( 'challengehistory-status' )->escaped() . '</td>
			</tr>';

		$page = $request->getInt( 'page', 1 );
		$perPage = 25;

		$c = new Challenge();
		$challengeList = $c->getChallengeList(
			( $user && !$user->isAnon() ) ? $user->getName() : false,
			$status,
			$perPage,
			$page
		);
		$totalChallenges = $c->getChallengeCount();

		if ( $challengeList ) {
			$challenge_view_title = SpecialPage::getTitleFor( 'ChallengeView' );
			$lang = $this->getLanguage();
			$viewingUserObject = $this->getUser();

			foreach ( $challengeList as $challenge ) {
				// Set up avatars and wiki titles for challenge and target
				$challenger = $this->userFactory->newFromActorId( $challenge['challenger_actor'] );
				$challengee = $this->userFactory->newFromActorId( $challenge['challengee_actor'] );
				$avatar1 = new wAvatar( $challenger->getId(), 's' );
				$avatar2 = new wAvatar( $challengee->getId(), 's' );

				$title1 = Title::makeTitle( NS_USER, $challenger->getName() );
				$title2 = Title::makeTitle( NS_USER, $challengee->getName() );

				// F O R M A T T I N G !
				// Old code used the US date format (DD/MM/YYYY)
				// New code displays the full timestamp; not ideal! So we prettify it here.
				// Language#userDate barfs on things which aren't TS_MW timestamps
				// We don't want everything to break just because something couldn't be
				// formatted...
				// Anyway this is probably not an issue outside my devbox, I don't think
				// it's possible for US dates to get inserted into the timestamp on fresh
				// installations of Challenge...
				try {
					$fmtDate = $lang->userDate( $challenge['date'], $viewingUserObject );
				} catch ( InvalidArgumentException ) {
					$fmtDate = $challenge['date'];
				}

				// Set up titles for pages used in table
				$challengeViewLink = $linkRenderer->makeLink(
					$challenge_view_title,
					$challenge['info'] . ' [' . $fmtDate . ']',
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
					<td class=\"challenge-data challenge-data-description\">";
				$out .= htmlspecialchars( $challenge['description'], ENT_QUOTES ) . "</td>
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

		if ( $numOfPages > 1 && !$user->isAnon() ) {
			$out .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$out .= $linkRenderer->makeLink(
					$challenge_history_title,
					$this->msg( 'challengehistory-prev' )->text(),
					[],
					[ 'user' => $user->getName(), 'page' => ( $page - 1 ) ]
				) . $this->msg( 'word-separator' )->escaped();
			}

			if ( ( $totalChallenges % $perPage ) != 0 ) {
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
						(string)$i,
						[],
						[ 'user' => $user->getName(), 'page' => $i ]
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $totalChallenges - ( $perPage * $page ) ) > 0 ) {
				$out .= $this->msg( 'word-separator' )->escaped() . $linkRenderer->makeLink(
					$challenge_history_title,
					$this->msg( 'challengehistory-next' )->text(),
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
