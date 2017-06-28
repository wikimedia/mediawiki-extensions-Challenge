<?php
/**
 * @file
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( -1 );
}

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
?>
	<table class="challenge-main-table">
		<tr>
			<td><?php echo $this->data['avatar1']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-title"><?php echo Linker::link(
					$this->data['title1'],
					$this->data['title1']->getText(),
					array( 'class' => 'challenge-user-link' )
				) ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['user_id_1'] ) ?>)
				<br /><?php echo Linker::link(
					$challenge_history_title,
					wfMessage( 'challengeview-view-history' )->plain(),
					array( 'class' => 'challenge-small-link' ),
					array( 'user' => $this->data['title1']->getDBkey() )
				); ?>
				<?php if ( $user->getName() !== $this->data['title1']->getText() ) { ?>
				<br /><?php echo Linker::link(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->plain(),
					array( 'class' => 'challenge-small-link' ),
					array( 'user' => $this->data['title1']->getDBkey() )
				); ?>
				<?php } ?>
			</td>
			<td>
				<b><?php echo wfMessage( 'challengeview-versus' )->plain() ?></b>
			</td>
			<td><?php echo $this->data['avatar2']->getAvatarURL(); ?></td>
			<td>
				<span class="challenge-user-link"><?php echo Linker::link(
					$this->data['title2'],
					$this->data['title2']->getText(),
					array( 'class' => 'challenge-user-link' )
				) ?></span> (<?php echo $this->data['c']->getUserChallengeRecord( $challenge['user_id_2'] ) ?>)
				<br /><?php echo Linker::link(
					$challenge_history_title,
					wfMessage( 'challengeview-view-history' )->plain(),
					array( 'class' => 'challenge-small-link' ),
					array( 'user' => $this->data['title2']->getDBkey() )
				); ?>
				<?php if ( $user->getName() !== $this->data['title2']->getText() ) { ?>
				<br /><?php echo Linker::link(
					$challenge_user_title,
					wfMessage( 'challengeview-issue-challenge' )->plain(),
					array( 'class' => 'challenge-small-link' ),
					array( 'user' => $this->data['title2']->getDBkey() )
				); ?>
				<?php } ?>
			</td>
		</tr>
	</table>
	<br />
	<table>
		<tr>
			<td>
				<b><?php echo wfMessage( 'challengeview-event' )->plain() ?></b> <span class="challenge-event"><?php echo $challenge['info'] . ' [' . $challenge['date'] . ']' ?></span>
				<br /><b><?php echo wfMessage( 'challengeview-description', $challenge['user_name_1'] )->parse() ?></b><span class="challenge-description"><?php echo $challenge['description'] ?></span>
			</td>
		</tr>
	</table>

	<!--</td></tr></table><br />-->

	<table class="challenge-terms-container">
		<tr>
			<td valign="top">
				<span class="challenge-title"><?php echo wfMessage(
					'challengeview-ifwins',
					$challenge['user_name_1'],
					$challenge['user_name_2']
				)->parse() ?></span>
				<table class="challenge-terms"><tr><td><?php echo $challenge['win_terms'] ?></td></tr></table><br />
			</td>
			<td width="20">&nbsp;</td>
			<td valign="top">
				<span class="challenge-title"><?php echo wfMessage(
					'challengeview-ifwins',
					$challenge['user_name_2'],
					$challenge['user_name_1']
				)->parse() ?></span>
				<table class="challenge-terms"><tr><td><?php echo $challenge['lose_terms'] ?></td></tr></table>
			</td>
		</tr>
	</table>

<?php
	if ( $user->isAllowed( 'challengeadmin' ) && $challenge['user_id_2'] != $user->getId() && $challenge['user_id_1'] != $user->getId() ) {
		$adminLink = "<a class=\"challenge-admin-cancel-link\" data-challenge-id=\"{$challenge['id']}\" href=\"#\">";
		$adminLink .= wfMessage( 'challengeview-admin' )->plain();
		$adminLink .= '</a>';
		echo $adminLink;
	}
?>
	<div class="challenge-line"></div>
	<span class="challenge-title"><?php echo wfMessage( 'challengeview-status' )->plain() ?></span><br />
	<div class="challenge-status-text">
		<span id="challenge-status">
			<?php echo $this->data['challenge-status-html']; ?>
		</span>
	</div>
	<span id="status2"></span>
<?php
	} // execute()
} // class