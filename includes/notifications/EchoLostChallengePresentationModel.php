<?php
/**
 * Formatter for notifications about lost challenges ('challenge-lost')
 */

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\SpecialPage\SpecialPage;

class EchoLostChallengePresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'challenge-lost';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-challenge-lost-bundle', $this->getBundleCount() );
		} else {
			return $this->msg( 'notification-challenge-lost', $this->event->getExtraParam( 'challenge-id' ) );
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
