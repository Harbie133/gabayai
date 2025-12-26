<?php
// captcha.php: creates a CAPTCHA image and stores the code in the session
session_start();

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: image/png');

$width = 140;
$height = 44;
$img = imagecreatetruecolor($width, $height);

// Colors
$bg  = imagecolorallocate($img, 245, 247, 252);
$fg  = imagecolorallocate($img,  32,  32,  32);
$ln1 = imagecolorallocate($img, 200, 210, 235);
$ln2 = imagecolorallocate($img, 180, 190, 220);

imagefill($img, 0, 0, $bg);

// Generate 6-char alphanumeric code
$pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 6; $i++) {
  $code .= $pool[random_int(0, strlen($pool)-1)];
}
$_SESSION['captcha_code'] = $code;

// Noise lines
for ($i = 0; $i < 4; $i++) {
  imageline($img, random_int(0,$width), random_int(0,$height), random_int(0,$width), random_int(0,$height), ($i%2==0 ? $ln1 : $ln2));
}

// Text (imagestring avoids font dependencies)
$charSpace = $width / 7;
for ($i = 0; $i < strlen($code); $i++) {
  $x = 12 + $i * $charSpace + random_int(-2, 2);
  $y = random_int(8, 16);
  imagestring($img, random_int(4,5), $x, $y, $code[$i], $fg);
}

imagepng($img);
imagedestroy($img);
