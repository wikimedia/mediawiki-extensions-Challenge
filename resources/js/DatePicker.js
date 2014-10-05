$( document ).ready( function() {
	mw.loader.using( 'jquery.ui.datepicker', function() {
		$( '#date' ).datepicker( {
			changeYear: true,
			yearRange: '1930:c',
			dateFormat: 'mm/dd/yy'
		} );
	} );
} );