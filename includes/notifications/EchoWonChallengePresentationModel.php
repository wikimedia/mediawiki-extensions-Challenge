<?php
/**
 * Formatter for notifications about won challenges ('challenge-won')
 */

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\SpecialPage\SpecialPage;

class EchoWonChallengePresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'challenge-won';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-challenge-won-bundle', $this->getBundleCount() );
		} else {
			return $this->msg( 'notification-challenge-won', $this->event->getExtraParam( 'challenge-id' ) );
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
