<?php
/**
 * @file
 */

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * HTML template for Special:ChallengeView
 * @ingroup Templates
 */
class ChallengeViewTemplate extends QuickTemplate {
	public function execute() {
		$challenge_history_title = SpecialPage::getTitleFor( 'ChallengeHistory' );
		$challenge_user_title = SpecialPage::getTitleFor( 'ChallengeUser' );

		$challenge = $this->data['challenge'];
		$user = $this->data['user'];
		$linkRenderer = $this->data['class']->getLinkRenderer();

		// F O R M A T T I N G
		// Copy-pasted from SpecialChallengeHistory.php
		try {
			$fmtDate = $this->data['class']->getLanguage()->userDate( $challenge['date'], $user );
		} catch ( MWException $ex ) {
			$fmtDate = $challenge['date'];
		}

		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>
	<table class="challenge-main-table">
		<tr>
			<td><?php echo $this->data['avatar1']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-title"><?php echo $linkRenderer->makeLink(
					$this->data['title1'],
					$this->data['title1']->getText(),
					[ 'class' => 'challenge-user-link' ]
				) ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['challenger_actor'] ) ?>)
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_history_title,
					wfMessage( 'challengeview-view-history' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['title1']->getDBkey() ]
				); ?>
				<?php if ( $user->getName() !== $this->data['title1']->getText() ) { ?>
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['title1']->getDBkey() ]
				); ?>
				<?php } ?>
			</td>
			<td>
				<b><?php echo wfMessage( 'challengeview-versus' )->escaped() ?></b>
			</td>
			<td><?php echo $this->data['avatar2']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-link"><?php echo $linkRenderer->makeLink(
					$this->data['title2'],
					$this->data['title2']->getText(),
					[ 'class' => 'challenge-user-link' ]
				) ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['challengee_actor'] ) ?>)
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_history_title,
					wfMessage( 'challengeview-view-history' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['title2']->getDBkey() ]
				); ?>
				<?php if ( $user->getName() !== $this->data['title2']->getText() ) { ?>
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['title2']->getDBkey() ]
				); ?>
				<?php } ?>
			</td>
		</tr>
	</table>
	<br />
	<table>
		<tr>
			<td>
				<b><?php echo wfMessage( 'challengeview-event' )->escaped() ?></b> <span class="challenge-event"><?php echo htmlspecialchars( $challenge['info'], ENT_QUOTES ) . ' [' . $fmtDate . ']' ?></span>
				<br /><b><?php echo wfMessage( 'challengeview-description', $userFactory->newFromActorId( $challenge['challenger_actor'] )->getName() )->parse() ?></b><span class="challenge-description"><?php echo htmlspecialchars( $challenge['description'], ENT_QUOTES ) ?></span>
			</td>
		</tr>
	</table>

	<!--</td></tr></table><br />-->

	<table class="challenge-terms-container">
		<tr>
			<td valign="top">
				<span class="challenge-title"><?php echo wfMessage(
					'challengeview-ifwins',
					$userFactory->newFromActorId( $challenge['challenger_actor'] )->getName(),
					$userFactory->newFromActorId( $challenge['challengee_actor'] )->getName()
				)->parse() ?></span>
				<table class="challenge-terms"><tr><td><?php echo htmlspecialchars( $challenge['win_terms'], ENT_QUOTES ) ?></td></tr></table><br />
			</td>
			<td width="20">&nbsp;</td>
			<td valign="top">
				<span class="challenge-title"><?php echo wfMessage(
					'challengeview-ifwins',
					$userFactory->newFromActorId( $challenge['challengee_actor'] )->getName(),
					$userFactory->newFromActorId( $challenge['challenger_actor'] )->getName()
				)->parse() ?></span>
				<table class="challenge-terms"><tr><td><?php echo htmlspecialchars( $challenge['lose_terms'], ENT_QUOTES ) ?></td></tr></table>
			</td>
		</tr>
	</table>

<?php
	if (
		$user->isAllowed( 'challengeadmin' ) &&
		$challenge['challengee_actor'] != $user->getActorId() &&
		$challenge['challenger_actor'] != $user->getActorId() &&
		// Only show this link/form when the challenge has not yet been removed by an admin
		// (yes, of course the type cast is needed :^) thanks, PHP)
		(int)$challenge['status'] !== Challenge::STATUS_REMOVED
	) {
		// NoJS version
		$challengeAction = SpecialPage::getTitleFor( 'ChallengeAction' );
		$noJSform = '<form class="challenge-admin-cancel-form" action="' . htmlspecialchars( $challengeAction->getFullURL( [ 'action' => 1 ] ), ENT_QUOTES ) . '" method="post">';
		$noJSform .= Html::hidden( 'challenge_id', (int)$challenge['id'] );
		$noJSform .= Html::hidden( 'action', 1 );
		$noJSform .= Html::hidden( 'status', -2 );
		$noJSform .= Html::hidden( 'wpAdminToken', $user->getEditToken() );
		$noJSform .= Html::submitButton( wfMessage( 'challengeview-admin' )->text(), [] );
		$noJSform .= '</form>';
		echo $noJSform;
		// JS version
		$adminLink = "<a class=\"challenge-admin-cancel-link\" data-challenge-id=\"{$challenge['id']}\" href=\"#\">";
		$adminLink .= wfMessage( 'challengeview-admin' )->escaped();
		$adminLink .= '</a>';
		$adminLink .= Html::hidden( 'wpAdminToken', $user->getEditToken() );
		echo $adminLink;
	}
?>
	<div class="challenge-line"></div>
	<span class="challenge-title"><?php echo wfMessage( 'challengeview-status' )->escaped() ?></span><br />
	<div class="challenge-status-text">
		<span id="challenge-status">
			<?php echo $this->data['challenge-status-html']; ?>
		</span>
	</div>
	<span id="status2"></span>
<?php
		// phpcs:enable Generic.WhiteSpace.ScopeIndent
	} // execute()
} // class
