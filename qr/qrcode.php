<?php
function generateQRCode($data, $size = 300) {
    // Use Google Chart API as fallback (or could use a local PHP QR library)
    // For production, use a local library
    $encoded = urlencode($data);
    return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded}&choe=UTF-8";
}

function generateQRCodeDataURI($data, $size = 300) {
    $url = generateQRCode($data, $size);
    // Return the URL instead of trying to fetch the image
    return $url;
}
?>