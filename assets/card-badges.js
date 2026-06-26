(() => {
    const settings = window.zwLiveblogCardBadges || {};
    const articles = Array.from(
        document.querySelectorAll(
            'article[data-post-id], article[id^="post-"]',
        ),
    );

    if (articles.length === 0) {
        return;
    }

    const liveIds = new Set(
        Array.isArray(settings.ids)
            ? settings.ids.map((id) => Number.parseInt(id, 10)).filter(Boolean)
            : [],
    );

    if (liveIds.size === 0) {
        return;
    }

    // Selectors/classes below mirror the current ZuidWest card markup.
    const postIdFor = (article) => {
        const postId = Number.parseInt(
            article.getAttribute('data-post-id') ||
                (article.id || '').replace(/^post-/, ''),
            10,
        );

        return Number.isFinite(postId) && postId > 0 ? postId : 0;
    };

    const findRegionRow = (article) => {
        const region = Array.from(
            article.querySelectorAll('a.bg-groen, span.bg-groen'),
        ).find((element) => {
            const label = (element.textContent || '').trim();
            return (
                label !== '' &&
                label.length <= 40 &&
                element.classList.contains('rounded-md')
            );
        });
        const row = region ? region.parentElement : null;

        if (!(row instanceof HTMLElement)) {
            return null;
        }

        return row;
    };

    articles.forEach((article) => {
        if (
            !liveIds.has(postIdFor(article)) ||
            article.querySelector('.zw-liveblog-card-badge')
        ) {
            return;
        }

        const row = findRegionRow(article);
        if (!row) {
            return;
        }

        row.style.alignItems = 'center';
        row.style.flexWrap = 'wrap';

        const badge = document.createElement('span');
        badge.className =
            'zw-liveblog-card-badge rounded-md px-2 py-0.5 text-xs tracking-wide uppercase font-black font-round text-white';
        badge.textContent = settings.label || 'LIVE';
        badge.setAttribute('aria-label', 'Liveblog');

        row.appendChild(badge);
    });
})();
