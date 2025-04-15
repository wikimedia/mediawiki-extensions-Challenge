<?php
/**
 * Formatter for notifications about received challenges ('challenge-received')
 */

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\SpecialPage\SpecialPage;

class EchoReceivedChallengePresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'challenge-received';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-challenge-received-bundle', $this->getBundleCount() );
		} else {
			return $this->msg(
				'notification-challenge-received',
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
