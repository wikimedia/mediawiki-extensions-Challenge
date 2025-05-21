<?php
/**
 * PHP port of ValidateDate.js for use by SpecialChallengeUser.php
 *
 * @file
 * @date 3 August 2020
 */
class ChallengeDateValidator {
	// Declaring valid date character, minimum year and maximum year
	const dateCharacter = '/';
	const minYear = 1900;
	const maxYear = 2100;

	/**
	 * @var array Error to be displayed to the user, if any; the JS version alert()s
	 *   these kind of errors to the user but we can't obviously do that here. 1st item
	 *   is always the message name, and subsequent items are message parameters.
	 */
	public $error;

	/**
	 * Is the current character a number?
	 *
	 * @param string $sx
	 * @return bool
	 */
	public function isInteger( $sx ) {
		for ( $i = 0; $i < strlen( $sx ); $i++ ) {
			// Check that current character is number.
			$c = $sx[$i];
			if ( ( ( $c < '0' ) || ( $c > '9' ) ) ) {
				return false;
			}
		}

		// All characters are numbers.
		return true;
	}

	/**
	 * @param string $sx
	 * @param string $bag
	 * @return string
	 */
	public function stripCharsInBag( $sx, $bag ) {
		$returnString = '';
		// Search through string's characters one by one.
		// If character is not in bag, append to returnString.
		for ( $i = 0; $i < strlen( $sx ); $i++ ) {
			$c = $sx[$i];
			if ( strpos( $bag, $c ) == -1 ) {
				$returnString += $c;
			}
		}
		return $returnString;
	}

	/**
	 * How many days does February have in the given $year?
	 *
	 * @param int $year
	 * @return int Amount of days in February in $year, either 29 or 28
	 */
	public function daysInFebruary( $year ) {
		// February has 29 days in any year evenly divisible by four,
		// EXCEPT for centurial years which are not also divisible by 400.
		return ( ( ( $year % 4 == 0 ) && ( ( !( $year % 100 == 0 ) ) || ( $year % 400 == 0 ) ) ) ? 29 : 28 );
	}

	/**
	 * Produces an array of "month -> amount of days it has" mappings for the given $nx months.
	 *
	 * @param int $nx
	 * @return array
	 */
	public function DaysArray( $nx ) {
		$retVal = [];
		for ( $i = 1; $i <= $nx; $i++ ) {
			$retVal[$i] = 31;
			if ( $i == 4 || $i == 6 || $i == 9 || $i == 11 ) {
				$retVal[$i] = 30;
			}
			if ( $i == 2 ) {
				$retVal[$i] = 29;
			}
		}
		return $retVal;
	}

	/**
	 * Is the supplied string a valid date?
	 *
	 * @param string $dtStr Date in the MM/DD/YYYY format, e.g. 08/18/2020 for 18 August 2020
	 * @return bool True if it's a valid date, otherwise false
	 */
	public function isDate( $dtStr ) {
		if ( !$dtStr ) {
			// ashley: added this to prevent strpos() (the $pos2 definition below) from whining
			$this->error = [ 'challenge-js-error-invalid-date' ];
			return false;
		}

		$daysInMonth = $this->DaysArray( 12 );
		$pos1 = strpos( $dtStr, self::dateCharacter );
		$pos2 = strpos( $dtStr, self::dateCharacter, $pos1 + 1 );
		$strMonth = substr( $dtStr, 0, $pos1 );
		$strDay = substr( $dtStr, $pos1 + 1, $pos2 );
		$strYear = substr( $dtStr, $pos2 + 1 );

		$strYr = $strYear;

		if ( $strDay[0] == '0' && strlen( $strDay ) > 1 ) {
			$strDay = substr( $strDay, 1 );
		}
		if ( isset( $strMonth[0] ) && $strMonth[0] == '0' && strlen( $strMonth ) > 1 ) {
			$strMonth = substr( $strMonth, 1 );
		}

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( $strYr[0] == '0' && strlen( $strYr ) > 1 ) {
				$strYr = substr( $strYr, 1 );
			}
		}

		$month = (int)$strMonth;
		$day = (int)$strDay;
		$year = (int)$strYr;

		if ( $pos1 == -1 || $pos2 == -1 ) {
			$this->error = [ 'challenge-js-error-date-format' ];
			return false;
		}

		if ( strlen( $strMonth ) < 1 || $month < 1 || $month > 12 ) {
			$this->error = [ 'challenge-js-error-invalid-month' ];
			return false;
		}

		if (
			strlen( $strDay ) < 1 || $day < 1 || $day > 31 ||
			( $month == 2 && $day > $this->daysInFebruary( $year ) ) ||
			$day > $daysInMonth[$month] // $this->DaysArray( $month )
		) {
			$this->error = [ 'challenge-js-error-invalid-day' ];
			return false;
		}

		if ( strlen( $strYear ) != 4 || $year == 0 || $year < self::minYear || $year > self::maxYear ) {
			$this->error = [ 'challenge-js-error-invalid-year', self::minYear, self::maxYear ];
			return false;
		}

		if (
			// ashley 18 August 2020: specifying the offset like this is causing the comparison to always
			// fail, even for valid dates
			strpos( $dtStr, self::dateCharacter/*, $pos2 + 1*/ ) === false ||
			$this->isInteger( $this->stripCharsInBag( $dtStr, self::dateCharacter ) ) === false
		) {
			$this->error = [ 'challenge-js-error-invalid-date' ];
			return false;
		}

		return true;
	}

	/**
	 * Is the given date a future date?
	 *
	 * @param string $dtStr User-supplied date string, like 02/21/2022 for 21 February 2022
	 * @return bool True if it's a future date, else false
	 */
	public function isFuture( $dtStr ) {
		// exclamation mark per https://www.php.net/manual/en/datetime.createfromformat.php#118358
		// to ensure the safe comparison of their equalities
		$today = new DateTime( 'now' );
		$tstDate = DateTime::createFromFormat( '!m/d/Y', $dtStr );

		if ( $tstDate === false ) {
			// Invalid date format or something...
			// @todo FIXME: return a better error message!
			$this->error = [ 'challenge-js-error-future-date' ];
			return true;
		}

		// Compare the input date with the current date
		if ( $tstDate > $today ) {
			$this->error = [ 'challenge-js-error-future-date' ];
			return true;
		}

		return false;
	}

	/**
	 * @param string $dtBeg Begin date
	 * @param string $dtEnd End date
	 * @return bool True if the dates are backwards, else false
	 */
	public function isBackwards( $dtBeg, $dtEnd ) {
		// exclamation mark per https://www.php.net/manual/en/datetime.createfromformat.php#118358
		// to ensure the safe comparison of their equalities
		$startDate = DateTime::createFromFormat( '!m-d-Y', $dtBeg );
		$endDate = DateTime::createFromFormat( '!m-d-Y', $dtEnd );

		if ( round( ( $endDate->getTimestamp() - $startDate->getTimestamp() ) / ( 1000 * 60 * 60 * 24 ) ) >= 0 ) {
			return true;
		} else {
			$this->error = [ 'challenge-js-error-is-backwards' ];
			return false;
		}
	}
}
