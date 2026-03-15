// Dynamic Product Loader for Main Website
// This script will load products from the database and update the HTML

async function loadCategoriesFromDatabase() {
    try {
        console.log('Loading categories from database...');
        if (typeof CONFIG === 'undefined' || !CONFIG.API_BASE_URL) {
            console.warn('CONFIG not available, skipping database load');
            return [];
        }
        const response = await fetch(`${CONFIG.API_BASE_URL}/categories.php`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.categories) {
            console.log('Categories loaded from database:', data.categories);
            return data.categories;
        } else {
            console.error('Failed to load categories from database:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error loading categories from database:', error);
        return [];
    }
}

function updateCategorySubtitles(categories) {
    console.log('Updating category subtitles...', categories);
    
    const categoryMapping = {
        'coffees': 'coffees',
        'chai': 'chai',
        'cold-drinks': 'cold-drinks', 
        'tea': 'everyday-teas',
        'everyday-teas': 'everyday-teas',
        'matcha-teas': 'matcha-teas',
        'specialties': 'specialties',
        'chocolate': 'chocolate'
    };
    
    categories.forEach(category => {
        const pageId = categoryMapping[category.slug] || category.slug;
        const page = document.getElementById(pageId);
        
        if (page && category.subtitle) {
            let subtitleElement = page.querySelector('.section-subtitle');
            if (subtitleElement) {
                subtitleElement.textContent = category.subtitle;
                console.log(`Updated subtitle for ${pageId}: ${category.subtitle}`);
            }
        }
    });
}

async function loadProductsFromDatabase() {
    try {
        console.log('Loading products from database...');
        if (typeof CONFIG === 'undefined' || !CONFIG.API_BASE_URL) {
            console.warn('CONFIG not available, skipping database load');
            return [];
        }
        console.log('API URL:', CONFIG.API_BASE_URL);
        
        const response = await fetch(`${CONFIG.API_BASE_URL}/products.php`);
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success && data.products) {
            console.log('Products loaded from database:', data.products);
            return data.products;
        } else {
            console.error('Failed to load products from database:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error loading products from database:', error);
        return [];
    }
}

function updateProductHTML(products) {
    console.log('updateProductHTML called with products:', products.length);
    
    // Map database categories to storefront categories
    const categoryMapping = {
        'coffees': 'coffees',
        'chai': 'chai',
        'cold-drinks': 'cold-drinks', 
        'tea': 'everyday-teas',
        'matcha': 'matcha-teas',
        'special': 'specialties',
        'chocolate': 'chocolate'
    };
    
    // Group products by category
    const productsByCategory = {};
    products.forEach(product => {
        const storefrontCategory = categoryMapping[product.category] || product.category;
        if (!productsByCategory[storefrontCategory]) {
            productsByCategory[storefrontCategory] = [];
        }
        productsByCategory[storefrontCategory].push(product);
    });
    
    console.log('Products grouped by category:', productsByCategory);
    
    // Update each category section
    Object.keys(productsByCategory).forEach(category => {
        const categoryProducts = productsByCategory[category];
        const categoryElement = document.getElementById(category);
        
        console.log(`Processing category: ${category}, products: ${categoryProducts.length}, element found: ${!!categoryElement}`);
        
        if (categoryElement) {
            // Handle tea category with subcategories
            if (category === 'everyday-teas') {
                updateTeaCategory(categoryElement, categoryProducts);
            } else if (category === 'matcha-teas') {
                // Handle matcha category with subcategories
                updateMatchaCategory(categoryElement, categoryProducts);
            } else {
                // Regular category update
                const itemList = categoryElement.querySelector('.item-list');
                console.log(`Category ${category}: itemList found: ${!!itemList}`);
                if (itemList) {
                    console.log(`Category ${category}: clearing ${itemList.children.length} existing items`);
                    // Clear existing items completely
                    itemList.innerHTML = '';
                    
                    // Add new items
                    categoryProducts.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'list-item';
                        item.onclick = () => openCustomizeModal(
                            product.name, 
                            parseFloat(product.regular_price), 
                            parseFloat(product.large_price), 
                            product.type,
                            product.id
                        );
                        
                        // Calculate price with discount (async, so we'll update after)
                        const regularPrice = parseFloat(product.regular_price);
                        const baseOriginalPrice = regularPrice * 1.25; // Assume 20% base discount
                        
                        // Use calculateProductDisplayPrice if available, otherwise use default
                        if (typeof calculateProductDisplayPrice === 'function') {
                            calculateProductDisplayPrice(regularPrice).then(priceInfo => {
                                const priceContainer = item.querySelector('.price-container');
                                if (priceContainer) {
                                    priceContainer.innerHTML = `
                                        <span class="original-price">Normal price £${priceInfo.originalPrice.toFixed(2)}</span>
                                        <span class="discounted-price">Your price £${priceInfo.finalPrice.toFixed(2)}</span>
                                    `;
                                }
                            }).catch(() => {
                                // Fallback to default display
                                const priceContainer = item.querySelector('.price-container');
                                if (priceContainer) {
                                    priceContainer.innerHTML = `
                                        <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                                        <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                                    `;
                                }
                            });
                        }
                        
                        item.innerHTML = `
                            <span class="list-item-name">${product.name}</span>
                            <div class="price-container">
                                <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                                <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                            </div>
                        `;
                        
                        itemList.appendChild(item);
                    });
                }
            }
        }
    });
}

