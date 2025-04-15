<?php
/**
 * Formatter for notifications about accepted challenges ('challenge-accepted')
 */

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\SpecialPage\SpecialPage;

class EchoAcceptedChallengePresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'challenge-accepted';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			return $this->msg( 'notification-challenge-accepted-bundle', $this->getBundleCount() );
		} else {
			return $this->msg(
				'notification-challenge-accepted',
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
