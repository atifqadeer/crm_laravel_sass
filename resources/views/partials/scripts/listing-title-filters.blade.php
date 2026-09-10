<script>
    (function() {
        function selectedCategoryIds() {
            var checkboxes = document.querySelectorAll('.category-filter[type="checkbox"]');
            if (checkboxes.length) {
                return Array.from(document.querySelectorAll('.category-filter:checked'))
                    .map(function(el) {
                        return String(el.getAttribute('data-category-id') || '');
                    })
                    .filter(function(id) {
                        return id !== '';
                    });
            }
            return Array.isArray(window.__listingSelectedCategoryIds) ? window.__listingSelectedCategoryIds : [];
        }

        function selectedTypeFromLabel(overrideType) {
            if (typeof overrideType === 'string') {
                return overrideType;
            }
            var label = document.getElementById('showFilterType');
            var type = ((label && label.textContent) || '').trim().toLowerCase();
            return (type === 'specialist' || type === 'regular') ? type : '';
        }

        function titleSearchValue() {
            var input = document.getElementById('titleSearchInput');
            return ((input && input.value) || '').trim().toLowerCase();
        }

        function isAllTitlesControl(el) {
            var id = el.getAttribute('data-title-id');
            return id === null || id === '';
        }

        function titleMatches(el, categories, type, search) {
            if (isAllTitlesControl(el)) {
                return true;
            }
            var cat = String(el.getAttribute('data-category-id') || '');
            var titleType = String(el.getAttribute('data-type') || '').toLowerCase();
            var matchCat = categories.length === 0 || categories.indexOf(cat) !== -1;
            var matchType = !type || titleType === type;
            var labelEl = el.closest('.form-check') ? el.closest('.form-check').querySelector('label') : el;
            var label = ((labelEl && labelEl.textContent) || '').toLowerCase();
            var matchSearch = !search || label.indexOf(search) !== -1;
            return matchCat && matchType && matchSearch;
        }

        function visibleTitleInputs() {
            return Array.from(document.querySelectorAll('.title-filter')).filter(function(el) {
                if (isAllTitlesControl(el)) {
                    return false;
                }
                var row = el.closest('.form-check') || el;
                return !row.classList.contains('listing-title-hidden');
            });
        }

        window.getVisibleListingTitleIds = function() {
            return visibleTitleInputs()
                .filter(function(el) {
                    return el.type === 'checkbox' && el.checked;
                })
                .map(function(el) {
                    return el.getAttribute('data-title-id');
                })
                .filter(Boolean);
        };

        window.applyListingTitleVisibility = function(overrideType) {
            if (!document.querySelector('.title-filter[data-category-id], .title-filter[data-type]')) {
                return;
            }
            var categories = selectedCategoryIds();
            var type = selectedTypeFromLabel(overrideType);
            var search = titleSearchValue();
            var visibleCount = 0;

            document.querySelectorAll('.title-filter').forEach(function(el) {
                var row = el.closest('.form-check') || el;
                var visible = titleMatches(el, categories, type, search);
                row.classList.toggle('listing-title-hidden', !visible);
                if (row.style) {
                    row.style.display = visible ? '' : 'none';
                }
                if (!visible && !isAllTitlesControl(el) && el.type === 'checkbox' && el.checked) {
                    el.checked = false;
                }
                if (visible && !isAllTitlesControl(el)) {
                    visibleCount += 1;
                }
            });

            var emptyId = 'listingTitleEmptyMsg';
            var list = document.getElementById('titleList');
            if (list) {
                var empty = document.getElementById(emptyId);
                if (!empty) {
                    empty = document.createElement('div');
                    empty.id = emptyId;
                    empty.className = 'text-muted small px-1 py-1';
                    list.appendChild(empty);
                }
                empty.textContent = 'No titles for the selected category / type';
                empty.style.display = visibleCount === 0 ? '' : 'none';
            }

            var checked = visibleTitleInputs().filter(function(el) {
                return el.type === 'checkbox' && el.checked;
            }).length;
            var label = document.getElementById('showFilterTitle');
            if (label && document.querySelector('#titleList .title-filter')) {
                label.textContent = checked > 0 ? ('Selected Titles (' + checked + ')') : 'All Titles';
            }

            var toggle = document.getElementById('titleToggleContainer');
            if (toggle) {
                var selectAll = toggle.querySelector('.filter-select-all');
                var deselectAll = toggle.querySelector('.filter-deselect-all');
                if (selectAll) {
                    selectAll.style.display = checked < visibleCount ? '' : 'none';
                }
                if (deselectAll) {
                    deselectAll.style.display = checked > 0 ? '' : 'none';
                }
            }
        };

        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('category-filter')) {
                window.applyListingTitleVisibility();
            }
        }, true);

        document.addEventListener('click', function(e) {
            var catEl = e.target.closest && e.target.closest('a.category-filter');
            if (catEl) {
                var catId = String(catEl.getAttribute('data-category-id') || '');
                window.__listingSelectedCategoryIds = catId ? [catId] : [];
                window.applyListingTitleVisibility();
            }
            var typeEl = e.target.closest && e.target.closest('.type-filter');
            if (typeEl) {
                var t = (typeEl.textContent || '').trim().toLowerCase();
                var type = (t === 'specialist' || t === 'regular') ? t : '';
                window.applyListingTitleVisibility(type);
                return;
            }
            var toggle = e.target.closest && e.target.closest('.filter-select-all, .filter-deselect-all');
            if (toggle) {
                var target = toggle.getAttribute('data-target');
                if (target === '.title-filter' || target === '.category-filter') {
                    setTimeout(function() {
                        window.applyListingTitleVisibility();
                    }, 0);
                }
            }
        }, true);

        document.addEventListener('keyup', function(e) {
            if (e.target && e.target.id === 'titleSearchInput') {
                window.applyListingTitleVisibility();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.applyListingTitleVisibility();
        });
    })();
</script>
<style>
    .listing-title-hidden {
        display: none !important;
    }
</style>
