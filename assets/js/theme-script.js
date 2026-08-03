document.addEventListener('DOMContentLoaded', function () {
    // 1. Carousel Slider Implementation
    const carousels = document.querySelectorAll('.eka-news-carousel');
    carousels.forEach(carousel => {
        const list = carousel.querySelector('.wp-block-post-template') || carousel.querySelector('.eka-news-list');
        if (!list) return;

        const items = Array.from(list.children).filter(child => child.matches('.wp-block-post, .eka-news-card, li'));
        if (items.length <= 1) return;

        let currentIndex = 0;
        const totalItems = items.length;

        // Create Navigation Controls
        const navContainer = document.createElement('div');
        navContainer.className = 'eka-carousel-controls';

        const prevBtn = document.createElement('button');
        prevBtn.className = 'eka-carousel-btn eka-carousel-prev';
        prevBtn.innerHTML = '&#10094;';
        prevBtn.setAttribute('aria-label', 'Previous Slide');

        const nextBtn = document.createElement('button');
        nextBtn.className = 'eka-carousel-btn eka-carousel-next';
        nextBtn.innerHTML = '&#10095;';
        nextBtn.setAttribute('aria-label', 'Next Slide');

        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'eka-carousel-dots';

        items.forEach((item, idx) => {
            const img = item.querySelector('img');
            if (img) {
                const imgSrc = img.currentSrc || img.src;
                if (imgSrc) {
                    item.style.backgroundImage = `url("${imgSrc}")`;
                    item.style.backgroundSize = 'cover';
                    item.style.backgroundPosition = 'center center';
                    item.style.backgroundRepeat = 'no-repeat';
                }
            }

            const dot = document.createElement('span');
            dot.className = `eka-carousel-dot${idx === 0 ? ' active' : ''}`;
            dot.addEventListener('click', () => goToSlide(idx));
            dotsContainer.appendChild(dot);
        });

        navContainer.appendChild(prevBtn);
        navContainer.appendChild(dotsContainer);
        navContainer.appendChild(nextBtn);
        carousel.appendChild(navContainer);

        function updateSlides() {
            items.forEach((item, idx) => {
                const isActive = (idx === currentIndex);
                item.style.display = isActive ? 'block' : 'none';
                if (isActive) {
                    item.classList.remove('is-active-slide');
                    // Force reflow for animation restart
                    void item.offsetWidth;
                    item.classList.add('is-active-slide');
                } else {
                    item.classList.remove('is-active-slide');
                }
            });
            const dots = dotsContainer.querySelectorAll('.eka-carousel-dot');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        }

        function goToSlide(index) {
            currentIndex = (index + totalItems) % totalItems;
            updateSlides();
        }

        prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
        nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));

        // Auto Advance every 5 seconds
        let timer = setInterval(() => goToSlide(currentIndex + 1), 5000);
        carousel.addEventListener('mouseenter', () => clearInterval(timer));
        carousel.addEventListener('mouseleave', () => {
            clearInterval(timer);
            timer = setInterval(() => goToSlide(currentIndex + 1), 5000);
        });

        // Initialize first slide view
        updateSlides();
    });

    // 2. Interactive Search Icon Toggle
    const searchBlocks = document.querySelectorAll('.eka-header-search');
    searchBlocks.forEach(searchBlock => {
        const btn = searchBlock.querySelector('.wp-block-search__button');
        const input = searchBlock.querySelector('.wp-block-search__input');

        if (btn && input) {
            input.classList.add('is-hidden');
            btn.addEventListener('click', function (e) {
                if (input.classList.contains('is-hidden')) {
                    e.preventDefault();
                    input.classList.remove('is-hidden');
                    input.focus();
                }
            });

            // Close on blur if empty
            input.addEventListener('blur', function () {
                if (!input.value.trim()) {
                    input.classList.add('is-hidden');
                }
            });
        }
    });
});
