( function () {
	document.querySelectorAll( '[data-burger]' ).forEach( function ( burger ) {
		var header = burger.closest( '.site-header' );
		var menu = header && header.querySelector( '[data-mobile-nav]' );
		if ( ! menu ) {
			return;
		}
		burger.addEventListener( 'click', function () {
			var open = burger.getAttribute( 'aria-expanded' ) === 'true';
			if ( open ) {
				menu.hidden = true;
				burger.setAttribute( 'aria-expanded', 'false' );
				burger.setAttribute( 'aria-label', 'Åbn menu' );
				burger.textContent = '☰';
			} else {
				menu.hidden = false;
				burger.setAttribute( 'aria-expanded', 'true' );
				burger.setAttribute( 'aria-label', 'Luk menu' );
				burger.textContent = '✕';
			}
		} );
	} );
} )();
