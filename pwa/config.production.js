// Production Configuration for Workplace Roast PWA
// This file is for deployment to workplaceroast.com/pwa
// Copy this to config.js after updating with your actual credentials

const CONFIG = {
    // Backend API URL - Update to your production domain
    // For subdirectory deployment: 'https://workplaceroast.com/pwa/api'
    API_BASE_URL: 'https://workplaceroast.com/pwa/api',
    
    // Stripe Configuration
    // Get your LIVE keys from: https://dashboard.stripe.com/apikeys
    // IMPORTANT: Use LIVE keys (pk_live_...) for production, not test keys
    STRIPE_PUBLISHABLE_KEY: 'pk_live_YOUR_STRIPE_PUBLISHABLE_KEY_HERE',
    
    // Admin Configuration
    // Generate a secure random token (see instructions below)
    // This MUST match the $ADMIN_TOKEN in api/config.php
    ADMIN_TOKEN: 'YOUR_SECURE_ADMIN_TOKEN_HERE',
    
    // Environment
    ENVIRONMENT: 'production'
};

// Auto-detect production environment
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    CONFIG.ENVIRONMENT = 'production';
}

