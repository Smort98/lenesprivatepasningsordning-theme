( function () {
	function lukLightbox( overlay ) {
		overlay.remove();
		document.body.style.overflow = '';
	}

	function aabnLightbox( img, alleImg, index ) {
		const overlay = document.createElement( 'div' );
		overlay.className = 'lene-lightbox';
		overlay.innerHTML =
			'<button class="lene-lightbox__luk" aria-label="Luk">✕</button>' +
			'<button class="lene-lightbox__prev" aria-label="Forrige">‹</button>' +
			'<img class="lene-lightbox__img" alt="">' +
			'<button class="lene-lightbox__next" aria-label="Næste">›</button>';
		document.body.appendChild( overlay );
		document.body.style.overflow = 'hidden';

		let i = index;
		const imgEl = overlay.querySelector( '.lene-lightbox__img' );

		function vis( n ) {
			i = ( n + alleImg.length ) % alleImg.length;
			const kilde = alleImg[ i ];
			imgEl.src = kilde.getAttribute( 'src' );
			imgEl.alt = kilde.getAttribute( 'alt' ) || '';
		}
		vis( index );

		overlay.querySelector( '.lene-lightbox__luk' ).addEventListener( 'click', () => lukLightbox( overlay ) );
		overlay.querySelector( '.lene-lightbox__prev' ).addEventListener( 'click', () => vis( i - 1 ) );
		overlay.querySelector( '.lene-lightbox__next' ).addEventListener( 'click', () => vis( i + 1 ) );
		overlay.addEventListener( 'click', ( e ) => {
			if ( e.target === overlay ) {
				lukLightbox( overlay );
			}
		} );
		document.addEventListener( 'keydown', function handler( e ) {
			if ( ! document.body.contains( overlay ) ) {
				document.removeEventListener( 'keydown', handler );
				return;
			}
			if ( 'Escape' === e.key ) {
				lukLightbox( overlay );
			} else if ( 'ArrowLeft' === e.key ) {
				vis( i - 1 );
			} else if ( 'ArrowRight' === e.key ) {
				vis( i + 1 );
			}
		} );
	}

	document.querySelectorAll( '.galleri[data-lightbox="true"]' ).forEach( function ( galleri ) {
		const billeder = Array.from( galleri.querySelectorAll( 'img' ) );
		billeder.forEach( function ( img, index ) {
			img.style.cursor = 'zoom-in';
			img.addEventListener( 'click', function () {
				aabnLightbox( img, billeder, index );
			} );
		} );
	} );
} )();
