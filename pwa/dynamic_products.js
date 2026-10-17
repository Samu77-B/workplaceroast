/**
 * Storefront menu: load this cafe's categories and products from the API.
 * Once a cafe is known, static demo drinks are never shown.
 */
function getStorefrontCafeId() {
    if (typeof CONFIG !== 'undefined' && CONFIG.CAFE_ID) {
        return parseInt(CONFIG.CAFE_ID, 10) || 0;
    }
    return parseInt(localStorage.getItem('cafe_id') || '0', 10) || 0;
}

function storefrontApiBase() {
    if (typeof CONFIG !== 'undefined' && CONFIG.API_BASE_URL) {
        return CONFIG.API_BASE_URL;
    }
    return window.location.origin + '/pwa/api';
}

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function categoryIconMarkup(category) {
    var inner = '';
    if (typeof window.renderCategoryIconHtml === 'function') {
        inner = window.renderCategoryIconHtml(category, (category && category.name || '?').charAt(0));
    } else {
        inner = '<span class="category-icon-fallback" aria-hidden="true">' +
            escapeHtml((category && category.name || '?').charAt(0).toUpperCase()) + '</span>';
    }
    return '<div class="item-icon">' + inner + '</div>';
}

function productPrices(product) {
    var sizes = product.sizes || {};
    var regular = typeof sizes.regular === 'number' ? sizes.regular : Number(product.price) || 0;
    var large = typeof sizes.large === 'number' ? sizes.large : 0;
    return { regular: regular, large: large };
}

function formatPrice(amount) {
    return '£' + Number(amount).toFixed(2);
}

function categoryPageId(slug) {
    return String(slug || '').replace(/[^a-z0-9-]/gi, '');
}

function renderEmptyMenu() {
    var grid = document.querySelector('#mainMenu .menu-grid');
    if (grid) {
        grid.innerHTML = '<p class="empty-menu-message">No drinks available yet</p>';
    }
    document.querySelectorAll('.page.dynamic-category-page').forEach(function (page) {
        page.remove();
    });
}

function renderCategoryGrid(categories) {
    var grid = document.querySelector('#mainMenu .menu-grid');
    if (!grid) return;

    if (!categories.length) {
        grid.innerHTML = '<p class="empty-menu-message">No drinks available yet</p>';
        return;
    }

    grid.innerHTML = categories.map(function (category) {
        var pageId = categoryPageId(category.slug);
        var subtitle = category.subtitle || '';
        return (
            '<div class="menu-item" onclick="showCategory(\'' + escapeHtml(pageId) + '\')">' +
                categoryIconMarkup(category) +
                '<div class="item-name">' + escapeHtml(category.name) + '</div>' +
                (subtitle ? '<div class="menu-item-subtitle">' + escapeHtml(subtitle) + '</div>' : '') +
            '</div>'
        );
    }).join('');
}

function renderCategoryPages(categories, products) {
    var host = document.getElementById('dynamicCategoryPages');
    if (!host) {
        host = document.createElement('div');
        host.id = 'dynamicCategoryPages';
        var mainMenu = document.getElementById('mainMenu');
        if (mainMenu && mainMenu.parentNode) {
            mainMenu.parentNode.insertBefore(host, mainMenu.nextSibling);
        } else {
            document.body.appendChild(host);
        }
    }

    host.innerHTML = categories.map(function (category) {
        var pageId = categoryPageId(category.slug);
        var items = products.filter(function (product) {
            return product.category === category.slug && product.active !== false;
        });
        var listHtml = items.length
            ? items.map(function (product) {
                var prices = productPrices(product);
                var nameJs = JSON.stringify(product.name);
                var typeJs = JSON.stringify(category.slug);
                var idArg = product.id ? String(product.id) : 'null';
                return (
                    '<div class="list-item" onclick=\'openCustomizeModal(' + nameJs + ', ' +
                        prices.regular + ', ' + prices.large + ', ' + typeJs + ', ' + idArg + ')\'>' +
                        '<span class="list-item-name">' + escapeHtml(product.name) + '</span>' +
                        '<div class="price-container">' +
                            '<span class="discounted-price">' + formatPrice(prices.regular) + '</span>' +
                        '</div>' +
                    '</div>'
                );
            }).join('')
            : '<p class="empty-menu-message">No drinks available yet</p>';

        return (
            '<div class="page dynamic-category-page" id="' + escapeHtml(pageId) + '">' +
                '<div class="menu-section">' +
                    '<h2 class="section-title">' + escapeHtml(category.name) + '</h2>' +
                    (category.subtitle ? '<p class="section-subtitle">' + escapeHtml(category.subtitle) + '</p>' : '') +
                    '<div class="item-list">' + listHtml + '</div>' +
                '</div>' +
                '<div class="footer-placeholder"></div>' +
            '</div>'
        );
    }).join('');
}

async function loadStorefrontMenu() {
    var cafeId = getStorefrontCafeId();
    if (!cafeId) {
        renderEmptyMenu();
        return;
    }

    var apiBase = storefrontApiBase();
    try {
        var catRes = await fetch(apiBase + '/categories.php?cafe_id=' + encodeURIComponent(cafeId));
        var prodRes = await fetch(apiBase + '/products.php?cafe_id=' + encodeURIComponent(cafeId));
        var catData = await catRes.json();
        var prodData = await prodRes.json();
        var categories = (catData.success && Array.isArray(catData.categories))
            ? catData.categories.filter(function (c) { return c.active !== false && categoryPageId(c.slug); })
            : [];
        var products = (prodData.success && Array.isArray(prodData.products))
            ? prodData.products.filter(function (p) { return p.active !== false; })
            : [];
        var known = {};
        categories.forEach(function (category) {
            known[category.slug] = true;
        });
        products.forEach(function (product) {
            var slug = categoryPageId(product.category);
            if (!slug || known[slug]) return;
            known[slug] = true;
            categories.push({
                slug: slug,
                name: slug.replace(/-/g, ' ').replace(/\b\w/g, function (ch) { return ch.toUpperCase(); }),
                active: true
            });
        });

        renderCategoryGrid(categories);
        renderCategoryPages(categories, products);
        window.storefrontMenu = { categories: categories, products: products, cafeId: cafeId };
    } catch (error) {
        console.error('Failed to load cafe menu:', error);
        renderEmptyMenu();
    }
}

function initializeDynamicProducts() {
    if (typeof getCafeSlugFromPath === 'function' && getCafeSlugFromPath()) {
        return;
    }
    loadStorefrontMenu();
}

window.loadStorefrontMenu = loadStorefrontMenu;
window.refreshStorefrontProducts = loadStorefrontMenu;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDynamicProducts);
} else {
    initializeDynamicProducts();
}
