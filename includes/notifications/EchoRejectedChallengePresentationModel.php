<?php
/**
 * Formatter for notifications about rejected challenges ('challenge-rejected')
 */
class EchoRejectedChallengePresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'challenge-rejected';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-challenge-rejected-bundle', $this->getBundleCount() );
		} else {
			return $this->msg(
				'notification-challenge-rejected',
				$this->event->getAgent()->getName(),
				$this->event->getExtraParam( 'challenge-id' )
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		return [
			'url' => SpecialPage::getTitleFor( 'ChallengeView', $this->event->getExtraParam( 'challenge-id' ) )->getLocalURL(),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

}
