(function () {
	'use strict';

	var CFG = window.LENE_APP || {};
	var APP = document.getElementById( 'app' );

	var MAANEDER = [ 'jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec' ];
	var UGEDAGE_KORT = [ 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør', 'Søn' ];

	function danskDato( isoDato ) {
		if ( ! isoDato ) return '';
		var dele = isoDato.split( '-' );
		if ( dele.length < 3 ) return isoDato;
		return parseInt( dele[ 2 ], 10 ) + '. ' + MAANEDER[ parseInt( dele[ 1 ], 10 ) - 1 ] + ' ' + dele[ 0 ];
	}

	function esc( str ) {
		var d = document.createElement( 'div' );
		d.textContent = str === null || str === undefined ? '' : String( str );
		return d.innerHTML;
	}

	/**
	 * Almindelig tekst-input i stedet for <input type="time"> — den
	 * indbyggede tidsvælger følger enhedens sprog/region og kan vise
	 * AM/PM i stedet for 24-timers ur, alt efter telefonens indstillinger.
	 * Med et rent tekstfelt vises altid nøjagtigt det, der er tastet.
	 * Denne hjælper indsætter bare kolon automatisk efter to cifre.
	 */
	function formatterTidInput( input ) {
		input.addEventListener( 'input', function () {
			var tal = input.value.replace( /[^\d]/g, '' ).slice( 0, 4 );
			input.value = tal.length >= 3 ? tal.slice( 0, 2 ) + ':' + tal.slice( 2 ) : tal;
		} );
	}

	/**
	 * Dansk beløbstekst ("9.100 kr.", "6.478,50 kr.") -> tal. Punktum er
	 * tusind-adskiller, komma er decimal — modsat engelsk notation.
	 */
	function parseBeloeb( str ) {
		if ( ! str ) return 0;
		var negativ = /-/.test( str );
		var kun = String( str ).replace( /[^\d.,]/g, '' );
		var dele = kun.split( ',' );
		var heltal = dele[ 0 ].replace( /\./g, '' );
		var decimal = dele[ 1 ] ? dele[ 1 ].slice( 0, 2 ) : '';
		var tal = parseFloat( heltal + ( decimal ? '.' + decimal : '' ) );
		if ( isNaN( tal ) ) return 0;
		return negativ ? -tal : tal;
	}

	function formatBeloeb( tal ) {
		var negativ = tal < 0;
		var heltal = Math.round( Math.abs( tal ) );
		var streng = heltal.toString().replace( /\B(?=(\d{3})+(?!\d))/g, '.' );
		return ( negativ ? '-' : '' ) + streng + ' kr.';
	}

	/* ------------------------------------------------------------------
	 * API
	 * ---------------------------------------------------------------- */

	function visNonceBanner() {
		var banner = document.getElementById( 'nonce-banner' );
		if ( banner ) banner.hidden = false;
	}

	function api( sti, opts ) {
		opts = opts || {};
		opts.headers = Object.assign( { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce }, opts.headers || {} );
		return fetch( CFG.restBase + sti, opts ).then( function ( res ) {
			if ( 403 === res.status ) {
				visNonceBanner();
				throw new Error( 'Session udløbet' );
			}
			if ( ! res.ok ) {
				return res.json().catch( function () { return {}; } ).then( function ( fejl ) {
					throw new Error( fejl.message || 'Noget gik galt (' + res.status + ')' );
				} );
			}
			return res.status === 204 ? null : res.json();
		} );
	}

	/**
	 * Eksport/import — bruges til at overføre pladser/lukkedage fra fx
	 * et udviklingssite til det live site, uden at skulle genindtaste
	 * dem manuelt et af stederne.
	 */
	function eksporterJson( data, filnavn ) {
		var blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
		var url = URL.createObjectURL( blob );
		var a = document.createElement( 'a' );
		a.href = url;
		a.download = filnavn;
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
		URL.revokeObjectURL( url );
	}

	function importerJsonFil( callback ) {
		var input = document.createElement( 'input' );
		input.type = 'file';
		input.accept = 'application/json';
		input.addEventListener( 'change', function () {
			var fil = input.files[ 0 ];
			if ( ! fil ) return;
			var laeser = new FileReader();
			laeser.onload = function () {
				try {
					callback( JSON.parse( laeser.result ) );
				} catch ( e ) {
					window.alert( 'Kunne ikke læse filen — er det gyldig JSON?' );
				}
			};
			laeser.readAsText( fil );
		} );
		input.click();
	}

	/* ------------------------------------------------------------------
	 * App-skal: header, faner, banner
	 * ---------------------------------------------------------------- */

	var TABS = [
		{ id: 'pladser', navn: 'Pladser', ikon: '<circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/>' },
		{ id: 'aabningstider', navn: 'Åbningstider', ikon: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>' },
		{ id: 'priser', navn: 'Priser', ikon: '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>' },
		{ id: 'lukkedage', navn: 'Lukkedage', ikon: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>' },
		{ id: 'forside', navn: 'Forside', ikon: '<path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>' },
		{ id: 'hvem-er-jeg', navn: 'Hvem er jeg', ikon: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>' },
		{ id: 'fotoalbum', navn: 'Fotoalbum', ikon: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-4 4-3-3-6 6"/>' },
	];

	var state = { fane: hentFaneFraHash(), data: {}, indlaeser: {} };

	function hentFaneFraHash() {
		var h = ( window.location.hash || '' ).replace( '#', '' );
		var ids = TABS.map( function ( t ) { return t.id; } );
		return ids.indexOf( h ) > -1 ? h : 'pladser';
	}

	window.addEventListener( 'hashchange', function () {
		state.fane = hentFaneFraHash();
		renderSkal();
	} );

	function skiftFane( id ) {
		window.location.hash = id;
	}

	function renderSkal() {
		var faneHtml = TABS.map( function ( t ) {
			return '<button type="button" class="app__tab' + ( t.id === state.fane ? ' er-aktiv' : '' ) + '" data-fane="' + t.id + '">' +
				'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + t.ikon + '</svg>' +
				'<span>' + t.navn + '</span></button>';
		} ).join( '' );

		APP.innerHTML =
			'<header class="app__header">' +
				'<div><h1>LenesPrivatePasningsordning</h1><span class="bruger">' + esc( CFG.brugerNavn || '' ) + '</span></div>' +
				'<a class="app__logud" href="' + esc( CFG.logoutUrl || '#' ) + '">' +
				'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>' +
				'Log ud' +
			'</a>' +
			'</header>' +
			'<div id="nonce-banner" class="app__banner" hidden>Din session er udløbet. <button type="button" id="nonce-reload">Genindlæs</button></div>' +
			'<main class="app__main" id="skaerm"></main>' +
			'<nav class="app__tabs">' + faneHtml + '</nav>';

		APP.querySelectorAll( '.app__tab' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () { skiftFane( btn.dataset.fane ); } );
		} );
		document.getElementById( 'nonce-reload' ).addEventListener( 'click', function () { window.location.reload(); } );

		renderSkaerm();
	}

	function renderSkaerm() {
		var el = document.getElementById( 'skaerm' );
		if ( 'pladser' === state.fane ) return Pladser.render( el );
		if ( 'aabningstider' === state.fane ) return Aabningstider.render( el );
		if ( 'priser' === state.fane ) return Priser.render( el );
		if ( 'lukkedage' === state.fane ) return Lukkedage.render( el );
		if ( 'forside' === state.fane ) return Forside.render( el );
		if ( 'hvem-er-jeg' === state.fane ) return HvemErJeg.render( el );
		if ( 'fotoalbum' === state.fane ) return Fotoalbum.render( el );
	}

	/* ------------------------------------------------------------------
	 * Pladser
	 * ---------------------------------------------------------------- */

	var Pladser = {
		liste: null,
		aabenId: null, // 'ny' eller post-id for udvidet redigeringsvisning

		render: function ( el ) {
			if ( null === this.liste ) {
				el.innerHTML = '<div class="loading">Indlæser pladser…</div>';
				var self = this;
				api( '/pladser' ).then( function ( data ) {
					self.liste = data;
					self.render( el );
				} ).catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var self = this;
			var html = '<h2 class="skaerm-titel">Pladser</h2>';
			html += '<button type="button" class="tilfoej-knap" id="ny-plads">+ Tilføj ny plads</button>';
			html += '<div class="raekke-aktioner">' +
				'<button type="button" class="knap knap--sekundaer knap--lille" id="eksporter-pladser">Eksportér</button>' +
				'<button type="button" class="knap knap--sekundaer knap--lille" id="importer-pladser">Importér</button>' +
			'</div>';

			if ( 'ny' === this.aabenId ) {
				html += this.formular( { dato: '', antal: 1, antal_ledige: 0, status_naar_fuld: 'reserveret', note: '' }, 'ny' );
			}

			if ( ! this.liste.length && 'ny' !== this.aabenId ) {
				html += '<p class="tom-tilstand">Ingen pladser oprettet endnu.</p>';
			}

			this.liste.forEach( function ( p ) {
				if ( self.aabenId === p.id ) {
					html += self.formular( p, p.id );
					return;
				}
				var badgeKlasse = 'badge--' + p.status;
				html += '<div class="kort">' +
					'<div class="kort__top">' +
						'<div><div class="kort__titel">' + esc( danskDato( p.dato ) ) + '</div>' +
						( p.note ? '<div class="kort__undertitel">' + esc( p.note ) + '</div>' : '' ) + '</div>' +
						'<span class="badge ' + badgeKlasse + '">' + esc( p.status_label ) + '</span>' +
					'</div>' +
					'<div class="stepper">' +
						'<button type="button" data-stepper="ned" data-id="' + p.id + '">−</button>' +
						'<span class="stepper__tal">' + p.antal_ledige + ' af ' + p.antal + ' ledige</span>' +
						'<button type="button" data-stepper="op" data-id="' + p.id + '">+</button>' +
					'</div>' +
					'<div class="raekke-aktioner">' +
						'<button type="button" class="knap knap--sekundaer knap--lille" data-rediger="' + p.id + '">Redigér</button>' +
						'<button type="button" class="knap knap--fare knap--lille" data-slet="' + p.id + '">Slet</button>' +
					'</div>' +
				'</div>';
			} );

			el.innerHTML = html;
			this.bind( el );
		},

		formular: function ( p, id ) {
			return '<div class="kort" id="form-' + id + '">' +
				'<div class="felt"><label>Startdato</label><input type="date" data-felt="dato" value="' + esc( p.dato ) + '"></div>' +
				'<div class="felt"><label>Antal pladser i alt</label><input type="number" min="0" data-felt="antal" value="' + esc( p.antal ) + '"></div>' +
				'<div class="felt"><label>Heraf ledige</label><input type="number" min="0" data-felt="antal_ledige" value="' + esc( p.antal_ledige ) + '"></div>' +
				'<div class="felt"><label>Når 0 er ledige, vis som</label><select data-felt="status_naar_fuld">' +
					'<option value="reserveret"' + ( 'optaget' !== p.status_naar_fuld ? ' selected' : '' ) + '>Reserveret</option>' +
					'<option value="optaget"' + ( 'optaget' === p.status_naar_fuld ? ' selected' : '' ) + '>Optaget</option>' +
				'</select></div>' +
				'<div class="felt"><label>Note (valgfri)</label><input type="text" data-felt="note" value="' + esc( p.note ) + '" placeholder="Fx: kun formiddage"></div>' +
				'<div class="raekke-aktioner">' +
					'<button type="button" class="knap knap--sekundaer" data-annuller>Annullér</button>' +
					'<button type="button" class="knap knap--primaer" data-gem="' + id + '">Gem</button>' +
				'</div>' +
			'</div>';
		},

		laesForm: function ( container ) {
			var data = {};
			container.querySelectorAll( '[data-felt]' ).forEach( function ( f ) {
				data[ f.dataset.felt ] = f.value;
			} );
			return data;
		},

		bind: function ( el ) {
			var self = this;
			var nyBtn = document.getElementById( 'ny-plads' );
			if ( nyBtn ) nyBtn.addEventListener( 'click', function () { self.aabenId = 'ny'; self.render( el ); } );

			var eksportBtn = document.getElementById( 'eksporter-pladser' );
			if ( eksportBtn ) eksportBtn.addEventListener( 'click', function () {
				eksporterJson( self.liste, 'pladser.json' );
			} );
			var importBtn = document.getElementById( 'importer-pladser' );
			if ( importBtn ) importBtn.addEventListener( 'click', function () {
				importerJsonFil( function ( data ) {
					var liste = Array.isArray( data ) ? data : ( data.pladser || [] );
					api( '/pladser/importer', { method: 'POST', body: JSON.stringify( { pladser: liste } ) } ).then( function ( resultat ) {
						window.alert( resultat.oprettet.length + ' plads(er) importeret. ' + resultat.sprunget_over.length + ' sprunget over (fandtes allerede).' );
						self.liste = null;
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );

			el.querySelectorAll( '[data-rediger]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { self.aabenId = parseInt( b.dataset.rediger, 10 ); self.render( el ); } );
			} );
			el.querySelectorAll( '[data-annuller]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { self.aabenId = null; self.render( el ); } );
			} );
			el.querySelectorAll( '[data-slet]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					if ( ! window.confirm( 'Slet denne plads?' ) ) return;
					api( '/pladser/' + b.dataset.slet, { method: 'DELETE' } ).then( function () {
						self.liste = self.liste.filter( function ( p ) { return p.id !== parseInt( b.dataset.slet, 10 ); } );
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );
			el.querySelectorAll( '[data-stepper]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					var id = parseInt( b.dataset.id, 10 );
					var plads = self.liste.find( function ( p ) { return p.id === id; } );
					if ( ! plads ) return;
					var ny = plads.antal_ledige + ( 'op' === b.dataset.stepper ? 1 : -1 );
					ny = Math.max( 0, Math.min( plads.antal, ny ) );
					if ( ny === plads.antal_ledige ) return;
					api( '/pladser/' + id, { method: 'PUT', body: JSON.stringify( { antal_ledige: ny } ) } ).then( function ( opdateret ) {
						self.liste = self.liste.map( function ( p ) { return p.id === id ? opdateret : p; } );
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );
			el.querySelectorAll( '[data-gem]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					var id = b.dataset.gem;
					var form = document.getElementById( 'form-' + id );
					var data = self.laesForm( form );
					var kald = 'ny' === id ? api( '/pladser', { method: 'POST', body: JSON.stringify( data ) } ) : api( '/pladser/' + id, { method: 'PUT', body: JSON.stringify( data ) } );
					kald.then( function ( resultat ) {
						if ( 'ny' === id ) {
							self.liste.push( resultat );
							self.liste.sort( function ( a, c ) { return ( a.dato_raw || '' ).localeCompare( c.dato_raw || '' ); } );
						} else {
							self.liste = self.liste.map( function ( p ) { return p.id === resultat.id ? resultat : p; } );
						}
						self.aabenId = null;
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Åbningstider
	 * ---------------------------------------------------------------- */

	var Aabningstider = {
		data: null,

		render: function ( el ) {
			if ( null === this.data ) {
				el.innerHTML = '<div class="loading">Indlæser åbningstider…</div>';
				var self = this;
				api( '/aabningstider' ).then( function ( data ) { self.data = data; self.render( el ); } )
					.catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var html = '<h2 class="skaerm-titel">Åbningstider</h2>';
			this.data.dage.forEach( function ( dag, i ) {
				html += '<div class="kort" data-dag="' + i + '">' +
					'<div class="felt"><label>Etiket</label><input type="text" data-dagfelt="etiket" value="' + esc( dag.etiket ) + '" placeholder="Fx: Mandag – torsdag"></div>' +
					'<div class="felt"><label>Ugedage</label><div class="ugedag-vaelger">' +
						UGEDAGE_KORT.map( function ( navn, idx ) {
							var u = idx + 1;
							var valgt = dag.ugedage.indexOf( u ) > -1;
							return '<button type="button" class="' + ( valgt ? 'er-valgt' : '' ) + '" data-ugedag="' + u + '">' + navn + '</button>';
						} ).join( '' ) +
					'</div></div>' +
					'<div class="felt"><label>Åbner (24-timers, TT:MM)</label><input type="text" inputmode="numeric" maxlength="5" placeholder="06:45" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" data-dagfelt="aabner" data-tidsfelt value="' + esc( dag.aabner ) + '"></div>' +
					'<div class="felt"><label>Lukker (24-timers, TT:MM)</label><input type="text" inputmode="numeric" maxlength="5" placeholder="16:00" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" data-dagfelt="lukker" data-tidsfelt value="' + esc( dag.lukker ) + '"></div>' +
					'<div class="raekke-aktioner"><button type="button" class="knap knap--fare knap--lille" data-fjern-dag="' + i + '">Fjern tidsrum</button></div>' +
				'</div>';
			} );
			html += '<button type="button" class="tilfoej-knap" id="tilfoej-dag">+ Tilføj tidsrum</button>';
			html += '<div class="felt"><label>Note til besøgende</label><textarea id="aabn-note">' + esc( this.data.note ) + '</textarea></div>';
			html += '<button type="button" class="knap knap--primaer knap--fuld-bredde" id="gem-aabningstider">Gem åbningstider</button>';

			el.innerHTML = html;
			this.bind( el );
		},

		laesFraDom: function ( el ) {
			var dage = [];
			el.querySelectorAll( '[data-dag]' ).forEach( function ( kort ) {
				var ugedage = [];
				kort.querySelectorAll( '[data-ugedag].er-valgt' ).forEach( function ( b ) { ugedage.push( parseInt( b.dataset.ugedag, 10 ) ); } );
				dage.push( {
					etiket: kort.querySelector( '[data-dagfelt="etiket"]' ).value,
					ugedage: ugedage,
					aabner: kort.querySelector( '[data-dagfelt="aabner"]' ).value,
					lukker: kort.querySelector( '[data-dagfelt="lukker"]' ).value,
				} );
			} );
			return { dage: dage, note: document.getElementById( 'aabn-note' ).value };
		},

		bind: function ( el ) {
			var self = this;
			el.querySelectorAll( '[data-ugedag]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { b.classList.toggle( 'er-valgt' ); } );
			} );
			el.querySelectorAll( '[data-tidsfelt]' ).forEach( formatterTidInput );
			el.querySelectorAll( '[data-fjern-dag]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					self.data = self.laesFraDom( el );
					self.data.dage.splice( parseInt( b.dataset.fjernDag, 10 ), 1 );
					self.render( el );
				} );
			} );
			var tilfoej = document.getElementById( 'tilfoej-dag' );
			if ( tilfoej ) tilfoej.addEventListener( 'click', function () {
				self.data = self.laesFraDom( el );
				self.data.dage.push( { etiket: '', ugedage: [], aabner: '', lukker: '' } );
				self.render( el );
			} );
			document.getElementById( 'gem-aabningstider' ).addEventListener( 'click', function ( ev ) {
				var knap = ev.currentTarget;
				knap.disabled = true;
				knap.textContent = 'Gemmer…';
				var data = self.laesFraDom( el );
				api( '/aabningstider', { method: 'PUT', body: JSON.stringify( data ) } ).then( function ( opdateret ) {
					self.data = opdateret;
					self.render( el );
				} ).catch( function ( e ) {
					window.alert( e.message );
					knap.disabled = false;
					knap.textContent = 'Gem åbningstider';
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Priser
	 * ---------------------------------------------------------------- */

	var Priser = {
		data: null,

		render: function ( el ) {
			if ( null === this.data ) {
				el.innerHTML = '<div class="loading">Indlæser priser…</div>';
				var self = this;
				api( '/priser' ).then( function ( data ) { self.data = data; self.render( el ); } )
					.catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var html = '<h2 class="skaerm-titel">Priser</h2>';
			html += '<div class="kort">';
			html += '<div class="linje-editor">';
			this.data.linjer.forEach( function ( linje, i ) {
				html += Priser.linjeHtml( linje, i );
			} );
			html += '</div>';
			html += '<button type="button" class="tilfoej-knap" id="tilfoej-linje">+ Tilføj linje</button>';
			html += '<div class="felt"><label>Total (din egenbetaling — beregnes automatisk)</label><div class="pris-total-visning" id="pris-total-visning">' + esc( this.data.totalBeloeb ) + '</div></div>';
			html += '<div class="felt"><label>Bemærkninger (én pr. linje)</label><textarea id="pris-smaat" rows="4">' + esc( this.data.medSmaat.join( '\n' ) ) + '</textarea></div>';
			html += '</div>';
			html += '<button type="button" class="knap knap--primaer knap--fuld-bredde" id="gem-priser">Gem priser</button>';

			el.innerHTML = html;
			this.bind( el );
			this.omberegn( el );
		},

		linjeHtml: function ( linje, i ) {
			var modus     = linje._modus || 'kroner';
			var erProcent = linje.erMinus && 'procent' === modus;
			var html = '<div class="linje-editor__raekke" data-linje="' + i + '">' +
				'<input type="text" data-linjefelt="tekst" value="' + esc( linje.tekst ) + '" placeholder="Fx: Fuldtidsplads pr. måned">' +
				'<input type="text" class="beloeb" data-linjefelt="beloeb" value="' + esc( linje.beloeb ) + '"' + ( erProcent ? ' readonly' : '' ) + ' placeholder="9.100 kr.">' +
				'<button type="button" class="fjern" data-fjern-linje="' + i + '" aria-label="Fjern linje">×</button>' +
			'</div>';
			html += '<div data-linje-ekstra="' + i + '">' + Priser.linjeEkstraHtml( linje, i ) + '</div>';
			return html;
		},

		linjeEkstraHtml: function ( linje, i ) {
			var modus     = linje._modus || 'kroner';
			var erProcent = linje.erMinus && 'procent' === modus;
			var html = '<label class="linje-editor__minus-toggle"><input type="checkbox" data-linjefelt="erMinus" data-linje-checkbox="' + i + '"' + ( linje.erMinus ? ' checked' : '' ) + '> Er et fradrag (fx tilskud)</label>';
			if ( linje.erMinus ) {
				html += '<div class="linje-editor__modus">' +
					'<select data-linje-modus="' + i + '">' +
						'<option value="kroner"' + ( 'kroner' === modus ? ' selected' : '' ) + '>Indtast i kroner</option>' +
						'<option value="procent"' + ( 'procent' === modus ? ' selected' : '' ) + '>Indtast i procent af 1. linje</option>' +
					'</select>' +
					( erProcent ? '<input type="number" class="linje-editor__procent" data-linje-procent="' + i + '" min="0" max="100" step="0.1" value="' + esc( linje._procent || '' ) + '" placeholder="75">' : '' ) +
				'</div>';
			}
			return html;
		},

		/**
		 * Genberegner alle procent-baserede fradrag ud fra første linjes
		 * beløb, samt totalen ud fra alle linjers beløb/fradrag-status.
		 * Køres live ved enhver relevant ændring, ikke kun ved gem.
		 */
		omberegn: function ( el ) {
			var raekker      = el.querySelectorAll( '[data-linje]' );
			var foersteBeloeb = raekker.length ? parseBeloeb( raekker[ 0 ].querySelector( '[data-linjefelt="beloeb"]' ).value ) : 0;
			var total = 0;

			raekker.forEach( function ( raekke ) {
				var i           = raekke.dataset.linje;
				var checkbox    = el.querySelector( '[data-linje-checkbox="' + i + '"]' );
				var erMinus     = !! ( checkbox && checkbox.checked );
				var beloebInput = raekke.querySelector( '[data-linjefelt="beloeb"]' );
				var modusSelect = el.querySelector( '[data-linje-modus="' + i + '"]' );
				var modus       = modusSelect ? modusSelect.value : 'kroner';

				if ( erMinus && 'procent' === modus ) {
					var procentInput = el.querySelector( '[data-linje-procent="' + i + '"]' );
					var procent      = procentInput ? parseFloat( ( procentInput.value || '0' ).replace( ',', '.' ) ) || 0 : 0;
					beloebInput.value = formatBeloeb( foersteBeloeb * ( procent / 100 ) );
					beloebInput.readOnly = true;
				} else {
					beloebInput.readOnly = false;
				}

				var beloeb = Math.abs( parseBeloeb( beloebInput.value ) );
				total += erMinus ? -beloeb : beloeb;
			} );

			var visning = document.getElementById( 'pris-total-visning' );
			if ( visning ) visning.textContent = formatBeloeb( total );
		},

		laesFraDom: function ( el ) {
			var linjer = [];
			el.querySelectorAll( '[data-linje]' ).forEach( function ( raekke ) {
				var i          = raekke.dataset.linje;
				var checkbox   = el.querySelector( '[data-linje-checkbox="' + i + '"]' );
				var modusSelect = el.querySelector( '[data-linje-modus="' + i + '"]' );
				var procentInput = el.querySelector( '[data-linje-procent="' + i + '"]' );
				linjer.push( {
					tekst: raekke.querySelector( '[data-linjefelt="tekst"]' ).value,
					beloeb: raekke.querySelector( '[data-linjefelt="beloeb"]' ).value,
					erMinus: !! ( checkbox && checkbox.checked ),
					_modus: modusSelect ? modusSelect.value : 'kroner',
					_procent: procentInput ? procentInput.value : '',
				} );
			} );
			return {
				linjer: linjer,
				totalBeloeb: document.getElementById( 'pris-total-visning' ).textContent,
				medSmaat: document.getElementById( 'pris-smaat' ).value.split( '\n' ).map( function ( s ) { return s.trim(); } ).filter( Boolean ),
			};
		},

		bind: function ( el ) {
			var self = this;

			el.addEventListener( 'input', function ( ev ) {
				if ( ev.target.matches( '[data-linjefelt="beloeb"]:not([readonly])' ) || ev.target.matches( '[data-linje-procent]' ) ) {
					self.omberegn( el );
				}
			} );

			el.addEventListener( 'change', function ( ev ) {
				if ( ev.target.matches( '[data-linje-checkbox]' ) ) {
					var i = ev.target.dataset.linjeCheckbox;
					var linje = { erMinus: ev.target.checked, _modus: 'kroner', _procent: '' };
					var wrap = el.querySelector( '[data-linje-ekstra="' + i + '"]' );
					if ( wrap ) wrap.innerHTML = self.linjeEkstraHtml( linje, i );
					self.omberegn( el );
				} else if ( ev.target.matches( '[data-linje-modus]' ) ) {
					var mi = ev.target.dataset.linjeModus;
					var linje2 = { erMinus: true, _modus: ev.target.value, _procent: '' };
					var wrap2 = el.querySelector( '[data-linje-ekstra="' + mi + '"]' );
					if ( wrap2 ) wrap2.innerHTML = self.linjeEkstraHtml( linje2, mi );
					self.omberegn( el );
				}
			} );

			el.querySelectorAll( '[data-fjern-linje]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					self.data = self.laesFraDom( el );
					self.data.linjer.splice( parseInt( b.dataset.fjernLinje, 10 ), 1 );
					self.render( el );
				} );
			} );
			var tilfoej = document.getElementById( 'tilfoej-linje' );
			if ( tilfoej ) tilfoej.addEventListener( 'click', function () {
				self.data = self.laesFraDom( el );
				self.data.linjer.push( { tekst: '', beloeb: '', erMinus: false } );
				self.render( el );
			} );
			document.getElementById( 'gem-priser' ).addEventListener( 'click', function ( ev ) {
				var knap = ev.currentTarget;
				knap.disabled = true;
				knap.textContent = 'Gemmer…';
				var data = self.laesFraDom( el );
				api( '/priser', { method: 'PUT', body: JSON.stringify( data ) } ).then( function ( opdateret ) {
					self.data = opdateret;
					self.render( el );
				} ).catch( function ( e ) {
					window.alert( e.message );
					knap.disabled = false;
					knap.textContent = 'Gem priser';
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Lukkedage
	 * ---------------------------------------------------------------- */

	var Lukkedage = {
		liste: null,
		aabenId: null,

		render: function ( el ) {
			if ( null === this.liste ) {
				el.innerHTML = '<div class="loading">Indlæser lukkedage…</div>';
				var self = this;
				api( '/lukkedage' ).then( function ( data ) { self.liste = data; self.render( el ); } )
					.catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var self = this;
			var html = '<h2 class="skaerm-titel">Lukkedage</h2>';
			html += '<button type="button" class="tilfoej-knap" id="ny-lukkedag">+ Tilføj lukkeperiode</button>';
			html += '<div class="raekke-aktioner">' +
				'<button type="button" class="knap knap--sekundaer knap--lille" id="eksporter-lukkedage">Eksportér</button>' +
				'<button type="button" class="knap knap--sekundaer knap--lille" id="importer-lukkedage">Importér</button>' +
			'</div>';

			if ( 'ny' === this.aabenId ) {
				html += this.formular( { periode: '', datoer: '', aarsag: '', skjul_efter: '' }, 'ny' );
			}
			if ( ! this.liste.length && 'ny' !== this.aabenId ) {
				html += '<p class="tom-tilstand">Ingen lukkeperioder oprettet endnu.</p>';
			}

			this.liste.forEach( function ( l ) {
				if ( self.aabenId === l.id ) {
					html += self.formular( l, l.id );
					return;
				}
				html += '<div class="kort">' +
					'<div class="kort__titel">' + esc( l.periode || '(uden titel)' ) + '</div>' +
					'<div class="kort__undertitel">' + esc( l.datoer ) + ( l.aarsag ? ' · ' + esc( l.aarsag ) : '' ) + '</div>' +
					( l.skjul_efter ? '<div class="kort__undertitel">Skjules efter ' + esc( danskDato( l.skjul_efter ) ) + '</div>' : '' ) +
					'<div class="raekke-aktioner">' +
						'<button type="button" class="knap knap--sekundaer knap--lille" data-rediger="' + l.id + '">Redigér</button>' +
						'<button type="button" class="knap knap--fare knap--lille" data-slet="' + l.id + '">Slet</button>' +
					'</div>' +
				'</div>';
			} );

			el.innerHTML = html;
			this.bind( el );
		},

		formular: function ( l, id ) {
			return '<div class="kort" id="form-' + id + '">' +
				'<div class="felt"><label>Periode</label><input type="text" data-felt="periode" value="' + esc( l.periode ) + '" placeholder="Fx: Uge 29–30"></div>' +
				'<div class="felt"><label>Datoer</label><input type="text" data-felt="datoer" value="' + esc( l.datoer ) + '" placeholder="Fx: 13.–24. juli"></div>' +
				'<div class="felt"><label>Årsag</label><input type="text" data-felt="aarsag" value="' + esc( l.aarsag ) + '" placeholder="Fx: Sommerferie"></div>' +
				'<div class="felt"><label>Skjul efter denne dato</label><input type="date" data-felt="skjul_efter" value="' + esc( l.skjul_efter ) + '"></div>' +
				'<div class="raekke-aktioner">' +
					'<button type="button" class="knap knap--sekundaer" data-annuller>Annullér</button>' +
					'<button type="button" class="knap knap--primaer" data-gem="' + id + '">Gem</button>' +
				'</div>' +
			'</div>';
		},

		bind: function ( el ) {
			var self = this;
			var nyBtn = document.getElementById( 'ny-lukkedag' );
			if ( nyBtn ) nyBtn.addEventListener( 'click', function () { self.aabenId = 'ny'; self.render( el ); } );

			var eksportBtn = document.getElementById( 'eksporter-lukkedage' );
			if ( eksportBtn ) eksportBtn.addEventListener( 'click', function () {
				eksporterJson( self.liste, 'lukkedage.json' );
			} );
			var importBtn = document.getElementById( 'importer-lukkedage' );
			if ( importBtn ) importBtn.addEventListener( 'click', function () {
				importerJsonFil( function ( data ) {
					var liste = Array.isArray( data ) ? data : ( data.lukkedage || [] );
					api( '/lukkedage/importer', { method: 'POST', body: JSON.stringify( { lukkedage: liste } ) } ).then( function ( resultat ) {
						window.alert( resultat.oprettet.length + ' lukkeperiode(r) importeret. ' + resultat.sprunget_over.length + ' sprunget over (fandtes allerede).' );
						self.liste = null;
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );

			el.querySelectorAll( '[data-rediger]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { self.aabenId = parseInt( b.dataset.rediger, 10 ); self.render( el ); } );
			} );
			el.querySelectorAll( '[data-annuller]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { self.aabenId = null; self.render( el ); } );
			} );
			el.querySelectorAll( '[data-slet]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					if ( ! window.confirm( 'Slet denne lukkeperiode?' ) ) return;
					api( '/lukkedage/' + b.dataset.slet, { method: 'DELETE' } ).then( function () {
						self.liste = self.liste.filter( function ( l ) { return l.id !== parseInt( b.dataset.slet, 10 ); } );
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );
			el.querySelectorAll( '[data-gem]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					var id = b.dataset.gem;
					var form = document.getElementById( 'form-' + id );
					var data = {};
					form.querySelectorAll( '[data-felt]' ).forEach( function ( f ) { data[ f.dataset.felt ] = f.value; } );
					var kald = 'ny' === id ? api( '/lukkedage', { method: 'POST', body: JSON.stringify( data ) } ) : api( '/lukkedage/' + id, { method: 'PUT', body: JSON.stringify( data ) } );
					kald.then( function ( resultat ) {
						if ( 'ny' === id ) {
							self.liste.push( resultat );
						} else {
							self.liste = self.liste.map( function ( l ) { return l.id === resultat.id ? resultat : l; } );
						}
						self.aabenId = null;
						self.render( el );
					} ).catch( function ( e ) { window.alert( e.message ); } );
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Forside (hero-tekst + døgnrytme)
	 * ---------------------------------------------------------------- */

	function punktRaekkeHtml( felter, i ) {
		var html = '<div class="punkt-editor__raekke" data-punkt="' + i + '">';
		felter.forEach( function ( f ) {
			html += '<div class="felt"><label>' + esc( f.label ) + '</label>' +
				( f.flerlinje
					? '<textarea data-punktfelt="' + f.navn + '" rows="2">' + esc( f.vaerdi ) + '</textarea>'
					: '<input type="text" data-punktfelt="' + f.navn + '" value="' + esc( f.vaerdi ) + '" placeholder="' + esc( f.placeholder || '' ) + '">'
				) +
			'</div>';
		} );
		html += '<button type="button" class="knap knap--fare knap--lille" data-fjern-punkt="' + i + '">Fjern</button>';
		html += '</div>';
		return html;
	}

	function laesPunkterFraDom( el, felt_navne ) {
		var punkter = [];
		el.querySelectorAll( '[data-punkt]' ).forEach( function ( raekke ) {
			var p = {};
			felt_navne.forEach( function ( navn ) {
				p[ navn ] = raekke.querySelector( '[data-punktfelt="' + navn + '"]' ).value;
			} );
			punkter.push( p );
		} );
		return punkter;
	}

	/**
	 * saetPunkter: function(nyPunktliste) — skriver den opdaterede liste
	 * tilbage til modulets egen state. genrender: function() — kalder
	 * modulets render(el) igen. Holdt generisk, så både Forside (som
	 * gemmer punkter under .rytme.punkter) og HvemErJeg (under
	 * .data.punkter) kan bruge den samme binder.
	 */
	function bindPunktFjernOgTilfoej( el, saetPunkter, genrender, tilfoejBtnId, tomtPunkt, felt_navne ) {
		el.querySelectorAll( '[data-fjern-punkt]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var punkter = laesPunkterFraDom( el, felt_navne );
				punkter.splice( parseInt( b.dataset.fjernPunkt, 10 ), 1 );
				saetPunkter( punkter );
				genrender();
			} );
		} );
		var tilfoejBtn = document.getElementById( tilfoejBtnId );
		if ( tilfoejBtn ) tilfoejBtn.addEventListener( 'click', function () {
			var punkter = laesPunkterFraDom( el, felt_navne );
			punkter.push( tomtPunkt );
			saetPunkter( punkter );
			genrender();
		} );
	}

	var Forside = {
		hero: null,
		rytme: null,

		render: function ( el ) {
			if ( null === this.hero || null === this.rytme ) {
				el.innerHTML = '<div class="loading">Indlæser forside…</div>';
				var self = this;
				Promise.all( [ api( '/hero' ), api( '/dagsrytme' ) ] ).then( function ( resultater ) {
					self.hero  = resultater[ 0 ];
					self.rytme = resultater[ 1 ];
					self.render( el );
				} ).catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var self = this;
			var html = '<h2 class="skaerm-titel">Forside</h2>';

			html += '<div class="kort">';
			html += '<h3 class="kort-sektion__titel">Hero</h3>';
			html += '<div class="felt"><label>Overrubrik</label><input type="text" id="hero-overrubrik" value="' + esc( this.hero.overrubrik ) + '"></div>';
			html += '<div class="felt"><label>Overskrift</label><input type="text" id="hero-titel" value="' + esc( this.hero.titel ) + '"></div>';
			html += '<div class="felt"><label>Tekst</label><textarea id="hero-tekst" rows="3">' + esc( this.hero.tekst ) + '</textarea></div>';
			html += '<button type="button" class="knap knap--primaer" id="gem-hero">Gem hero</button>';
			html += '</div>';

			html += '<div class="kort">';
			html += '<h3 class="kort-sektion__titel">Døgnrytme</h3>';
			this.rytme.punkter.forEach( function ( p, i ) {
				html += punktRaekkeHtml( [
					{ navn: 'tidspunkt', label: 'Tidspunkt', vaerdi: p.tidspunkt, placeholder: 'Fx: 6.45' },
					{ navn: 'titel', label: 'Titel', vaerdi: p.titel },
					{ navn: 'tekst', label: 'Tekst', vaerdi: p.tekst, flerlinje: true },
				], i );
			} );
			html += '<button type="button" class="tilfoej-knap" id="tilfoej-rytme-punkt">+ Tilføj tidspunkt</button>';
			html += '<button type="button" class="knap knap--primaer knap--fuld-bredde" id="gem-rytme">Gem døgnrytme</button>';
			html += '</div>';

			el.innerHTML = html;
			this.bind( el );
		},

		bind: function ( el ) {
			var self = this;

			document.getElementById( 'gem-hero' ).addEventListener( 'click', function ( ev ) {
				var knap = ev.currentTarget;
				knap.disabled = true;
				var data = {
					overrubrik: document.getElementById( 'hero-overrubrik' ).value,
					titel: document.getElementById( 'hero-titel' ).value,
					tekst: document.getElementById( 'hero-tekst' ).value,
				};
				api( '/hero', { method: 'PUT', body: JSON.stringify( data ) } ).then( function ( opdateret ) {
					self.hero = opdateret;
					knap.disabled = false;
					knap.textContent = 'Gemt ✓';
					setTimeout( function () { knap.textContent = 'Gem hero'; }, 1500 );
				} ).catch( function ( e ) { window.alert( e.message ); knap.disabled = false; } );
			} );

			bindPunktFjernOgTilfoej(
				el,
				function ( punkter ) { self.rytme.punkter = punkter; },
				function () { self.render( el ); },
				'tilfoej-rytme-punkt',
				{ tidspunkt: '', titel: '', tekst: '' },
				[ 'tidspunkt', 'titel', 'tekst' ]
			);

			document.getElementById( 'gem-rytme' ).addEventListener( 'click', function ( ev ) {
				var knap = ev.currentTarget;
				knap.disabled = true;
				knap.textContent = 'Gemmer…';
				var data = { punkter: laesPunkterFraDom( el, [ 'tidspunkt', 'titel', 'tekst' ] ) };
				api( '/dagsrytme', { method: 'PUT', body: JSON.stringify( data ) } ).then( function ( opdateret ) {
					self.rytme = opdateret;
					self.render( el );
				} ).catch( function ( e ) {
					window.alert( e.message );
					knap.disabled = false;
					knap.textContent = 'Gem døgnrytme';
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Hvem er jeg (efteruddannelse/tidslinje)
	 * ---------------------------------------------------------------- */

	var HvemErJeg = {
		data: null,

		render: function ( el ) {
			if ( null === this.data ) {
				el.innerHTML = '<div class="loading">Indlæser…</div>';
				var self = this;
				api( '/tidslinje' ).then( function ( data ) { self.data = data; self.render( el ); } )
					.catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var self = this;
			var html = '<h2 class="skaerm-titel">Hvem er jeg</h2>';
			html += '<div class="kort">';
			html += '<h3 class="kort-sektion__titel">Efteruddannelse</h3>';
			this.data.punkter.forEach( function ( p, i ) {
				html += punktRaekkeHtml( [
					{ navn: 'aar', label: 'Årstal / periode', vaerdi: p.aar, placeholder: 'Fx: 2026' },
					{ navn: 'titel', label: 'Titel', vaerdi: p.titel },
					{ navn: 'tekst', label: 'Tekst', vaerdi: p.tekst, flerlinje: true },
				], i );
			} );
			html += '<button type="button" class="tilfoej-knap" id="tilfoej-kursus">+ Tilføj kursus</button>';
			html += '<button type="button" class="knap knap--primaer knap--fuld-bredde" id="gem-tidslinje">Gem</button>';
			html += '</div>';
			el.innerHTML = html;
			this.bind( el );
		},

		bind: function ( el ) {
			var self = this;
			bindPunktFjernOgTilfoej(
				el,
				function ( punkter ) { self.data.punkter = punkter; },
				function () { self.render( el ); },
				'tilfoej-kursus',
				{ aar: '', titel: '', tekst: '' },
				[ 'aar', 'titel', 'tekst' ]
			);

			document.getElementById( 'gem-tidslinje' ).addEventListener( 'click', function ( ev ) {
				var knap = ev.currentTarget;
				knap.disabled = true;
				knap.textContent = 'Gemmer…';
				var data = { punkter: laesPunkterFraDom( el, [ 'aar', 'titel', 'tekst' ] ) };
				api( '/tidslinje', { method: 'PUT', body: JSON.stringify( data ) } ).then( function ( opdateret ) {
					self.data = opdateret;
					self.render( el );
				} ).catch( function ( e ) {
					window.alert( e.message );
					knap.disabled = false;
					knap.textContent = 'Gem';
				} );
			} );
		},
	};

	/* ------------------------------------------------------------------
	 * Fotoalbum
	 * ---------------------------------------------------------------- */

	var Fotoalbum = {
		billeder: null,
		gemmer: false,

		render: function ( el ) {
			if ( null === this.billeder ) {
				el.innerHTML = '<div class="loading">Indlæser fotoalbum…</div>';
				var self = this;
				api( '/galleri' ).then( function ( data ) { self.billeder = data.billeder; self.render( el ); } )
					.catch( function ( e ) { el.innerHTML = '<div class="loading">' + esc( e.message ) + '</div>'; } );
				return;
			}
			var self = this;
			var html = '<h2 class="skaerm-titel">Fotoalbum</h2>';
			html += '<input type="file" id="foto-input" accept="image/*" capture="environment" hidden>';
			html += '<button type="button" class="tilfoej-knap" id="tilfoej-billede"' + ( this.gemmer ? ' disabled' : '' ) + '>+ Tilføj billede</button>';
			if ( this.gemmer ) {
				html += '<p class="loading">Gemmer…</p>';
			}
			html += '<div class="foto-grid">';
			this.billeder.forEach( function ( b, i ) {
				html += '<div class="foto-kort">' +
					'<img src="' + esc( b.url ) + '" alt="' + esc( b.alt ) + '" loading="lazy">' +
					'<div class="foto-kort__aktioner">' +
						'<button type="button" data-flyt="op" data-index="' + i + '"' + ( 0 === i ? ' disabled' : '' ) + ' aria-label="Flyt tidligere">↑</button>' +
						'<button type="button" data-flyt="ned" data-index="' + i + '"' + ( i === self.billeder.length - 1 ? ' disabled' : '' ) + ' aria-label="Flyt senere">↓</button>' +
						'<button type="button" class="foto-kort__slet" data-slet-index="' + i + '">Slet</button>' +
					'</div>' +
				'</div>';
			} );
			html += '</div>';
			if ( ! this.billeder.length ) {
				html += '<p class="tom-tilstand">Ingen billeder endnu.</p>';
			}

			el.innerHTML = html;
			this.bind( el );
		},

		gemRaekkefoelge: function ( el ) {
			var self = this;
			this.gemmer = true;
			this.render( el );
			var ider = this.billeder.map( function ( b ) { return b.id; } );
			api( '/galleri', { method: 'PUT', body: JSON.stringify( { billede_ider: ider } ) } ).then( function ( opdateret ) {
				self.billeder = opdateret.billeder;
				self.gemmer   = false;
				self.render( el );
			} ).catch( function ( e ) {
				window.alert( e.message );
				self.gemmer = false;
				self.render( el );
			} );
		},

		bind: function ( el ) {
			var self       = this;
			var input      = document.getElementById( 'foto-input' );
			var tilfoejBtn = document.getElementById( 'tilfoej-billede' );

			tilfoejBtn.addEventListener( 'click', function () { input.click(); } );
			input.addEventListener( 'change', function () {
				var fil = input.files[ 0 ];
				if ( ! fil ) return;
				self.gemmer = true;
				self.render( el );

				var form = new FormData();
				form.append( 'fil', fil );
				fetch( CFG.restBase + '/galleri/upload', {
					method: 'POST',
					headers: { 'X-WP-Nonce': CFG.nonce },
					body: form,
				} ).then( function ( res ) {
					if ( ! res.ok ) {
						return res.json().catch( function () { return {}; } ).then( function ( fejl ) {
							throw new Error( fejl.message || 'Upload fejlede (' + res.status + ')' );
						} );
					}
					return res.json();
				} ).then( function ( nyt ) {
					self.billeder.push( { id: nyt.id, url: nyt.url, alt: '' } );
					self.gemRaekkefoelge( el );
				} ).catch( function ( e ) {
					window.alert( e.message );
					self.gemmer = false;
					self.render( el );
				} );
			} );

			el.querySelectorAll( '[data-flyt]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					var i = parseInt( b.dataset.index, 10 );
					var j = 'op' === b.dataset.flyt ? i - 1 : i + 1;
					if ( j < 0 || j >= self.billeder.length ) return;
					var tmp = self.billeder[ i ];
					self.billeder[ i ] = self.billeder[ j ];
					self.billeder[ j ] = tmp;
					self.gemRaekkefoelge( el );
				} );
			} );
			el.querySelectorAll( '[data-slet-index]' ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					if ( ! window.confirm( 'Slet dette billede fra fotoalbummet?' ) ) return;
					self.billeder.splice( parseInt( b.dataset.sletIndex, 10 ), 1 );
					self.gemRaekkefoelge( el );
				} );
			} );
		},
	};

	renderSkal();
} )();
