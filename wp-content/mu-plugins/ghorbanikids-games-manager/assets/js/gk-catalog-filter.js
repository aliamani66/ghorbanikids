function initGameFilter() {
                var searchInput = document.getElementById('gkGameSearchInput');
                var clearBtn = document.getElementById('gkSearchClearBtn');
                var catChips = document.querySelectorAll('.gk-cat-chip');
                var ageChips = document.querySelectorAll('.gk-age-chip');
                var cards = document.querySelectorAll('.gk-game-card-item');
                var countElem = document.getElementById('gkGamesCount');
                var noResults = document.getElementById('gkNoResults');
                var resetBtn = document.getElementById('gkResetFiltersBtn');
                var app = document.getElementById('gkGamesArchiveApp');

                function norm(str) {
                    if (!str) return '';
                    try { str = decodeURIComponent(str); } catch(e) {}
                    return str.replace(/[-_+]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                }

                var urlParams = new URLSearchParams(window.location.search);
                var currentCat = norm(urlParams.get('cat') || (app ? app.getAttribute('data-initial-cat') : ''));
                var currentAge = norm(urlParams.get('age') || (app ? app.getAttribute('data-initial-age') : ''));
                var currentSearch = (urlParams.get('s') || '').trim().toLowerCase();

                if (searchInput && currentSearch) {
                    searchInput.value = currentSearch;
                }

                function syncActiveChips() {
                    var foundCat = false;
                    catChips.forEach(function(c) {
                        var cCat = norm(c.getAttribute('data-cat'));
                        var cSlug = norm(c.getAttribute('data-slug'));
                        if (currentCat && (cCat === currentCat || cSlug === currentCat || currentCat.indexOf(cCat) !== -1 || cCat.indexOf(currentCat) !== -1)) {
                            c.classList.add('active');
                            foundCat = true;
                        } else {
                            c.classList.remove('active');
                        }
                    });
                    if (!foundCat && catChips.length > 0) {
                        catChips[0].classList.add('active');
                    }

                    var foundAge = false;
                    ageChips.forEach(function(a) {
                        var aAge = norm(a.getAttribute('data-age'));
                        var aSlug = norm(a.getAttribute('data-slug'));
                        if (currentAge && (aAge === currentAge || aSlug === currentAge || currentAge.indexOf(aAge) !== -1 || aAge.indexOf(currentAge) !== -1)) {
                            a.classList.add('active');
                            foundAge = true;
                        } else {
                            a.classList.remove('active');
                        }
                    });
                    if (!foundAge && ageChips.length > 0) {
                        ageChips[0].classList.add('active');
                    }
                }

                function filterGames() {
                    var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
                    if (clearBtn) clearBtn.style.display = query ? 'flex' : 'none';

                    var visibleCount = 0;
                    cards.forEach(function(card) {
                        var title = norm(card.getAttribute('data-title'));
                        var cats = norm(card.getAttribute('data-cats'));
                        var ages = norm(card.getAttribute('data-ages'));

                        var matchesSearch = !query || title.indexOf(query) !== -1 || cats.indexOf(query) !== -1;
                        var matchesCat = !currentCat || cats.indexOf(currentCat) !== -1;
                        var matchesAge = !currentAge || ages.indexOf(currentAge) !== -1;

                        if (matchesSearch && matchesCat && matchesAge) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    if (countElem) countElem.textContent = visibleCount;
                    if (noResults) noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
                    if (resetBtn) resetBtn.style.display = (currentCat || currentAge || query) ? 'inline-flex' : 'none';

                    // تغییر URL بدون رفرش
                    var newUrl = new URL(window.location.href);
                    if (currentCat) newUrl.searchParams.set('cat', currentCat); else newUrl.searchParams.delete('cat');
                    if (currentAge) newUrl.searchParams.set('age', currentAge); else newUrl.searchParams.delete('age');
                    if (query) newUrl.searchParams.set('s', query); else newUrl.searchParams.delete('s');
                    window.history.replaceState({}, '', newUrl.toString());
                }

                if (searchInput) {
                    searchInput.addEventListener('input', filterGames);
                }
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        filterGames();
                    });
                }

                catChips.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        catChips.forEach(function(c) { c.classList.remove('active'); });
                        btn.classList.add('active');
                        currentCat = norm(btn.getAttribute('data-cat'));
                        filterGames();
                    });
                });

                ageChips.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        ageChips.forEach(function(a) { a.classList.remove('active'); });
                        btn.classList.add('active');
                        currentAge = norm(btn.getAttribute('data-age'));
                        filterGames();
                    });
                });

                window.gkResetAllFilters = function() {
                    if (searchInput) searchInput.value = '';
                    currentCat = '';
                    currentAge = '';
                    syncActiveChips();
                    filterGames();
                };

                if (resetBtn) {
                    resetBtn.addEventListener('click', window.gkResetAllFilters);
                }

                syncActiveChips();
                filterGames();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initGameFilter);
            } else {
                initGameFilter();
            }
        })();
        
        <?php
        return ob_get_clean();
    }

}

GhorbaniKids_Games::get_instance();