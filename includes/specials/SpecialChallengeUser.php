<?php

class ChallengeUser extends SpecialPage {

	/** @var User The person getting challenged */
	public $challengee;

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
	 * Whether this special page is listed on Special:SpecialPages or not.
	 *
	 * @return bool
	 */
	function isListed() {
		return $this->getUser()->isRegistered();
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Name of the user to be challenged
	 */
	public function execute( $par ) {
		$output = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$this->setHeaders();

		// Anons don't get to use this special page
		// @todo FIXME: why tf doesn't this redirect to Special:UserLogin like how Special:Watchlist does??? --ashley, 18 August 2020
		$this->requireLogin( 'challengeuser-login' );

		if ( $user->getBlock() ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-error-message-blocked' )->escaped() );
			return;
		}

		// Add CSS & JS via ResourceLoader
		$output->addModuleStyles( 'ext.challenge.user' );
		$output->addModules( [
			'ext.challenge.js.main',
			'ext.challenge.js.datepicker'
		] );

		$urlParamName = 'user';
		$userTitle = Title::newFromDBkey( $request->getVal( 'user', $par ) );
		if ( !$userTitle ) {
			// NoJS fallback
			$urlParamName = 'friend-list';
			$userTitle = Title::newFromDBkey( $request->getVal( 'friend-list', '' ) );
		}

		if ( !$userTitle ) {
			$output->addHTML( $this->displayFormNoUser() );
			return false;
		}

		$this->challengee = User::newFromName( $userTitle->getText() );

		if ( $user->getId() == $this->challengee->getId() ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-self' )->escaped() );
		} elseif ( $this->challengee->getId() == 0 ) {
			$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
			$output->addHTML( $this->msg( 'challengeuser-nouser' )->escaped() );
		} else {
			if (
				$request->wasPosted() &&
				$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
				$_SESSION['alreadysubmitted'] === false
			) {
				$_SESSION['alreadysubmitted'] = true;

				// Server-side validation for all the params, because JS just isn't enough
				// (not to mention that no-JS is also a thing...)
				$requiredFields = [ 'info', 'date', 'description', 'win', 'lose' ];
				$errors = [];
				foreach ( $requiredFields as $requiredField ) {
					if ( $request->getVal( $requiredField ) === '' && $requiredField !== 'date' ) {
						$errorMsgKey = in_array( $requiredField, [ 'win', 'lose' ] ) ?
							"challenge-js-{$requiredField}-terms-required" :
							"challenge-js-{$requiredField}-required";
						// FIXME: stupid special case hack
						if ( $requiredField === 'info' ) {
							$errorMsgKey = 'challenge-js-event-required';
						}
						$errors[] = $errorMsgKey;
					} elseif ( $requiredField === 'date' ) {
						// Date parsing is slightly more complicated than "is the field non-empty?"...
						$date = $request->getVal( 'date' );

						// ...but let's start by checking that we have something to begin with
						if ( !$date || $date === '' ) {
							$errors[] = 'challenge-js-date-required';
							continue;
						}

						// We do? Great.
						$validator = new ChallengeDateValidator;
						if ( !$validator->isDate( $date ) ) {
							// Validator's isDate() is the only method where $validator->error can have
							// an array length of 3 instead of the more usual 1
							$errors[] = $validator->error[0];
						}

						if ( $validator->isFuture( $date ) /*|| $validator->isBackwards( $date )*/ ) {
							$errors[] = $validator->error[0];
						}

						// Alright, our date passed validation, great. Let's move on...
					}
				}

				// Errors? If any, show 'em and prevent going any further because the Challenge
				// class' internals will barf in certain cases of invalid input (e.g. invalid date)
				// @todo FIXME: ensure that validator returning false for isDate() is handled correctly here (msg params)
				if ( $errors !== [] ) {
					$output->setPageTitle( $this->msg( 'challengeuser-error-page-title' ) );
					foreach ( $errors as $msgKey ) {
						$output->addHTML( $this->msg( $msgKey )->escaped() . '<br />' );
					}
					$output->addReturnTo( $this->getPageTitle(), [ $urlParamName => $this->challengee->getName() ] );
					return false;
				}

				$c = new Challenge();
				$c->addChallenge(
					$this->getUser(),
					$this->challengee,
					$request->getVal( 'info' ),
					$request->getVal( 'date' ),
					$request->getVal( 'description' ),
					$request->getVal( 'win' ),
					$request->getVal( 'lose' )
				);

				$output->setPageTitle(
					$this->msg( 'challengeuser-challenge-sent-title', $this->challengee->getName() )
				);

				// @todo FIXME: clean up this mess (empty <div>, empty if() loop) --ashley, 18 August 2020
				$out = '<div class="challenge-links">';
					// $out .= "<a href=\"index.php?title=User:{$this->challengee->getName()}\">< {$this->challengee->getName()}'s User Page</a>";
					// $out .= " - <a href=\"index.php?title=Special:ViewGifts&user={$this->challengee->getName()}\">View All of {$this->challengee->getName()}'s Gifts</a>";
				if ( $this->getUser()->isRegistered() ) {
					// $out .= " - <a href=\"index.php?title=Special:ViewGifts&user={$user->getName()}\">View All of Your Gifts</a>";
				}
				$out .= '</div>';

				$out .= '<div class="challenge-sent-message">';
				$out .= $this->msg( 'challengeuser-sent', $this->challengee->getName() )->parse();
				$out .= '</div>';

				// @todo FIXME: NoJS support for these two buttons
				$out .= '<div class="challenge-buttons">';
				$mainPageURL = Title::newMainPage()->getFullURL();
				$userPageURL = $user->getUserPage()->getFullURL();
				$out .= Html::input( 'wpMainPage', $this->msg( 'mainpage' )->text(), 'button', [
					'size' => 20,
					'class' => 'site-button',
					'onclick' => "window.location='{$mainPageURL}'"
				] );
				$out .= Html::input( 'wpMyProfile', $this->msg( 'challengeuser-your-profile' )->text(), 'button', [
					'size' => 20,
					'class' => 'site-button',
					'onclick' => "window.location='{$userPageURL}'"
				] );
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
			'challengeuser-info-title-no-user' )->escaped() );

		// JS required for autocompleting the user name (T152885)
		$this->getOutput()->addModules( 'mediawiki.userSuggest' );

		// @todo FIXME: rename form & HTML classes/IDs
		$output = '<form action="" method="get" enctype="multipart/form-data" name="gift">';
		$output .= Html::hidden( 'title', $this->getPageTitle() );

		$output .= '<div class="give-gift-message">';
		$output .= $this->msg( 'challengeuser-info-body-no-user' )->escaped();
		$output .= '</div>';

		$friends = false;
		if ( $wgFriendingEnabled ) {
			$listLookup = new RelationshipListLookup( $this->getUser() );
			$friends = $listLookup->getFriendList();
			if ( $friends ) {
				$output .= '<div class="give-gift-title">';
				$output .= $this->msg( 'challengeuser-select-friend-from-list' )->escaped();
				$output .= '</div>
				<div class="give-gift-selectbox">
				<select id="challenge-user-selector" name="friend-list">';
				$output .= '<option value="#" selected="selected">';
				$output .= $this->msg( 'challengeuser-select-friend' )->escaped();
				$output .= '</option>';
				foreach ( $friends as $friend ) {
					$friendUser = User::newFromActorId( $friend['actor'] );
					if ( !$friendUser || !$friendUser instanceof User ) {
						continue;
					}
					$output .= Html::element(
						'option',
						[ 'value' => $friendUser->getName() ],
						$friendUser->getName()
					);
				}
				$output .= '</select>
				</div>';
			}
		}

		// Display the 'or' only if friending is enabled _and_ the current user has some friends;
		// no point in displaying it if friending is enabled but the current user's
		// friend list is empty (e.g. brand user new account)
		if ( $wgFriendingEnabled && $friends ) {
			$output .= '<p class="challenge-user-or">';
			$output .= $this->msg( 'challengeuser-or' )->escaped();
			$output .= '</p>';
		}

		$output .= '<div class="give-gift-title">';
		$output .= $this->msg( 'challengeuser-type-username' )->escaped();
		$output .= '</div>';
		$output .= '<div class="give-gift-textbox">
			<input type="text" width="85" name="user" class="mw-autocomplete-user" value="" />
			<input class="site-button" type="submit" value="' .
				$this->msg( 'challengeuser-start-button' )->escaped() .
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
			$this->msg( 'challengeuser-title-user', $this->challengee->getName() )->parse()
		);

		$user_title = Title::makeTitle( NS_USER, $this->challengee->getName() );
		$challengeeActorId = $this->challengee->getActorId();
		$challenge_history_title = SpecialPage::getTitleFor( 'ChallengeHistory' );
		$avatar = new wAvatar( $this->challengee->getId(), 'l' );

		$pos = Challenge::getUserFeedbackScoreByType( 1, $challengeeActorId );
		$neg = Challenge::getUserFeedbackScoreByType( -1, $challengeeActorId );
		$neu = Challenge::getUserFeedbackScoreByType( 0, $challengeeActorId );
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
