// Theme configuration for different client IDs
// Maps client ID (URL slug) to theme properties

export const THEME_CONFIG = {
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
export const getThemeByClientId = (clientId) => {
  if (!clientId) {
    return THEME_CONFIG.default;
  }
  
  const normalizedClientId = clientId.toLowerCase();
  return THEME_CONFIG[normalizedClientId] || THEME_CONFIG.default;
};

