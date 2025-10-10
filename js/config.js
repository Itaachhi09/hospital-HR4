/**
 * URL Configuration for the HR Management System
 * Version: 3.0 - Dual API Support (REST + Legacy)
 */

// Base URL for the entire application (update this based on your setup)
export const BASE_URL = window.location.origin + '/hospital-HR4/';

// NEW: REST API (Modern routing system)
export const REST_API_URL = BASE_URL + 'api/';

// LEGACY: Old individual endpoint files
export const LEGACY_API_URL = BASE_URL + 'php/api/';

// Default to REST API for backward compatibility with newer code
export const API_BASE_URL = REST_API_URL;

// Asset URLs
export const ASSETS_URL = BASE_URL + 'assets/';

// JavaScript module base URL
export const JS_BASE_URL = BASE_URL + 'js/';