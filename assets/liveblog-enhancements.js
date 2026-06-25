(() => {
    const settings = window.zwLiveblogEnhancements || {};
    const inputtingLabel = settings.inputtingLabel || 'Aan het typen...';
    const recentlyUpdatedLabel =
        settings.recentlyUpdatedLabel || 'Net bijgewerkt';
    const inputtingSelector = '.lb24-base-editor-inputting';
    const statusSelector = '.lb24-base-topbar-status';
    const updatingTextSelector = '.lb24-component-updating1';
    const statusTextSelector = '.lb24-base-topbar-status-text';
    const whiteLabelSelector = '.lb24-liveblog-white-label';

    const removeWhiteLabels = (root) => {
        root.querySelectorAll(whiteLabelSelector).forEach((label) => {
            label.remove();
        });
    };

    const hasVisibleInputtingElement = (root) => {
        const inputting = root.querySelector(inputtingSelector);

        if (!inputting) {
            return false;
        }

        return (
            !inputting.hidden &&
            inputting.getAttribute('aria-hidden') !== 'true' &&
            inputting.style.display !== 'none'
        );
    };

    const applyInputtingState = (root) => {
        const status = root.querySelector(statusSelector);
        const updatingText = status
            ? status.querySelector(updatingTextSelector)
            : null;
        const statusText =
            updatingText ||
            (status ? status.querySelector(statusTextSelector) : null);
        const wasInputting = root.classList.contains(
            'zw-liveblog-editor-inputting',
        );
        const isInputting = hasVisibleInputtingElement(root);

        root.classList.toggle('zw-liveblog-editor-inputting', isInputting);

        if (!statusText) {
            return;
        }

        if (isInputting) {
            if (
                !statusText.dataset.zwLiveblogOriginalText &&
                statusText.textContent !== inputtingLabel
            ) {
                statusText.dataset.zwLiveblogOriginalText =
                    statusText.textContent || '';
            }

            statusText.textContent = inputtingLabel;
            return;
        }

        if (wasInputting && statusText.dataset.zwLiveblogOriginalText) {
            statusText.textContent = statusText.dataset.zwLiveblogOriginalText;
            delete statusText.dataset.zwLiveblogOriginalText;
        }

        if (
            updatingText &&
            updatingText.textContent.trim() !== recentlyUpdatedLabel
        ) {
            updatingText.textContent = recentlyUpdatedLabel;
        }
    };

    const enhanceRoot = (root) => {
        if (!root || root.dataset.zwLiveblogInputtingEnhanced === '1') {
            return false;
        }

        root.dataset.zwLiveblogInputtingEnhanced = '1';

        let animationFrame = 0;
        const scheduleUpdate = () => {
            if (animationFrame) {
                return;
            }

            animationFrame = window.requestAnimationFrame(() => {
                animationFrame = 0;
                removeWhiteLabels(root);
                applyInputtingState(root);
            });
        };

        const observer = new MutationObserver(scheduleUpdate);
        observer.observe(root, {
            attributes: true,
            attributeFilter: ['aria-hidden', 'hidden', 'style'],
            childList: true,
            subtree: true,
        });

        scheduleUpdate();
        return true;
    };

    if (enhanceRoot(document.getElementById('LB24'))) {
        return;
    }

    const pageObserver = new MutationObserver(() => {
        if (enhanceRoot(document.getElementById('LB24'))) {
            pageObserver.disconnect();
        }
    });

    pageObserver.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
})();