function updateTeaCategory(categoryElement, products) {
    console.log('Updating tea category with products:', products);
    
    // Clear all existing content including subsections
    const mainItemList = categoryElement.querySelector('.item-list');
    if (mainItemList) {
        mainItemList.innerHTML = '';
    }
    
    // Remove any existing subsections
    const allSubsections = categoryElement.querySelectorAll('.subsection');
    allSubsections.forEach(subsection => subsection.remove());
    
    // Show ALL tea products in one list (no subcategory divisions)
    if (mainItemList) {
        products.forEach(product => {
            const item = document.createElement('div');
            item.className = 'list-item';
            item.onclick = () => openCustomizeModal(
                product.name, 
                parseFloat(product.regular_price), 
                parseFloat(product.large_price), 
                product.type,
                product.id
            );
            
            const regularPrice = parseFloat(product.regular_price);
            const baseOriginalPrice = regularPrice * 1.25;
            
            // Use calculateProductDisplayPrice if available
            if (typeof calculateProductDisplayPrice === 'function') {
                calculateProductDisplayPrice(regularPrice).then(priceInfo => {
                    const priceContainer = item.querySelector('.price-container');
                    if (priceContainer) {
                        priceContainer.innerHTML = `
                            <span class="original-price">Normal price £${priceInfo.originalPrice.toFixed(2)}</span>
                            <span class="discounted-price">Your price £${priceInfo.finalPrice.toFixed(2)}</span>
                        `;
                    }
                }).catch(() => {
                    // Fallback
                    const priceContainer = item.querySelector('.price-container');
                    if (priceContainer) {
                        priceContainer.innerHTML = `
                            <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                            <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                        `;
                    }
                });
            }
            
            item.innerHTML = `
                <span class="list-item-name">${product.name}</span>
                <div class="price-container">
                    <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                    <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                </div>
            `;
            
            mainItemList.appendChild(item);
        });
        
        console.log(`Added ${products.length} tea products to the list`);
    }
}

