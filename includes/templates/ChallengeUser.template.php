<?php
/**
 * @file
 */

/**
 * HTML template for Special:ChallengeUser
 * @ingroup Templates
 */
class ChallengeUserTemplate extends QuickTemplate {
	public function execute() {
		$linkRenderer = $this->data['class']->getLinkRenderer();
		$challengee = $this->data['class']->challengee;
?>
	<div class="challenge-user-top">
		<?php echo $linkRenderer->makeLink(
			$this->data['user_title'],
			$this->data['class']->msg(
				'challengeuser-view-userpage',
				$challengee->getName()
			)->text()
		) . ' - ' .
		$linkRenderer->makeLink(
			$this->data['challenge_history_title'],
			$this->data['class']->msg( 'challengeuser-completehistory' )->text(),
			[],
			[ 'user' => $challengee->getName() ]
		) . ' - ' .
		$linkRenderer->makeLink(
			$this->data['challenge_history_title'],
			$this->data['class']->msg( 'challengeuser-view-all-challenges' )->text()
		);
		?>
	</div>
		<div class="challenge-info">
			<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-info-title' )->plain() ?></div>
			<?php echo $this->data['class']->msg( 'challengeuser-info-body' )->plain() ?>
		</div>
		<div class="challenge-user-info">
			<div class="challenge-user-avatar">
				<?php echo $this->data['avatar']->getAvatarURL( [ 'align' => 'middle' ] ); ?>
			</div>
			<div class="challenge-user-stats">
			<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-users-stats', $challengee->getName() )->escaped(); ?></div>
			<div class="challenge-user-record"><?php echo $this->data['class']->msg( 'challengeuser-record' )->plain() ?> <b><?php echo Challenge::getUserChallengeRecord( $challengee->getActorId() ) ?></b></div>
			<div class="challenge-user-feedback"><?php echo $this->data['class']->msg( 'challengeuser-feedback' )->numParams( $this->data['total'] )->parse() ?> (<?php echo
			$this->data['class']->getLanguage()->pipeList( [
				$this->data['class']->msg( 'challengehistory-positive2' )->numParams( $this->data['pos'] )->parse(),
				$this->data['class']->msg( 'challengehistory-negative2' )->numParams( $this->data['neg'] )->parse(),
				$this->data['class']->msg( 'challengehistory-neutral2' )->numParams( $this->data['neu'] )->parse()
			] ); ?>)</div>
		</div>
	</div>
	<div class="visualClear"></div>

	<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-enter-info' )->plain() ?></div>
	<form action="" method="post" enctype="multipart/form-data" name="challenge">
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-event' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="35" name="info" id="info" value="" />
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-date' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="10" name="date" id="date" value="" />
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-description' )->plain() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="50" name="description" id="description" value="" />
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-winterms' )->plain() ?></div>
			<div class="challenge-form">
				<textarea class="createbox" name="win" id="win" rows="2" cols="50"></textarea>
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-loseterms' )->plain() ?></div>
			<div class="challenge-form">
			<textarea class="createbox" name="lose" id="lose" rows="2" cols="50"></textarea>
			</div>
		</div>
		<div class="challenge-buttons">
			<input type="button" class="createbox challenge-send-button site-button" value="<?php echo $this->data['class']->msg( 'challengeuser-submit-button' )->plain() ?>" size="20" />
		</div>
		<div class="visualClear"></div>
		<input type="hidden" name="wpEditToken" value="<?php echo $this->data['class']->getUser()->getEditToken() ?>" />
	</form>
<?php
	} // execute()
} // class
