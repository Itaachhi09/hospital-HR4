/**
 * Shared Utilities for HR Management System
 */

// Import base URLs from config
import { API_BASE_URL, JS_BASE_URL } from './config.js';

// Re-export URLs for other modules
export { API_BASE_URL, JS_BASE_URL };

// Cache for loaded modules
// Each entry will store an object: { module, initializer }
const moduleCache = new Map();

// Cache for read-only mode detection
let __readOnlyCache = null;
let __readOnlyFetchedAt = 0;
const __READONLY_TTL_MS = 30 * 1000; // 30s cache

/**
 * Returns whether the backend is configured to run in read-only mode.
 * Caches the result briefly to avoid repeated network calls across modules.
 */
export async function isReadOnlyMode() {
    const now = Date.now();
    if (__readOnlyCache !== null && (now - __readOnlyFetchedAt) < __READONLY_TTL_MS) {
        return __readOnlyCache;
    }
    try {
        const url = `${API_BASE_URL.replace(/php\/api\/$/, 'api/')}hr-core/config`;
        const res = await fetch(url, { credentials: 'include' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const j = await res.json();
        const ro = !!(j && (j.data?.read_only ?? j.read_only));
        __readOnlyCache = ro;
        __readOnlyFetchedAt = now;
        return ro;
    } catch (err) {
        console.warn('[utils.isReadOnlyMode] failed to fetch, assuming false', err);
        __readOnlyCache = false;
        __readOnlyFetchedAt = now;
        return false;
    }
}

/**
 * Loads and initializes a module dynamically
 * @param {string} modulePath - The relative path to the module from js/
 * @param {HTMLElement} container - The container element to render the module in
 * @param {string} title - The title of the section
 * @returns {Promise<Object>} - The loaded module instance
 */
export async function loadModule(modulePath, container, title = '') {
    try {
        // First check if we already have this module in cache
        if (moduleCache.has(modulePath)) {
            const cached = moduleCache.get(modulePath);
            const initializer = cached.initializer;
            if (typeof initializer === 'function') {
                // Call cached initializer the same way we call freshly-imported initializers:
                // if it expects at least one argument, pass the container id string; otherwise call with no args.
                try {
                    if (initializer.length >= 1) {
                        await initializer(container.id || 'main-content-area');
                    } else {
                        await initializer();
                    }
                } catch (err) {
                    // If calling with id fails for any reason, attempt to call with the element itself as a fallback
                    try {
                        await initializer(container);
                    } catch (err2) {
                        // If it still fails, rethrow the original error to trigger the normal loadModule error handling
                        throw err;
                    }
                }
                return cached.module;
            }
            // If cache exists but initializer missing, continue to re-import
        }

        // If not in cache, load it
        const fullPath = `${JS_BASE_URL}${modulePath}`;
        // For employee-facing modules, append a cache-busting query param to avoid stale module code in browser cache.
        // We still cache the resolved module in moduleCache after a successful load so subsequent navigations in the same session are fast.
        let importPath = fullPath;
        // Cache-bust all feature modules to avoid stale UI after deployments
        importPath = `${fullPath}${fullPath.includes('?') ? '&' : '?'}_=${Date.now()}`;
        console.log(`Loading module from: ${importPath}`);

        const module = await import(importPath);


        // Resolve an initializer function from the module exports.
        // Support a broad set of common patterns so we don't need to update many module files:
        // - initialize, init, start
        // - default
        // - display*, render*, show*, load*, setup*
        let initializer = null;
        const exportKeys = Object.keys(module || {});
        const tryNames = ['initialize', 'init', 'start'];
        for (const name of tryNames) {
            if (module && typeof module[name] === 'function') { initializer = module[name]; break; }
        }
        if (!initializer && module && typeof module.default === 'function') {
            initializer = module.default;
        }
        if (!initializer && module) {
            // Try patterns
            const patterns = [/^display/i, /^render/i, /^show/i, /^load/i, /^setup/i, /^start/i];
            for (const key of exportKeys) {
                for (const pat of patterns) {
                    if (pat.test(key) && typeof module[key] === 'function') {
                        initializer = module[key];
                        break;
                    }
                }
                if (initializer) break;
            }
        }

        if (!initializer) {
            throw new Error(`Module ${modulePath} does not export an initializer (tried: initialize/init/start/default/display*/render*/show*/load*/setup*). Exports: ${JSON.stringify(exportKeys)}`);
        }

        // Cache the resolved module and its initializer for future use
        moduleCache.set(modulePath, { module, initializer });

        // Update page title if provided
        const pageTitleElement = document.getElementById('page-title');
        if (pageTitleElement && title) {
            pageTitleElement.textContent = title;
        }

        // Initialize the module with the container.
        // Many existing modules expect a container ID string (e.g., 'main-content-area'),
        // while others accept an HTMLElement. Try both: first call with container.id
        // when the initializer declares parameters; if that fails, retry with the HTMLElement.
        try {
            if (typeof initializer === 'function') {
                // If initializer expects arguments, prefer sending the container id (string)
                if (initializer.length >= 1) {
                    // Call with id first
                    try {
                        await initializer(container.id || 'main-content-area');
                    } catch (errId) {
                        // Retry with element
                        await initializer(container);
                    }
                } else {
                    // No args expected
                    await initializer();
                }
            }
            return module;
        } catch (err) {
            console.error(`Initializer for ${modulePath} threw an error:`, err);
            throw err;
        }
    } catch (error) {
        console.error(`Failed to load module ${modulePath}:`, error);
        if (container) {
            container.innerHTML = `
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">${title || 'Error'}</h3>
                    </div>
                    <div class="p-6">
                        <div class="text-center py-8">
                            <div class="text-6xl text-gray-300 mb-4">⚠️</div>
                            <h4 class="text-xl font-semibold text-gray-700 mb-2">Module Load Error</h4>
                            <p class="text-gray-600 mb-4">Failed to load the requested module. Please try refreshing the page.</p>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <p class="text-red-800 text-sm">
                                    <strong>Technical Details:</strong><br>
                                    ${error.message}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        throw error;
    }
}

/**
 * Fetches employees and populates a given select element.
 * @param {string} selectElementId - The ID of the select element to populate.
 * @param {boolean} [includeAllOption=false] - Whether to include an "All Employees" option.
 */
export async function populateEmployeeDropdown(selectElementId, includeAllOption = false) {
    const selectElement = document.getElementById(selectElementId);
    if (!selectElement) {
        console.error(`[populateEmployeeDropdown] Element with ID '${selectElementId}' NOT FOUND.`);
        return;
    }

    // Clear existing options except potential placeholder if needed later
    selectElement.innerHTML = ''; // Clear existing options first

    // Create and add the placeholder/default option
    let placeholderOption = document.createElement('option');
    if (includeAllOption) {
        placeholderOption.value = "";
        placeholderOption.textContent = "All Employees";
    } else {
        placeholderOption.value = "";
        placeholderOption.textContent = "-- Select Employee --";
        placeholderOption.disabled = true; // Disable selection of placeholder initially
        placeholderOption.selected = true;
    }
    selectElement.appendChild(placeholderOption);

    try {
        const apiUrl = `${API_BASE_URL}get_employees.php`;
        const response = await fetch(apiUrl);

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, Response: ${errorText.substring(0, 100)}...`);
        }

        const employees = await response.json();

        if (employees.error) {
            console.error(`[populateEmployeeDropdown] API error for '${selectElementId}':`, employees.error);
            placeholderOption.textContent = 'Error loading!';
            placeholderOption.disabled = true;
        } else if (!Array.isArray(employees)) {
             console.error(`[populateEmployeeDropdown] Invalid data format for '${selectElementId}'. Expected array.`);
             placeholderOption.textContent = 'Invalid data!';
             placeholderOption.disabled = true;
        } else if (employees.length === 0) {
            // If 'All Employees' is allowed, keep it, otherwise show 'No employees'
            if (!includeAllOption) {
                placeholderOption.textContent = 'No employees available';
                placeholderOption.disabled = true;
            } else {
                 // Keep "All Employees" selectable even if list is empty for filtering purposes
                 placeholderOption.disabled = false;
            }
        } else {
             // Enable placeholder if it was disabled and we have employees
             if (!includeAllOption) placeholderOption.disabled = false;

            // Populate with fetched employees
            employees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.EmployeeID;
                // Use textContent for security
                option.textContent = `${emp.FirstName} ${emp.LastName} (ID: ${emp.EmployeeID})`;
                selectElement.appendChild(option);
            });
        }
    } catch (error) {
        console.error(`[populateEmployeeDropdown] Failed for '${selectElementId}':`, error);
        // Reset to placeholder but indicate error
        selectElement.innerHTML = ''; // Clear again
        placeholderOption.textContent = 'Error loading!';
        placeholderOption.value = "";
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        selectElement.appendChild(placeholderOption);
    }
}


