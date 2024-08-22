function notification()
{

    const timeInMs = 250;

    /** @var {NodeListOf<ToastElement>} notifications */
    const notifications = document.querySelectorAll('toast');

    if (!notifications.length) return;

    /** @var {ToastContainer} container */
    const container = elementGet('section#notifications', {role: 'list'});

    notifications.forEach((toast, i) => {

        if (!toast.style.getPropertyValue('--timeout')) {

            toast.classList.add('hidden');

            const remove = () => {
                const parent = toast.parentElement;
                const transition = elementAnimations(toast);

                toast.classList.add('hidden');

                toast.addEventListener('transitionend', (transitionend) => {
                    if (transitionend.propertyName !== transition.longest.property) return;
                    toast.remove();
                    if (parent && parent.children.length === 0) parent.remove();
                });
            }

            const timeout = parseInt(toast.getAttribute('timeout') ?? '0');

            setTimeout(() => {
                toast.classList.remove('hidden');

                if (timeout) {
                    toast.style.setProperty('--timeout', timeout + 'ms');
                    toast.addEventListener('click', () => {
                        toast.classList.add('animation-paused');
                        document.addEventListener('mousedown', (click) => {
                            if (toast.contains( /** @type {?Node} */ (click.relatedTarget))) return;
                            toast.classList.remove('animation-paused');
                        }, {once: true});
                        document.addEventListener('keyup', (key) => {
                            if (key.key === 'Escape') toast.classList.remove('animation-paused');
                        })
                    })
                    toast.addEventListener('animationend', remove, {once: true});
                }
            }, timeInMs + (i * (timeInMs / 2)));

            toast.querySelector('button.close')?.addEventListener('click', remove);

            container.appendChild(toast);

        }
    });
}

App.on.ContentLoaded(notification);