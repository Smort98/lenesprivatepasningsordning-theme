document.querySelectorAll( '.tjekliste__print' ).forEach( function ( btn ) {
	btn.addEventListener( 'click', function () {
		window.print();
	} );
} );
