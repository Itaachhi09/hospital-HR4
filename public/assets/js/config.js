/**
 * URL Configuration for the HR Management System
 * Version: 3.0 - Dual API Support (REST + Legacy)
 */

// Dynamically detect base path from current location
function getBasePath() {
  const path = window.location.pathname;
  // Remove trailing filename (e.g., index.php, admin_landing.php)
  const pathParts = path.split('/').filter(p => p && !p.includes('.php') && !p.includes('.html'));
  // If we're in /public/, go up one level; otherwise use current path
  if (pathParts.includes('public')) {
    const publicIndex = pathParts.indexOf('public');
    return '/' + pathParts.slice(0, publicIndex).join('/') + '/';
  }
  // Default to root if we can't determine
  return pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
}

// Base URL for the entire application (dynamically detected)
export const BASE_URL = window.location.origin + getBasePath();

// NEW: REST API (Modern routing system)
export const REST_API_URL = BASE_URL + 'public/api/';

// LEGACY: Old individual endpoint files (now in modules)
// Note: Legacy endpoints moved to modules - use REST API when possible
export const LEGACY_API_URL = BASE_URL + 'modules/';

// Default to REST API for backward compatibility with newer code
export const API_BASE_URL = REST_API_URL;

// Asset URLs
export const ASSETS_URL = BASE_URL + 'public/assets/';

// JavaScript module base URL
export const JS_BASE_URL = BASE_URL + 'public/assets/js/';