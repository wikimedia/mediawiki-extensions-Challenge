var Challenge = {
	chooseFriend: function( friend ) {
		window.location = mw.util.getUrl( 'Special:ChallengeUser', { 'user': friend } );
	},

	changeFilter: function( user, status ) {
		window.location = mw.util.getUrl( 'Special:ChallengeHistory', { 'user': user, 'status': status } );
	},

	send: function() {
		var err = '',
			req = [
				// field name | i18n message name
				'info|challenge-js-event-required',
				'date|challenge-js-date-required',
				'description|challenge-js-description-required',
				'win|challenge-js-win-terms-required',
				'lose|challenge-js-lose-terms-required'
			];

		for ( var x = 0; x <= req.length - 1; x++ ) {
			var fld = req[x].split( '|' );
			if ( document.getElementById( fld[0] ) == '' ) {
				err += mw.msg( fld[1] ) + '\n';
			}
		}

		if ( !err ) {//&& isDate($F('date'))==true){
			document.challenge.submit();
		} else {
			if ( err ) {
				alert( err );
			}
		}
	},

	cancel: function( id ) {
		$.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'index' ),
			data: {
				title: 'Special:ChallengeAction',
				action: 1,
				'id': id,
				status: -2
			}
		} ).done( function() {
			var text = mw.msg( 'challenge-js-challenge-removed' );
			alert( text );
			$( '#challenge-status' ).text( text );
		} );
	},

	response: function() {
		$( '#challenge-status' ).hide();
		$.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'index' ),
			data: {
				title: 'Special:ChallengeAction',
				action: 1,
				'id': $( '#challenge_id' ).val(),
				status: $( '#challenge_action' ).val()
			}
		} ).done( function() {
			var newStatus;
			switch ( parseInt( $( '#challenge_action' ).val() ) ) {
				case 1:
					newStatus = mw.msg( 'challenge-js-accepted' );
					break;
				case -1:
					newStatus = mw.msg( 'challenge-js-rejected' );
					break;
				case 2:
					newStatus = mw.msg( 'challenge-js-countered' );
					break;
			}

			$( '#challenge-status' ).text( newStatus ).show( 500 );
		} );
	},

	approval: function() {
		$( '#challenge-status' ).hide();
		$.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'index' ),
			data: {
				title: 'Special:ChallengeAction',
				action: 2,
				'id': $( '#challenge_id' ).val(),
				actorid: $( '#challenge_winner_actorid' ).val()
			}
		} ).done( function() {
			$( '#challenge-status' ).text( mw.msg( 'challenge-js-winner-recorded' ) ).show( 500 );
		} );
	},

	rate: function() {
		$( '#challenge-status' ).hide();
		$.ajax( {
			type: 'POST',
			url: mw.util.wikiScript( 'index' ),
			data: {
				title: 'Special:ChallengeAction',
				action: 3,
				'id': $( '#challenge_id' ).val(),
				challenge_rate: $( '#challenge_rate' ).val(),
				rate_comment: $( '#rate_comment' ).val(),
				loser_actorid: $( '#loser_actorid' ).val()
			}
		} ).done( function() {
			$( '#challenge-status' ).text( mw.msg( 'challenge-js-rating-submitted' ) ).show( 500 );
		} );
	}
};

$( function() {
	// Special:ChallengeHistory (SpecialChallengeHistory.php)
	$( 'select[name="status-filter"]' ).on( 'change', function() {
		Challenge.changeFilter( $( this ).data( 'username' ), $( this ).val() );
	} );

	// Special:ChallengeUser (SpecialChallengeUser.php)
	$( '#challenge-user-selector' ).on( 'change', function() {
		Challenge.chooseFriend( this.value );
	} );

	// Special:ChallengeUser (templates/challengeuser.tmpl.php)
	$( 'input.challenge-send-button' ).on( 'click', function() {
		Challenge.send();
	} );

	// Special:ChallengeView (templates/challengeview.tmpl.php)
	$( 'a.challenge-admin-cancel-link' ).on( 'click', function( e ) {
		e.preventDefault();
		Challenge.cancel( $( this ).data( 'challenge-id' ) );
	} );

	// Special:ChallengeView (SpecialChallengeView.php)
	$( 'input.challenge-approval-button' ).on( 'click', function() {
		Challenge.approval();
	} );

	$( 'input.challenge-rate-button' ).on( 'click', function() {
		Challenge.rate();
	} );

	$( 'input.challenge-response-button' ).on( 'click', function() {
		Challenge.response();
	} );
} );