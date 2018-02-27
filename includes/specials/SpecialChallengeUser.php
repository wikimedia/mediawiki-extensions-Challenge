<?php

class ChallengeUser extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ChallengeUser' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Under which header this special page is listed in Special:SpecialPages?
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}

	/**
	 * Show the special page
	 *
	 * @param mixed $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		$output = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Add CSS & JS via ResourceLoader
		$output->addModuleStyles( 'ext.challenge.user' );
		$output->addModules( array(
			'ext.challenge.js.main',
			'ext.challenge.js.datepicker'
		) );

		$userTitle = Title::newFromDBkey( $request->getVal( 'user', $par ) );
		if ( !$userTitle ) {
			$output->addHTML( $this->displayFormNoUser() );
			return false;
		}

		$this->user_name_to = $userTitle->getText();
		$this->user_id_to = User::idFromName( $this->user_name_to );

		if ( $user->getId() == $this->user_id_to ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-self' )->plain() );
		} elseif ( $this->user_id_to == 0 ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-nouser' )->plain() );
		} elseif ( $user->getId() == 0 ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-login' )->plain() );
		} else {
			if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] === false ) {
				$_SESSION['alreadysubmitted'] = true;
				$c = new Challenge();
				$c->addChallenge(
					$this->getUser(),
					$this->user_name_to,
					$request->getVal( 'info' ),
					$request->getVal( 'date' ),
					$request->getVal( 'description' ),
					$request->getVal( 'win' ),
					$request->getVal( 'lose' )
				);

				$output->setPageTitle(
					$this->msg( 'challengeuser-challenge-sent-title',
						$this->user_name_to )
				);

				$out = '<div class="challenge-links">';
					//$out .= "<a href=\"index.php?title=User:{$this->user_name_to}\">< {$this->user_name_to}'s User Page</a>";
					// $out .= " - <a href=\"index.php?title=Special:ViewGifts&user={$this->user_name_to}\">View All of {$this->user_name_to}'s Gifts</a>";
				if ( $this->getUser()->isLoggedIn() ) {
					// $out .= " - <a href=\"index.php?title=Special:ViewGifts&user={$wgUser->getName()}\">View All of Your Gifts</a>";
				}
				$out .= '</div>';

				$out .= '<div class="challenge-sent-message">';
				$out .= $this->msg( 'challengeuser-sent', $this->user_name_to )->parse();
				$out .= '</div>';

				$out .= '<div class="visualClear"></div>';

				$output->addHTML( $out );
			} else {
				$_SESSION['alreadysubmitted'] = false;
				$output->addHTML( $this->displayForm() );
			}
		}
	}

	function displayFormNoUser() {
		global $wgFriendingEnabled;

		$this->getOutput()->setPageTitle( $this->msg(
			'challengeuser-info-title-no-user' )->plain() );

		// JS required for autocompleting the user name (T152885)
		$this->getOutput()->addModules( 'mediawiki.userSuggest' );

		// @todo FIXME: rename form & HTML classes/IDs
		$output = '<form action="" method="get" enctype="multipart/form-data" name="gift">';
		$output .= Html::hidden( 'title', $this->getPageTitle() );

		$output .= '<div class="give-gift-message">';
		$output .= $this->msg( 'challengeuser-info-body-no-user' )->plain();
		$output .= '</div>';

		if ( $wgFriendingEnabled ) {
			$rel = new UserRelationship( $this->getUser()->getName() );
			$friends = $rel->getRelationshipList( 1 );
			if ( $friends ) {
				$output .= '<div class="give-gift-title">';
				$output .= $this->msg( 'challengeuser-select-friend-from-list' )->plain();
				$output .= '</div>
				<div class="give-gift-selectbox">
				<select id="challenge-user-selector">';
				$output .= '<option value="#" selected="selected">';
				$output .= $this->msg( 'challengeuser-select-friend' )->plain();
				$output .= '</option>';
				foreach ( $friends as $friend ) {
					$output .= Html::element(
						'option',
						array( 'value' => $friend['user_name'] ),
						$friend['user_name']
					);
				}
				$output .= '</select>
				</div>';
			}
		}

		$output .= '<p class="challenge-user-or">';
		$output .= $this->msg( 'challengeuser-or' )->plain();
		$output .= '</p>';
		$output .= '<div class="give-gift-title">';
		$output .= $this->msg( 'challengeuser-type-username' )->plain();
		$output .= '</div>';
		$output .= '<div class="give-gift-textbox">
			<input type="text" width="85" name="user" class="mw-autocomplete-user" value="" />
			<input class="site-button" type="button" value="' .
				$this->msg( 'challengeuser-start-button' )->plain() .
				'" onclick="document.gift.submit()" />
		</div>
		</form>';

		return $output;
	}

	/**
	 * Displays the "challenge a user" form
	 *
	 * @return string Generated HTML for the challenge form
	 */
	function displayForm() {
		$this->getOutput()->setPageTitle(
			$this->msg( 'challengeuser-title-user',
				$this->user_name_to )->parse()
		);

		$user_title = Title::makeTitle( NS_USER, $this->user_name_to );
		$challenge_history_title = SpecialPage::getTitleFor( 'ChallengeHistory' );
		$avatar = new wAvatar( $this->user_id_to, 'l' );

		$pos = Challenge::getUserFeedbackScoreByType( 1, $this->user_id_to );
		$neg = Challenge::getUserFeedbackScoreByType( -1, $this->user_id_to );
		$neu = Challenge::getUserFeedbackScoreByType( 0, $this->user_id_to );
		$total = ( $pos + $neg + $neu );

		$template = new ChallengeUserTemplate();

		$template->set( 'pos', $pos );
		$template->set( 'neg', $neg );
		$template->set( 'neu', $neu );
		$template->set( 'total', $total );
		$template->set( 'class', $this );
		$template->set( 'user_title', $user_title );
		$template->set( 'challenge_history_title', $challenge_history_title );
		$template->set( 'avatar', $avatar );

		return $template->getHTML();
	}
}