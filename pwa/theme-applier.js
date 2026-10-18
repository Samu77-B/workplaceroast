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

function hexLuma(hex) {
  const h = String(hex || '').replace('#', '');
  if (h.length !== 6) {
    return 0;
  }
  const r = parseInt(h.slice(0, 2), 16) / 255;
  const g = parseInt(h.slice(2, 4), 16) / 255;
  const b = parseInt(h.slice(4, 6), 16) / 255;
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function setIconContrastFromPrimary(primary) {
  const lightBrand = hexLuma(primary) > 0.62;
  const root = document.documentElement;
  root.style.setProperty('--icon-on-brand', lightBrand ? '#0f172a' : '#ffffff');
  root.setAttribute('data-brand-luma', lightBrand ? 'light' : 'dark');
}

function setStatusBarColor(color) {
  if (!color) return;
  const themeColorMeta = document.querySelector('meta[name="theme-color"]');
  if (themeColorMeta) {
    themeColorMeta.setAttribute('content', color);
  }
  document.documentElement.style.backgroundColor = color;
  const statusStyle = document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]');
  if (statusStyle) {
    statusStyle.setAttribute('content', hexLuma(color) > 0.62 ? 'default' : 'black-translucent');
  }
}

function isWorkplaceRoastDefaultColor(hex) {
  const value = String(hex || '').trim().toLowerCase();
  return value === '#431c0d' || value === '#8b4513' || value === '#5a2e1a' || value === '#8a4725';
}

function applyCafeColors(primary, accent) {
  const root = document.documentElement;
  if (primary && isWorkplaceRoastDefaultColor(primary)) {
    primary = '';
  }
  if (primary || accent) {
    root.setAttribute('data-cafe-brand', 'on');
  }
  if (primary) {
    root.style.setProperty('--theme-primary', primary);
    root.style.setProperty('--primary-color', primary);
    setIconContrastFromPrimary(primary);
    setStatusBarColor(primary);
  }
  const accentUse = (accent && !isWorkplaceRoastDefaultColor(accent)) ? accent : (primary || accent);
  if (accentUse) {
    root.style.setProperty('--theme-accent', accentUse);
    root.style.setProperty('--accent-color', accentUse);
  }
  try {
    if (primary) {
      localStorage.setItem('cafe_brand_primary', primary);
      localStorage.setItem('cafe_brand_accent', accentUse || primary);
      const slug = (typeof getCafeSlugFromPath === 'function') ? getCafeSlugFromPath() : '';
      if (slug) {
        let map = {};
        try {
          map = JSON.parse(localStorage.getItem('wr_brand_by_slug') || '{}');
        } catch (err) {
          map = {};
        }
        map[slug] = { primary: primary, accent: accentUse || primary };
        localStorage.setItem('wr_brand_by_slug', JSON.stringify(map));
      }
    }
  } catch (err) {}
}

var DEFAULT_CAFE_LOGO = '/pwa/Brand/Workplace-Roast-Logo-V4.svg';

function applyCafeLogo(logoUrl, cafeName) {
  const img = document.getElementById('headerCafeLogo');
  const name = (cafeName || localStorage.getItem('cafe_name') || 'Cafe').trim();
  const url = String(logoUrl || '').trim();
  if (url) {
    localStorage.setItem('cafe_logo_url', url);
  } else {
    localStorage.removeItem('cafe_logo_url');
  }
  if (!img) {
    return;
  }
  if (url) {
    img.src = url;
    img.alt = name + ' logo';
    img.classList.remove('logo--mono');
  } else {
    img.src = DEFAULT_CAFE_LOGO;
    img.alt = name ? name + ' logo' : 'Workplace Roast Logo';
    img.classList.add('logo--mono');
  }
}

// Function to apply theme
function applyTheme() {
  // Get clientId from URL path
  const clientId = getClientIdFromPath();
  
  // Get theme configuration
  const theme = getThemeByClientId(clientId);
  
  // Set CSS Variables on the <html> element
  const root = document.documentElement;
  
  const cafeBrandLocked = root.getAttribute('data-cafe-brand') === 'on';
  const cafeSlugPending = typeof getCafeSlugFromPath === 'function' && !!getCafeSlugFromPath();

  // Set theme CSS variables
  if (!cafeBrandLocked && !cafeSlugPending) {
    root.style.setProperty('--theme-primary', theme.primaryColor);
    root.style.setProperty('--theme-accent', theme.accentColor);
    root.style.setProperty('--primary-color', theme.primaryColor);
    root.style.setProperty('--accent-color', theme.accentColor);
    setIconContrastFromPrimary(theme.primaryColor);
  }
  root.style.setProperty('--theme-background', theme.backgroundColor);
  root.style.setProperty('--theme-text', theme.textColor);
  root.style.setProperty('--theme-secondary', theme.secondaryColor);
  
  if (!cafeSlugPending) {
    if (document.title !== theme.appName) {
      document.title = theme.appName;
    }
    const pwaTitleMeta = document.querySelector('meta[name="apple-mobile-web-app-title"]');
    if (pwaTitleMeta) {
      pwaTitleMeta.setAttribute('content', theme.appName);
    }
  }

  if (!cafeBrandLocked && !cafeSlugPending) {
    setStatusBarColor(theme.primaryColor);
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

