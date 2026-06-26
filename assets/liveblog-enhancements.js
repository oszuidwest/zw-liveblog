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
    const commentThemeStyleId = 'zw-liveblog-comment-dark-theme';
    const commentThemeCss = `
html,
body,
.frame-content {
    background: transparent !important;
    color: #d1d5db !important;
}

.frame-content [class*="textarea"],
.frame-content textarea {
    background: #111827 !important;
    border-color: #374151 !important;
    color: #f3f4f6 !important;
}

.frame-content [class*="textarea"] {
    border-radius: 4px !important;
}

.frame-content textarea::placeholder {
    color: #9ca3af !important;
    opacity: 1 !important;
}

.frame-content button,
.frame-content svg,
.frame-content svg * {
    color: #d1d5db !important;
    fill: #d1d5db !important;
}

.lb24-livechat-comment,
.lb24-livechat-comment-box,
.lb24-livechat-comment-header,
.lb24-livechat-comment-content,
.lb24-livechat-comment-footer {
    color: #d1d5db !important;
}

.lb24-livechat-comment-username {
    color: #f3f4f6 !important;
}

.lb24-livechat-comment-date,
.lb24-livechat-comment-date * {
    color: #93c5fd !important;
}

.lb24-livechat-comment-content-text {
    color: #d1d5db !important;
}

.lb24-livechat-comment-footer,
.lb24-livechat-comment-footer * {
    color: #9ca3af !important;
}
`;

    const isDarkTheme = () =>
        document.documentElement.classList.contains('dark');

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

    const getFrameDocument = (frame) => {
        try {
            return frame.contentDocument;
        } catch {
            return null;
        }
    };

    const updateCommentFrameTheme = (frame) => {
        const frameDocument = getFrameDocument(frame);

        if (!frameDocument?.head) {
            return;
        }

        const style = frameDocument.getElementById(commentThemeStyleId);

        if (!isDarkTheme()) {
            style?.remove();
            return;
        }

        if (!frameDocument.querySelector('.frame-content')) {
            return;
        }

        if (style) {
            style.textContent = commentThemeCss;
            return;
        }

        const newStyle = frameDocument.createElement('style');
        newStyle.id = commentThemeStyleId;
        newStyle.textContent = commentThemeCss;
        frameDocument.head.appendChild(newStyle);
    };

    const applyCommentFrameTheme = (root) => {
        root.querySelectorAll('iframe').forEach((frame) => {
            if (frame.dataset.zwLiveblogCommentThemeWatched !== '1') {
                frame.dataset.zwLiveblogCommentThemeWatched = '1';
                frame.addEventListener('load', () =>
                    window.requestAnimationFrame(() =>
                        updateCommentFrameTheme(frame),
                    ),
                );
            }

            updateCommentFrameTheme(frame);
        });
    };

    const enhanceRoot = (root) => {
        if (!root) {
            return false;
        }

        if (root.dataset.zwLiveblogInputtingEnhanced === '1') {
            if (root.dataset.zwLiveblogCommentThemeEnhanced !== '1') {
                root.dataset.zwLiveblogCommentThemeEnhanced = '1';
                window.setInterval(() => applyCommentFrameTheme(root), 1000);
            }

            applyCommentFrameTheme(root);
            return true;
        }

        root.dataset.zwLiveblogInputtingEnhanced = '1';
        root.dataset.zwLiveblogCommentThemeEnhanced = '1';

        let animationFrame = 0;
        const scheduleUpdate = () => {
            if (animationFrame) {
                return;
            }

            animationFrame = window.requestAnimationFrame(() => {
                animationFrame = 0;
                removeWhiteLabels(root);
                applyInputtingState(root);
                applyCommentFrameTheme(root);
            });
        };

        const observer = new MutationObserver(scheduleUpdate);
        observer.observe(root, {
            attributes: true,
            attributeFilter: ['aria-hidden', 'hidden', 'style'],
            childList: true,
            subtree: true,
        });

        window.setInterval(() => applyCommentFrameTheme(root), 1000);
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