function updateMatchaCategory(categoryElement, products) {
    console.log('Updating matcha category with products:', products);
    
    // Clear all existing content including subsections
    const mainItemList = categoryElement.querySelector('.item-list');
    if (mainItemList) {
        mainItemList.innerHTML = '';
    }
    
    // Remove any existing subsections
    const menuSection = categoryElement.querySelector('.menu-section');
    const allSubsections = categoryElement.querySelectorAll('.subsection');
    allSubsections.forEach(subsection => subsection.remove());
    
    // Separate products by subcategory
    const productsWithoutSubcategory = products.filter(p => !p.subcategory || p.subcategory === '');
    const productsWithSubcategory = products.filter(p => p.subcategory && p.subcategory !== '');
    
    // Add products without subcategory to main list
    if (mainItemList && productsWithoutSubcategory.length > 0) {
        productsWithoutSubcategory.forEach(product => {
            const item = document.createElement('div');
            item.className = 'list-item';
            item.onclick = () => openCustomizeModal(
                product.name, 
                parseFloat(product.regular_price), 
                parseFloat(product.large_price), 
                product.type,
                product.id
            );
            
            item.innerHTML = `
                <span class="list-item-name">${product.name}</span>
                <div class="price-container">
                    <span class="original-price">Normal price £${(parseFloat(product.regular_price) * 1.25).toFixed(2)}</span>
                    <span class="discounted-price">Your price £${parseFloat(product.regular_price).toFixed(2)}</span>
                </div>
            `;
            
            mainItemList.appendChild(item);
        });
    }
    
    // Group products by subcategory
    const subcategories = {};
    productsWithSubcategory.forEach(product => {
        if (!subcategories[product.subcategory]) {
            subcategories[product.subcategory] = [];
        }
        subcategories[product.subcategory].push(product);
    });
    
    // Add subsections for each subcategory
    Object.keys(subcategories).forEach(subcategoryName => {
        const subsection = document.createElement('div');
        subsection.className = 'subsection';
        
        const title = document.createElement('h3');
        title.className = 'subsection-title';
        title.textContent = subcategoryName;
        subsection.appendChild(title);
        
        const itemList = document.createElement('div');
        itemList.className = 'item-list';
        
        subcategories[subcategoryName].forEach(product => {
            const item = document.createElement('div');
            item.className = 'list-item';
            item.onclick = () => openCustomizeModal(
                product.name, 
                parseFloat(product.regular_price), 
                parseFloat(product.large_price), 
                product.type,
                product.id
            );
            
            const regularPrice = parseFloat(product.regular_price);
            const baseOriginalPrice = regularPrice * 1.25;
            
            // Use calculateProductDisplayPrice if available
            if (typeof calculateProductDisplayPrice === 'function') {
                calculateProductDisplayPrice(regularPrice).then(priceInfo => {
                    const priceContainer = item.querySelector('.price-container');
                    if (priceContainer) {
                        priceContainer.innerHTML = `
                            <span class="original-price">Normal price £${priceInfo.originalPrice.toFixed(2)}</span>
                            <span class="discounted-price">Your price £${priceInfo.finalPrice.toFixed(2)}</span>
                        `;
                    }
                }).catch(() => {
                    // Fallback
                    const priceContainer = item.querySelector('.price-container');
                    if (priceContainer) {
                        priceContainer.innerHTML = `
                            <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                            <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                        `;
                    }
                });
            }
            
            item.innerHTML = `
                <span class="list-item-name">${product.name}</span>
                <div class="price-container">
                    <span class="original-price">Normal price £${baseOriginalPrice.toFixed(2)}</span>
                    <span class="discounted-price">Your price £${regularPrice.toFixed(2)}</span>
                </div>
            `;
            
            itemList.appendChild(item);
        });
        
        subsection.appendChild(itemList);
        menuSection.appendChild(subsection);
    });
    
    console.log(`Added ${productsWithoutSubcategory.length} main matcha products and ${productsWithSubcategory.length} special matcha products`);
}

async function initializeDynamicProducts() {
    console.log('Initializing dynamic products...');
    
    // Load categories from database
    const categories = await loadCategoriesFromDatabase();
    if (categories.length > 0) {
        updateCategorySubtitles(categories);
    }
    
    // Load products from database
    const products = await loadProductsFromDatabase();
    
    if (products.length > 0) {
        console.log('Updating HTML with database products:', products);
        console.log('Products by category:', products.reduce((acc, p) => {
            acc[p.category] = (acc[p.category] || 0) + 1;
            return acc;
        }, {}));
        updateProductHTML(products);
    } else {
        console.log('No products found in database, keeping existing HTML');
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for the page to fully load
    setTimeout(initializeDynamicProducts, 1000);
    
    // Also try to initialize when the window loads completely
    window.addEventListener('load', function() {
        setTimeout(initializeDynamicProducts, 2000);
    });
});

// Retry mechanism - try to load products multiple times
let retryCount = 0;
const maxRetries = 5;

function retryInitialization() {
    if (retryCount < maxRetries) {
        retryCount++;
        console.log(`Retry attempt ${retryCount} to load products...`);
        setTimeout(() => {
            initializeDynamicProducts().then(() => {
                // Check if products were loaded successfully
                const products = document.querySelectorAll('.list-item');
                if (products.length === 0) {
                    retryInitialization(); // Try again if no products found
                } else {
                    console.log('Products loaded successfully after retry');
                }
            });
        }, 1000 * retryCount);
    } else {
        console.log('Max retries reached. Products may not be available.');
    }
}

// Start retry mechanism after initial load
setTimeout(retryInitialization, 3000);

// Make the function globally available for manual triggering
window.refreshStorefrontProducts = initializeDynamicProducts;

// Also expose a function to check if products are loaded
window.checkProductsLoaded = function() {
    const products = document.querySelectorAll('.list-item');
    console.log(`Found ${products.length} products in the storefront`);
    return products.length > 0;
};
