// Theme Applier for Vanilla JavaScript
// This script applies themes based on the client ID in the URL path

// Theme configuration (inline version for vanilla JS)
const THEME_CONFIG = {
  // Default theme (fallback)
  default: {
    appName: 'Workplace Roast',
    primaryColor: '#431c0d',
    accentColor: '#8b4513',
    backgroundColor: '#ffffff',
    textColor: '#333333',
    secondaryColor: '#f5f5f5',
  },
  
  // Acme Hair Salon theme (Basic Tier)
  acmehairsalon: {
    appName: 'Acme Hair Salon - Workplace Roast',
    primaryColor: '#431c0d',
    accentColor: '#8b4513',
    backgroundColor: '#ffffff',
    textColor: '#333333',
    secondaryColor: '#f5f5f5',
  },
  
  // Acme Law Firm theme (Premium Tier)
  acmelawfirm: {
    appName: 'Acme Law Firm - Workplace Roast',
    primaryColor: '#1a4d3a',
    accentColor: '#2d8659',
    backgroundColor: '#ffffff',
    textColor: '#333333',
    secondaryColor: '#f0f7f4',
  },
};

// Helper function to get theme by client ID
function getThemeByClientId(clientId) {
  if (!clientId) {
    return THEME_CONFIG.default;
  }
  
  const normalizedClientId = clientId.toLowerCase();
  return THEME_CONFIG[normalizedClientId] || THEME_CONFIG.default;
}

// Function to extract clientId from URL path
function getClientIdFromPath() {
  const path = window.location.pathname.toLowerCase();
  
  // Extract client ID from path like /pwa/acmelawfirm or /pwa/acmehairsalon
  const pathMatch = path.match(/\/pwa\/([^\/]+)/);
  if (pathMatch && pathMatch[1]) {
    return pathMatch[1];
  }
  
  return null;
}

// Function to apply theme
function applyTheme() {
  // Get clientId from URL path
  const clientId = getClientIdFromPath();
  
  // Get theme configuration
  const theme = getThemeByClientId(clientId);
  
  // Set CSS Variables on the <html> element
  const root = document.documentElement;
  
  // Set theme CSS variables
  root.style.setProperty('--theme-primary', theme.primaryColor);
  root.style.setProperty('--theme-accent', theme.accentColor);
  root.style.setProperty('--theme-background', theme.backgroundColor);
  root.style.setProperty('--theme-text', theme.textColor);
  root.style.setProperty('--theme-secondary', theme.secondaryColor);
  
  // Also set legacy CSS variables for backward compatibility
  root.style.setProperty('--primary-color', theme.primaryColor);
  root.style.setProperty('--accent-color', theme.accentColor);
  
  // Update the document title
  if (document.title !== theme.appName) {
    document.title = theme.appName;
  }
  
  // Update PWA meta tags
  const pwaTitleMeta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
  if (pwaTitleMeta) {
    pwaTitleMeta.setAttribute('content', theme.appName);
  }
  
  const themeColorMeta = document.querySelector('meta[name="theme-color"]');
  if (themeColorMeta) {
    themeColorMeta.setAttribute('content', theme.primaryColor);
  }
  
  // Debug logging
  console.log('Theme applied:', {
    clientId: clientId || 'default',
    primaryColor: theme.primaryColor,
    accentColor: theme.accentColor,
    cssVarSet: root.style.getPropertyValue('--theme-primary')
  });
}

// Apply theme immediately (don't wait for DOM)
// This ensures CSS variables are set before the page renders
applyTheme();

// Also apply when DOM is ready (in case script loads late)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', applyTheme);
} else {
  // DOM is already ready, apply again to ensure it's set
  applyTheme();
}

// Re-apply theme if URL changes (for SPA navigation if applicable)
window.addEventListener('popstate', applyTheme);

// Re-apply on hashchange (in case of client-side routing)
window.addEventListener('hashchange', applyTheme);

