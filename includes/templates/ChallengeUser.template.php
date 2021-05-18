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
			<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-info-title' )->escaped() ?></div>
			<?php echo $this->data['class']->msg( 'challengeuser-info-body' )->escaped() ?>
		</div>
		<div class="challenge-user-info">
			<div class="challenge-user-avatar">
				<?php echo $this->data['avatar']->getAvatarURL( [ 'align' => 'middle' ] ); ?>
			</div>
			<div class="challenge-user-stats">
			<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-users-stats', $challengee->getName() )->escaped(); ?></div>
			<div class="challenge-user-record"><?php echo $this->data['class']->msg( 'challengeuser-record' )->escaped() ?> <b><?php echo Challenge::getUserChallengeRecord( $challengee->getActorId() ) ?></b></div>
			<div class="challenge-user-feedback"><?php echo $this->data['class']->msg( 'challengeuser-feedback' )->numParams( $this->data['total'] )->parse() ?> (<?php echo $this->data['class']->getLanguage()->pipeList( [
				$this->data['class']->msg( 'challengehistory-positive2' )->numParams( $this->data['pos'] )->parse(),
				$this->data['class']->msg( 'challengehistory-negative2' )->numParams( $this->data['neg'] )->parse(),
				$this->data['class']->msg( 'challengehistory-neutral2' )->numParams( $this->data['neu'] )->parse()
			] ); ?>)</div>
		</div>
		<div class="visualClear"></div>
		<div class="challenge-rules">
			<?php echo $linkRenderer->makeKnownLink(
				Title::newFromText( $this->data['class']->msg( 'challengeuser-rules-page' )->inContentLanguage()->text() ),
				$this->data['class']->msg( 'challengeuser-rules' )->text()
			); ?>
		</div>
	</div>
	<div class="visualClear"></div>

	<div class="challenge-user-title"><?php echo $this->data['class']->msg( 'challengeuser-enter-info' )->escaped() ?></div>
	<form action="" method="post" enctype="multipart/form-data" name="challenge">
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-event' )->escaped() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="35" name="info" id="info" value="" />
			</div>
			<div class="challenge-countdown-container">
			<?php echo $this->data['class']->msg(
				'challengeuser-characters-left',
				'<input readonly="readonly" type="text" id="info-countdown" value="200" />'
			)->text() ?>
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-date' )->escaped() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="10" name="date" id="date" value="" />
			</div>
		</div>
		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-description' )->escaped() ?></div>
			<div class="challenge-form">
				<input type="text" class="createbox" size="50" name="description" id="description" value="" />
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-winterms' )->escaped() ?></div>
			<div class="challenge-form">
				<textarea class="createbox" name="win" id="win" rows="2" cols="50"></textarea>
			</div>
			<div class="challenge-countdown-container">
			<?php echo $this->data['class']->msg(
				'challengeuser-characters-left',
				'<input readonly="readonly" type="text" id="win-countdown" value="200" />'
			)->text() ?>
			</div>
		</div>

		<div class="challenge-field">
			<div class="challenge-label"><?php echo $this->data['class']->msg( 'challengeuser-loseterms' )->escaped() ?></div>
			<div class="challenge-form">
				<textarea class="createbox" name="lose" id="lose" rows="2" cols="50"></textarea>
			</div>
			<div class="challenge-countdown-container">
			<?php echo $this->data['class']->msg(
				'challengeuser-characters-left',
				'<input readonly="readonly" type="text" id="lose-countdown" value="200" />'
			)->text() ?>
			</div>
		</div>
		<div class="challenge-buttons">
			<input type="submit" class="createbox challenge-send-button site-button" value="<?php echo $this->data['class']->msg( 'challengeuser-submit-button' )->escaped() ?>" size="20" />
		</div>
		<div class="visualClear"></div>
		<input type="hidden" name="wpEditToken" value="<?php echo $this->data['class']->getUser()->getEditToken() ?>" />
	</form>
<?php
	} // execute()
} // class
