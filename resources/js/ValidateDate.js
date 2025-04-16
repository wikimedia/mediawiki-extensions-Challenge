var ChallengeDateValidator = {
	// Declaring valid date character, minimum year and maximum year
	dtCh: '/',
	minYear: 1900,
	maxYear: 2100,

	isInteger: function( sx ) {
		for ( var i = 0; i < sx.length; i++ ) {
			// Check that current character is number.
			var c = sx.charAt( i );
			if ( ( ( c < '0' ) || ( c > '9' ) ) ) {
				return false;
			}
		}

		// All characters are numbers.
		return true;
	},

	stripCharsInBag: function( sx, bag ) {
		var returnString = '';
		// Search through string's characters one by one.
		// If character is not in bag, append to returnString.
		for ( var i = 0; i < sx.length; i++ ) {
			var c = sx.charAt( i );
			if ( bag.indexOf( c ) == -1 ) {
				returnString += c;
			}
		}
		return returnString;
	},

	daysInFebruary: function( year ) {
		// February has 29 days in any year evenly divisible by four,
		// EXCEPT for centurial years which are not also divisible by 400.
		return ( ( ( year % 4 == 0 ) && ( ( !( year % 100 == 0 ) ) || ( year % 400 == 0 ) ) ) ? 29 : 28 );
	},

	DaysArray: function( nx ) {
		for ( var i = 1; i <= nx; i++ ) {
			this[i] = 31;
			if ( i == 4 || i == 6 || i == 9 || i == 11 ) {
				this[i] = 30;
			}
			if ( i == 2 ) {
				this[i] = 29;
			}
		}
		return this;
	},

	isDate: function( dtStr ) {
		var daysInMonth = ChallengeDateValidator.DaysArray( 12 );
		var pos1 = dtStr.indexOf( ChallengeDateValidator.dtCh );
		var pos2 = dtStr.indexOf( ChallengeDateValidator.dtCh, pos1 + 1 );
		var strMonth = dtStr.substring( 0, pos1 );
		var strDay = dtStr.substring( pos1 + 1, pos2 );
		var strYear = dtStr.substring( pos2 + 1 );

		strYr = strYear;

		if ( strDay.charAt( 0 ) == '0' && strDay.length > 1 ) {
			strDay = strDay.substring( 1 );
		}
		if ( strMonth.charAt( 0 ) == '0' && strMonth.length > 1 ) {
			strMonth = strMonth.substring( 1 );
		}

		for ( var i = 1; i <= 3; i++ ) {
			if ( strYr.charAt( 0 ) == '0' && strYr.length > 1 ) {
				strYr = strYr.substring( 1 );
			}
		}

		month = parseInt( strMonth );
		day = parseInt( strDay );
		year = parseInt( strYr );

		if ( pos1 == -1 || pos2 == -1 ) {
			alert( mw.msg( 'challenge-js-error-date-format' ) );
			return false;
		}
		if ( strMonth.length < 1 || month < 1 || month > 12 ) {
			alert( mw.msg( 'challenge-js-error-invalid-month' ) );
			return false;
		}
		if (
			strDay.length < 1 || day < 1 || day > 31 ||
			( month == 2 && day > ChallengeDateValidator.daysInFebruary( year ) ) ||
			day > ChallengeDateValidator.DaysArray[month]
		)
		{
			alert( mw.msg( 'challenge-js-error-invalid-day' ) );
			return false;
		}
		if ( strYear.length != 4 || year == 0 || year < ChallengeDateValidator.minYear || year > ChallengeDateValidator.maxYear ) {
			alert( mw.msg( 'challenge-js-error-invalid-year', ChallengeDateValidator.minYear, ChallengeDateValidator.maxYear ) );
			return false;
		}
		if (
			dtStr.indexOf( ChallengeDateValidator.dtCh, pos2 + 1 ) != -1 ||
			ChallengeDateValidator.isInteger( ChallengeDateValidator.stripCharsInBag( dtStr, ChallengeDateValidator.dtCh ) ) === false
		)
		{
			alert( mw.msg( 'challenge-js-error-invalid-date' ) );
			return false;
		}
		return true;
	},

	isFuture: function( dtStr ) {
		var today = new Date();
		var tstDate = new Date( dtStr );

		if ( Math.round( ( tstDate - today ) / ( 1000 * 60 * 60 * 24 ) ) < 0 ) {
			return true;
		} else {
			alert( mw.msg( 'challenge-js-error-future-date' ) );
			return false;
		}
	},

	isBackwards: function( dtBeg, dtEnd ) {
		var startDate = new Date( dtBeg );
		var endDate = new Date( dtEnd );

		if ( Math.round( ( endDate - startDate ) / ( 60 * 60 * 60 * 24 ) ) >= 0 ) {
			return true;
		} else {
			alert( mw.msg( 'challenge-js-error-is-backwards' ) );
			return false;
		}
	}
};