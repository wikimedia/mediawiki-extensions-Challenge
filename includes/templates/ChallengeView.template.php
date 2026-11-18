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
		$challenge_user_title = SpecialPage::getTitleFor( 'ChallengeUser' );

		$challenge = $this->data['challenge'];
		$user = $this->data['user'];
		$linkRenderer = $this->data['class']->getLinkRenderer();

		// F O R M A T T I N G
		// Copy-pasted from SpecialChallengeHistory.php
		try {
			$fmtDate = $this->data['class']->getLanguage()->userDate( $challenge['date'], $user );
		} catch ( MWException ) {
			$fmtDate = $challenge['date'];
		}

		$challengerName = $this->data['challenger']->getName();
		$challengeeName = $this->data['challengee']->getName();

		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>
	<table class="challenge-main-table">
		<tr>
			<td><?php echo $this->data['avatar1']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-title"><?php echo $this->data['challengerUserLink'] ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['challenger_actor'] ) ?>)
				<br /><?php echo $this->data['challengerHistoryLink']; ?>
				<?php if ( !$this->data['userIsChallenger'] ) { ?>
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['challenger']->getUserPage()->getDBkey() ]
				); ?>
				<?php } ?>
			</td>
			<td>
				<b><?php echo wfMessage( 'challengeview-versus' )->escaped() ?></b>
			</td>
			<td><?php echo $this->data['avatar2']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-link"><?php echo $this->data['challengeeUserLink'] ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['challengee_actor'] ) ?>)
				<br /><?php echo $this->data['challengeeHistoryLink']; ?>
				<?php if ( !$this->data['userIsChallengee'] ) { ?>
				<br /><?php echo $linkRenderer->makeLink(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->escaped(),
					[ 'class' => 'challenge-small-link' ],
					[ 'user' => $this->data['challengee']->getUserPage()->getDBkey() ]
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
				<br /><b><?php
					echo wfMessage( 'challengeview-description', $challengerName )->parse()
				?></b><span class="challenge-description"><?php echo htmlspecialchars( $challenge['description'], ENT_QUOTES ) ?></span>
			</td>
		</tr>
	</table>

	<!--</td></tr></table><br />-->

	<table class="challenge-terms-container">
		<tr>
			<td valign="top">
				<span class="challenge-title"><?php
					echo wfMessage(
						'challengeview-ifwins',
						$challengerName,
						$challengeeName
					)->parse() ?></span>
				<table class="challenge-terms"><tr><td><?php echo htmlspecialchars( $challenge['win_terms'], ENT_QUOTES ) ?></td></tr></table><br />
			</td>
			<td width="20">&nbsp;</td>
			<td valign="top">
				<span class="challenge-title"><?php echo wfMessage(
					'challengeview-ifwins',
					$challengeeName,
					$challengerName
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
