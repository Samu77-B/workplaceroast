(function (global) {
    var ICON_BASE = 'images/categories/';

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
        'hot-chocolate': ICON_BASE + 'chocolate.svg'
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

    function getCategoryIconSrc(category) {
        if (!category) {
            return '';
        }
        var icon = String(category.icon || '').trim();
        if (/\.(png|svg|webp|jpe?g|gif)$/i.test(icon) || icon.indexOf('images/') === 0 || icon.charAt(0) === '/') {
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

    global.CATEGORY_ICONS = CATEGORY_ICONS;
    global.getCategoryIconSrc = getCategoryIconSrc;
    global.renderCategoryIconHtml = renderCategoryIconHtml;
})(window);
