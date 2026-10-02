<?php
// Function to detect if the user agent is a mobile device
function isMobile()
{
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// Check if we should serve the mobile or desktop version
if (isMobile()) {
    // Serve the mobile version (formerly v2/index.html)
    include 'mobile.html';
}
else {
    // Serve the desktop version (formerly index.html.bak)
    include 'desktop.html';
}
?>