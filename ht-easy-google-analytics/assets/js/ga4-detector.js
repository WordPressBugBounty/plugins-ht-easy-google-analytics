/**
 * GA4 Detection Utility
 * Detects if Google Analytics 4 is implemented and active on a website
 */

class GA4Detector {
    constructor() {
        this.detectionResults = {
            hasGA4: false,
            hasGtag: false,
            hasDataLayer: false,
            hasGtagScript: false,
            hasGtagConfig: false,
            trackingId: null,
            consentMode: false,
            details: {}
        };
    }

    /**
     * Main detection method
     * @returns {Object} Detection results
     */
    detect() {
        this.detectGtagFunction();
        this.detectDataLayer();
        this.detectGtagScript();
        this.detectGtagConfig();
        this.detectConsentMode();
        this.determineOverallStatus();
        
        return this.detectionResults;
    }

    /**
     * Check if gtag function exists
     */
    detectGtagFunction() {
        this.detectionResults.hasGtag = typeof window.gtag === 'function';
        this.detectionResults.details.gtagFunction = this.detectionResults.hasGtag;
    }

    /**
     * Check if dataLayer exists and has content
     */
    detectDataLayer() {
        this.detectionResults.hasDataLayer = Array.isArray(window.dataLayer) && window.dataLayer.length > 0;
        this.detectionResults.details.dataLayerLength = window.dataLayer ? window.dataLayer.length : 0;
    }

    /**
     * Check if gtag script is loaded
     */
    detectGtagScript() {
        const scripts = document.querySelectorAll('script[src*="googletagmanager.com/gtag/js"]');
        this.detectionResults.hasGtagScript = scripts.length > 0;
        this.detectionResults.details.gtagScripts = scripts.length;
    }

    /**
     * Check for gtag config calls and extract tracking ID
     */
    detectGtagConfig() {
        if (window.dataLayer && window.dataLayer.length > 0) {
            // Look for config events in dataLayer
            const configEvents = window.dataLayer.filter(event => 
                event[0] === 'config' || 
                (event[0] === 'js' && event[1] instanceof Date)
            );
            
            this.detectionResults.hasGtagConfig = configEvents.length > 0;
            
            // Extract tracking ID from config events
            const configEvent = configEvents.find(event => event[0] === 'config');
            if (configEvent && configEvent[1]) {
                this.detectionResults.trackingId = configEvent[1];
            }
            
            this.detectionResults.details.configEvents = configEvents.length;
        }
    }

    /**
     * Check if consent mode is implemented
     */
    detectConsentMode() {
        if (window.dataLayer && window.dataLayer.length > 0) {
            const consentEvents = window.dataLayer.filter(event => 
                event[0] === 'consent' && 
                event[1] === 'default'
            );
            
            this.detectionResults.consentMode = consentEvents.length > 0;
            this.detectionResults.details.consentEvents = consentEvents.length;
        }
    }

    /**
     * Determine overall GA4 status
     */
    determineOverallStatus() {
        // GA4 is considered active if we have gtag function AND either dataLayer content or gtag script
        this.detectionResults.hasGA4 = this.detectionResults.hasGtag && 
            (this.detectionResults.hasDataLayer || this.detectionResults.hasGtagScript);
    }

    /**
     * Get a simple boolean result
     * @returns {boolean}
     */
    isGA4Active() {
        return this.detectionResults.hasGA4;
    }

    /**
     * Get tracking ID if available
     * @returns {string|null}
     */
    getTrackingId() {
        return this.detectionResults.trackingId;
    }

    /**
     * Check if consent mode is enabled
     * @returns {boolean}
     */
    hasConsentMode() {
        return this.detectionResults.consentMode;
    }

    /**
     * Get detailed detection results
     * @returns {Object}
     */
    getDetailedResults() {
        return this.detectionResults;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GA4Detector;
} else if (typeof window !== 'undefined') {
    window.GA4Detector = GA4Detector;
} 