(function (global) {
    var ICON_BASE = 'images/categories/';

    var CATEGORY_ICON_CHOICES = [
        { key: 'hot-drinks', label: 'Hot drinks', src: ICON_BASE + 'hot-drinks.svg' },
        { key: 'cold-drinks', label: 'Cold drinks', src: ICON_BASE + 'cold-drinks.svg' },
        { key: 'coffee', label: 'Coffee', src: ICON_BASE + 'coffee.svg' },
        { key: 'tea', label: 'Tea', src: ICON_BASE + 'loose-leaf-tea.svg' },
        { key: 'matcha', label: 'Matcha', src: ICON_BASE + 'matcha.svg' },
        { key: 'chai', label: 'Chai', src: ICON_BASE + 'chai.svg' },
        { key: 'chocolate', label: 'Chocolate', src: ICON_BASE + 'chocolate.svg' },
        { key: 'sweet', label: 'Sweet', src: ICON_BASE + 'sweet.svg' },
        { key: 'food', label: 'Food', src: ICON_BASE + 'food.svg' },
        { key: 'specials', label: 'Specials', src: ICON_BASE + 'specials.svg' }
    ];

    var CATEGORY_ICONS = {
        'hot-drinks': ICON_BASE + 'hot-drinks.svg',
        'hot-drink': ICON_BASE + 'hot-drinks.svg',
        'cold-drinks': ICON_BASE + 'cold-drinks.svg',
        'cold-drink': ICON_BASE + 'cold-drinks.svg',
        'everyday-teas': ICON_BASE + 'loose-leaf-tea.svg',
        'loose-leaf-tea': ICON_BASE + 'loose-leaf-tea.svg',
        'tea': ICON_BASE + 'loose-leaf-tea.svg',
        'matcha-teas': ICON_BASE + 'matcha.svg',
        'matcha': ICON_BASE + 'matcha.svg',
        'coffees': ICON_BASE + 'coffee.svg',
        'coffee': ICON_BASE + 'coffee.svg',
        'specialties': ICON_BASE + 'specials.svg',
        'specials': ICON_BASE + 'specials.svg',
        'special': ICON_BASE + 'specials.svg',
        'food': ICON_BASE + 'food.svg',
        'chai': ICON_BASE + 'chai.svg',
        'chai-tea': ICON_BASE + 'chai.svg',
        'chocolate': ICON_BASE + 'chocolate.svg',
        'hot-chocolate': ICON_BASE + 'chocolate.svg',
        'sweet': ICON_BASE + 'sweet.svg',
        'sweets': ICON_BASE + 'sweet.svg',
        'dessert': ICON_BASE + 'sweet.svg',
        'desserts': ICON_BASE + 'sweet.svg',
        'cake': ICON_BASE + 'sweet.svg',
        'cakes': ICON_BASE + 'sweet.svg'
    };

    function keyFromName(name) {
        return String(name || '').trim().toLowerCase().replace(/\s+/g, '-');
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function isIconPath(icon) {
        var value = String(icon || '').trim();
        return /\.(png|svg|webp|jpe?g|gif)$/i.test(value) || value.indexOf('images/') === 0 || value.charAt(0) === '/';
    }

    function getCategoryIconSrc(category) {
        if (!category) {
            return '';
        }
        var icon = String(category.icon || '').trim();
        if (isIconPath(icon)) {
            return icon;
        }
        var slug = String(category.slug || '').toLowerCase();
        var nameKey = keyFromName(category.name);
        return CATEGORY_ICONS[slug] || CATEGORY_ICONS[nameKey] || '';
    }

    function renderCategoryIconHtml(category, fallback) {
        var src = getCategoryIconSrc(category);
        if (src) {
            return '<span class="icon-shape" style="--icon-url: url(\'' + escapeAttr(src) + '\')" role="img" aria-label="' + escapeAttr((category && category.name) || 'Category') + '"></span>';
        }
        return escapeAttr((category && category.icon) || fallback || '📁');
    }

    function renderCategoryIconPicker(container, hiddenInput, selectedSrc) {
        if (!container || !hiddenInput) {
            return;
        }
        var current = selectedSrc || hiddenInput.value || '';
        hiddenInput.value = current;
        container.innerHTML = CATEGORY_ICON_CHOICES.map(function (choice) {
            var selected = current === choice.src ? ' selected' : '';
            return '<button type="button" class="icon-picker-btn' + selected + '" data-icon-src="' + escapeAttr(choice.src) + '" title="' + escapeAttr(choice.label) + '">' +
                '<span class="icon-shape" style="--icon-url: url(\'' + escapeAttr(choice.src) + '\')" role="img" aria-hidden="true"></span>' +
                '<span class="icon-picker-label">' + escapeAttr(choice.label) + '</span>' +
            '</button>';
        }).join('');
        container.onclick = function (event) {
            var btn = event.target.closest('.icon-picker-btn');
            if (!btn) return;
            renderCategoryIconPicker(container, hiddenInput, btn.getAttribute('data-icon-src') || '');
        };
    }

    function suggestCategoryIcon(slug, name) {
        return getCategoryIconSrc({ slug: slug, name: name, icon: '' });
    }

    global.CATEGORY_ICONS = CATEGORY_ICONS;
    global.CATEGORY_ICON_CHOICES = CATEGORY_ICON_CHOICES;
    global.getCategoryIconSrc = getCategoryIconSrc;
    global.renderCategoryIconHtml = renderCategoryIconHtml;
    global.renderCategoryIconPicker = renderCategoryIconPicker;
    global.suggestCategoryIcon = suggestCategoryIcon;
})(window);