/**
 * Fetches shifts and populates a given select element.
 * @param {string} selectElementId - The ID of the select element to populate.
 */
export async function populateShiftDropdown(selectElementId) {
    const selectElement = document.getElementById(selectElementId);
    if (!selectElement) {
         console.error(`[populateShiftDropdown] Element with ID '${selectElementId}' NOT FOUND.`);
         return;
    }

    // Preserve the first option (likely a placeholder like "-- No Specific Shift --")
    const firstOptionHTML = selectElement.options[0] ? selectElement.options[0].outerHTML : '<option value="">-- Select Shift --</option>';
    selectElement.innerHTML = firstOptionHTML; // Keep only the first option initially

    try {
        const response = await fetch(`${API_BASE_URL}get_shifts.php`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const shifts = await response.json();

        if (shifts.error) {
            console.error("Error fetching shifts for dropdown:", shifts.error);
            // Optionally add an error option or modify the placeholder
            selectElement.options[0].textContent = "Error loading shifts";
            selectElement.options[0].disabled = true;
        } else if (shifts.length > 0) {
            shifts.forEach(shift => {
                const option = document.createElement('option');
                option.value = shift.ShiftID;
                // Format time nicely for display
                const startTime = shift.StartTimeFormatted || (shift.StartTime ? new Date(`1970-01-01T${shift.StartTime}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : 'N/A');
                const endTime = shift.EndTimeFormatted || (shift.EndTime ? new Date(`1970-01-01T${shift.EndTime}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : 'N/A');
                option.textContent = `${shift.ShiftName} (${startTime} - ${endTime})`;
                selectElement.appendChild(option);
            });
        } else {
             // No shifts found, maybe update placeholder text
             if (selectElement.options[0].value === "") { // Only if it's a generic placeholder
                 selectElement.options[0].textContent = "-- No shifts available --";
             }
        }
    } catch (error) {
        console.error('Error populating shift dropdown:', error);
        // Update placeholder to show error
        selectElement.options[0].textContent = "Error loading shifts";
        selectElement.options[0].disabled = true;
    }
}

// Add other shared utility functions here as needed (e.g., showNotification, formatDate)

