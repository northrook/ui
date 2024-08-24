function notification() {

    const timeInMs = 250;

    /** @var {NodeListOf<ToastElement>} notifications */
    const notifications = document.querySelectorAll( 'toast' );


    if ( !notifications.length ) return;

    /** @var {ToastContainer} container */
    const container = $get( 'section#notifications', {class: 'fixed top right', role: 'list'} );

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

                    const timeoutBar = $make( '<div class="progress-bar"></div>' );
                    toast.style.setProperty( '--timeout', timeout + 'ms' );
                    toast.appendChild( timeoutBar.element );

                    toast.addEventListener( 'click', () => {
                        timeoutBar.fadeOut();
                        toast.setAttribute( 'timeout', 'stopped' );
                        toast.removeEventListener( 'animationend', remove );
                    }, {once: true} );
                    toast.addEventListener( 'animationend', remove, {once: true} );
                }
            }, timeInMs + ( i * ( timeInMs / 2 ) ) );

            toast.querySelector( 'button.close' )?.addEventListener( 'click', remove );

            container.appendChild( toast );

        }
    } );
}

App.on.ContentLoaded( notification );