<?php
/**
 * @file
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( -1 );
}

/**
 * HTML template for Special:ChallengeUser
 * @ingroup Templates
 */
class ChallengeUserTemplate extends QuickTemplate {
	public function execute() {
?>
	<div class="challenge-user-top">
		<?php echo Linker::link(
			$this->data['user_title'],
			wfMessage(
				'challengeuser-view-userpage',
				$this->data['class']->user_name_to
			)->escaped()
		) . ' - ' .
		Linker::link(
			$this->data['challenge_history_title'],
			wfMessage( 'challengeuser-completehistory' )->plain(),
			[],
			[ 'user' => $this->data['class']->user_name_to ]
		) . ' - ' .
		Linker::link(
			$this->data['challenge_history_title'],
			wfMessage( 'challengeuser-view-all-challenges' )->plain()
		);
		?>
	</div>
		<div class="challenge-info">
			<div class="challenge-user-title"><?php echo wfMessage( 'challengeuser-info-title' )->plain() ?></div>
			<?php echo wfMessage( 'challengeuser-info-body' )->plain() ?>
		</div>
		<div class="challenge-user-info">
			<div class="challenge-user-avatar">
				<?php echo $this->data['avatar']->getAvatarURL( [ 'align' => 'middle' ] ); ?>
			</div>
			<div class="challenge-user-stats">
			<div class="challenge-user-title"><?php echo wfMessage( 'challengeuser-users-stats', $this->data['class']->user_name_to )->escaped(); ?></div>
			<div class="challenge-user-record"><?php echo wfMessage( 'challengeuser-record' )->plain() ?> <b><?php echo Challenge::getUserChallengeRecord( $this->data['class']->user_id_to ) ?></b></div>
			<div class="challenge-user-feedback"><?php echo wfMessage( 'challengeuser-feedback' )->numParams( $this->data['total'] )->parse() ?> (<?php echo
			$this->data['class']->getLanguage()->pipeList( [
				wfMessage( 'challengehistory-positive2' )->numParams( $this->data['pos'] )->parse(),
				wfMessage( 'challengehistory-negative2' )->numParams( $this->data['neg'] )->parse(),
				wfMessage( 'challengehistory-neutral2' )->numParams( $this->data['neu'] )->parse()
			] ); ?>)</div>
		</div>
	</div>
	<div class="visualClear"></div>

	<div class="challenge-user-title"><?php echo wfMessage( 'challengeuser-enter-info' )->plain() ?></div>
	<form action="" method="post" enctype="multipart/form-data" name="challenge">
		<div class="challenge-field">
			<div class="challenge-label"><?php echo wfMessage( 'challengeuser-event' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="35" name="info" id="info" value="" />
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo wfMessage( 'challengeuser-date' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="10" name="date" id="date" value="" />
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo wfMessage( 'challengeuser-description' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="50" name="description" id="description" value="" />
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo wfMessage( 'challengeuser-winterms' )->plain() ?></div>
			<div class="challenge-form">
				<textarea class="createbox" name="win" id="win" rows="2" cols="50"></textarea>
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo wfMessage( 'challengeuser-loseterms' )->plain() ?></div>
			<div class="challenge-form">
			<textarea class="createbox" name="lose" id="lose" rows="2" cols="50"></textarea>
			</div>
		</div>
		<div class="challenge-buttons">
			<input type="button" class="createbox challenge-send-button site-button" value="<?php echo wfMessage( 'challengeuser-submit-button' )->plain() ?>" size="20" />
		</div>
		<div class="visualClear"></div>
	</form>
<?php
	} // execute()
} // class