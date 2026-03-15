// Configuration for Workplace Roast PWA
// IMPORTANT: Replace these placeholder values with your actual credentials

// Auto-detect environment
const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

// Function to get cafe_id from URL path or parameter
function getCafeIdFromPath() {
    // Check URL parameter first
    const urlParams = new URLSearchParams(window.location.search);
    const cafeIdParam = urlParams.get('cafe_id');
    if (cafeIdParam) {
        return parseInt(cafeIdParam);
    }
    
    // Check URL path
    const path = window.location.pathname.toLowerCase();
    
    // Path mapping to cafe IDs
    const pathMap = {
        '/pwa/acmehairsalon': 1,      // Salon Cafe ID: 1
        '/pwa/acmelawfirm': 2          // Law Firm Cafe ID: 2
    };
    
    // Check if path matches a cafe path
    for (const [pathPattern, cafeId] of Object.entries(pathMap)) {
        if (path.startsWith(pathPattern)) {
            return cafeId;
        }
    }
    
    return null;
}

// Store cafe_id
const detectedCafeId = getCafeIdFromPath();

// Configuration object - values change based on environment
const CONFIG = {
    // Backend API URL
    // Using relative path works for both local and production
    // This automatically uses the current domain (e.g., /api or /pwa/api)
    API_BASE_URL: window.location.origin + (window.location.pathname.includes('/pwa/') ? '/pwa/api' : '/api'),
    
    // Stripe Configuration
    // Get your keys from: https://dashboard.stripe.com/apikeys
    // TEST MODE: Use keys starting with pk_test_ and sk_test_
    // LIVE MODE: Use keys starting with pk_live_ and sk_live_
    STRIPE_PUBLISHABLE_KEY: isProduction 
        ? 'pk_test_51NQcOiA729hrPTyM2u6Xtppi4YsFKFo3RqsvipMiIuysgfo0LyNkfaueiysRaWRjBbCO9yNzjH6foEMLC1XXQVbX00YmHlBBog'
        : 'pk_test_your_stripe_publishable_key_here',
    
    // Admin Configuration
    // Generate a secure random token (see instructions below)
    // This MUST match the $ADMIN_TOKEN in api/config.php
    ADMIN_TOKEN: isProduction ? 'teas2024' : 'your-secure-admin-token-here',
    
    // Environment
    ENVIRONMENT: isProduction ? 'production' : 'development',
    
    // Cafe/Corporate Client Configuration
    CAFE_ID: detectedCafeId,  // Auto-detected from URL path or parameter
    
    // Tier mapping based on cafe_id
    CAFE_TIERS: {
        1: 'basic',      // Salon Cafe ID: 1 (Basic Tier)
        2: 'premium'     // Law Firm Cafe ID: 2 (Premium Tier)
    },
    
    // Cafe names mapping
    CAFE_NAMES: {
        1: 'Acme Hair Salon',      // Salon Cafe ID: 1
        2: 'Acme Law Firm'         // Law Firm Cafe ID: 2
    }
};

// Store cafe_id in localStorage for persistence
if (detectedCafeId) {
    localStorage.setItem('cafe_id', detectedCafeId.toString());
    localStorage.setItem('corporate_client_id', detectedCafeId.toString());
    
    // Determine tier and store
    const tier = CONFIG.CAFE_TIERS[detectedCafeId] || 'basic';
    localStorage.setItem('cafe_tier', tier);
    
    // Get cafe name and update page title
    const cafeName = CONFIG.CAFE_NAMES[detectedCafeId] || 'Workplace Roast';
    if (cafeName) {
        document.title = cafeName + ' - Ordering';
        // Update PWA title meta tag
        const pwaTitleMeta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
        if (pwaTitleMeta) {
            pwaTitleMeta.setAttribute('content', cafeName);
        }
        localStorage.setItem('cafe_name', cafeName);
    }
    
    console.log('Cafe ID detected:', detectedCafeId, 'Name:', cafeName, 'Tier:', tier);
}

