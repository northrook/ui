function notification() {

	const timeInMs = 250;

	const notifications = $all( 'toast' );

	if ( !notifications.length ) return;

	const container = $get( 'section#notifications', {class: 'fixed top right', role: 'list'} ).prependTo();

	// console.log( container );

	notifications.forEach( ( toast, i ) => {

		if ( !toast.style.getPropertyValue( '--timeout' ) ) {

			toast.classList.add( 'hidden' );

			const remove = () => {
				const parent = toast.parentElement;
				const transition = elementAnimations( toast );

				toast.classList.add( 'hidden' );

				toast.addEventListener( 'transitionend', ( transitionend ) => {
					if ( transitionend.propertyName !== transition.longest.property ) return;
					toast.remove();
					if ( parent && parent.children.length === 0 ) parent.remove();
				} );
			}

			const timeout = parseInt( toast.getAttribute( 'timeout' ) ?? '0' );

			setTimeout( () => {
				toast.classList.remove( 'hidden' );

				if ( timeout ) {

					const timeoutBar = $new( '<div class="progress-bar"></div>' );
					toast.style.setProperty( '--timeout', timeout + 'ms' );
					timeoutBar.appendTo( toast )

					toast.addEventListener( 'click', () => {
						timeoutBar.fadeOut();
						toast.setAttribute( 'timeout', 'stopped' );
						toast.removeEventListener( 'animationend', remove );
					}, {once: true} );
					toast.addEventListener( 'animationend', remove, {once: true} );
				}
			}, timeInMs + ( i * ( timeInMs / 2 ) ) );

			$( 'button.close', toast ).on( 'click', remove );
			// toast.querySelector( 'button.close' )?.addEventListener( 'click', remove );

			container.appendChild( toast );

		}
	} );
}

App.on.ContentLoaded( notification );