(function() {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function buildEmbedUrl(videoId) {
        return 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0&modestbranding=1';
    }

    ready(function() {
        const gallery = document.querySelector('[data-ysg-gallery]');
        const modal = document.querySelector('[data-ysg-modal]');
        if (!gallery || !modal) {
            return;
        }

        const iframe = modal.querySelector('[data-ysg-iframe]');
        const titleEl = modal.querySelector('[data-ysg-title]');
        const descriptionEl = modal.querySelector('[data-ysg-description]');

        function openModal(videoId, title, description) {
            if (!iframe) {
                return;
            }
            iframe.src = buildEmbedUrl(videoId);
            if (titleEl) {
                titleEl.textContent = title || '';
            }
            if (descriptionEl) {
                descriptionEl.textContent = description || '';
                descriptionEl.style.display = description ? 'block' : 'none';
            }
            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ysg-modal-open');
        }

        function closeModal() {
            if (iframe) {
                iframe.src = '';
            }
            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ysg-modal-open');
        }

        gallery.addEventListener('click', function(event) {
            const target = event.target;
            const button = target.closest('.ysg-card');
            if (!button) {
                return;
            }
            event.preventDefault();
            const item = button.closest('.ysg-item');
            if (!item) {
                return;
            }
            const videoId = item.getAttribute('data-video-id');
            const title = item.getAttribute('data-video-title');
            const description = item.getAttribute('data-video-description');
            if (!videoId) {
                return;
            }
            openModal(videoId, title, description);
        });

        modal.addEventListener('click', function(event) {
            const target = event.target;
            if (target.matches('[data-ysg-close]')) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('is-active')) {
                closeModal();
            }
        });
    });
})();
