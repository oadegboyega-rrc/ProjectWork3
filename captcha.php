<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate a random CAPTCHA code
$captcha_code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
$_SESSION['captcha'] = $captcha_code;

// Create an image
$image = imagecreatetruecolor(150, 50);

// Set colors
$background_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
$line_color = imagecolorallocate($image, 64, 64, 64);

// Fill the background
imagefilledrectangle($image, 0, 0, 150, 50, $background_color);

// Add random lines for obfuscation
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, 150), rand(0, 50), rand(0, 150), rand(0, 50), $line_color);
}

// Add the CAPTCHA text
imagettftext($image, 20, rand(-10, 10), rand(10, 50), rand(30, 40), $text_color, __DIR__ . '/fonts/arial.ttf', $captcha_code);

// Output the image
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);
?>