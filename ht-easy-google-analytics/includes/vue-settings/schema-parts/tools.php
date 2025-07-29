<?php
return ['tools_route' => [
    'title' => __('Tools', 'ht-easy-ga4'),
    'sections' => [],
    'fields' => [
        'enable_inspector' => [
            'id' => 'enable_inspector',
            'type' => 'switch',
            'title' => __('Enable GA4 Inspector', 'ht-easy-ga4'),
            'desc' => __('<br><strong>What it does:</strong><br>• Shows a debug panel on your website that displays which GA4 measurement IDs are present
            <br>• Checks if the Google Analytics gtag script is properly loaded
            <br>• Verifies if the dataLayer is present and working<br><br><strong>How to use:</strong>
            <br>1. Enable this feature using the toggle above
            <br>2. Visit your website and add <code>?htga4_inspector=1</code> to any page URL
            <br>3. A debug panel will appear showing your GA4 tracking status<br><br><strong>Quick test:</strong> Click the link below to see the inspector on your homepage:
            <br>• <a href="' . esc_url(home_url('/?htga4_inspector=1')) . '" target="_blank">Open Inspector</a>
            <br><br>
            <strong>💡 Tip:</strong> For best results, open the inspector in a private/incognito window to avoid browser extensions or cached data that might interfere with the test.
            <br><br><em>Note: This tool helps debug your GA4 implementation but cannot guarantee 100% accuracy.</em>', 'ht-easy-ga4'),
            'default' => false,
        ],
    ]
]];