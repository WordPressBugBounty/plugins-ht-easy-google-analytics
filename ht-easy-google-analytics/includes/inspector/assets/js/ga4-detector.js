/**
 * GA4 Inspector JavaScript
 * Enhanced detection and inspection of GA4 implementation
 */

/**
 * Check if GA4 is implemented and active
 * @returns {boolean}
 */
function isGA4Active() {
    // Check if gtag function exists
    if (typeof window.gtag !== 'function') {
        return false;
    }
    
    // Check if dataLayer exists and has content
    if (!window.dataLayer || !Array.isArray(window.dataLayer) || window.dataLayer.length === 0) {
        return false;
    }
    
    // Check for GA4 config events
    const hasConfig = window.dataLayer.some(event => 
        event[0] === 'config' || 
        (event[0] === 'js' && event[1] instanceof Date)
    );
    
    return hasConfig;
}

/**
 * Get GA4 tracking ID if available
 * @returns {string|null}
 */
function getGA4TrackingId() {
    if (!window.dataLayer || !Array.isArray(window.dataLayer)) {
        return null;
    }
    
    const configEvent = window.dataLayer.find(event => event[0] === 'config');
    return configEvent ? configEvent[1] : null;
}

/**
 * Get all GA4 tracking IDs from dataLayer and script tags
 * @returns {Array}
 */
function getAllGA4TrackingIds() {
    const trackingIds = [];
    
    // Check dataLayer for config events
    if (window.dataLayer && Array.isArray(window.dataLayer)) {
        window.dataLayer.forEach(event => {
            if (event[0] === 'config' && event[1] && typeof event[1] === 'string' && event[1].startsWith('G-')) {
                if (!trackingIds.includes(event[1])) {
                    trackingIds.push(event[1]);
                }
            }
        });
    }
    
    // Check script tags for tracking IDs
    const scripts = document.querySelectorAll('script');
    scripts.forEach(script => {
        if (script.textContent) {
            const matches = script.textContent.match(/G-[A-Z0-9]{10}/g);
            if (matches) {
                matches.forEach(match => {
                    if (!trackingIds.includes(match)) {
                        trackingIds.push(match);
                    }
                });
            }
        }
    });
    
    return trackingIds;
}

/**
 * Get comprehensive GA4 status
 * @returns {Object}
 */
function getGA4Status() {
    const trackingIds = getAllGA4TrackingIds();
    
    return {
        isActive: isGA4Active(),
        trackingId: getGA4TrackingId(),
        trackingIds: trackingIds,
        trackingCount: trackingIds.length,
        hasGtag: typeof window.gtag === 'function',
        hasDataLayer: Array.isArray(window.dataLayer) && window.dataLayer.length > 0,
        dataLayerLength: window.dataLayer ? window.dataLayer.length : 0,
        hasGtagScript: document.querySelectorAll('script[src*="googletagmanager.com/gtag/js"]').length > 0
    };
}

if (typeof window !== 'undefined') {
    window.GA4Detection = {
        isGA4Active,
        getGA4TrackingId,
        getAllGA4TrackingIds,
        getGA4Status
    };
} 