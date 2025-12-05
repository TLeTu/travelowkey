<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$docroot = __DIR__ . '/../';

$path = $docroot . ltrim($uri, '/');

// Serve existing files directly
if (is_file($path)) {
  return false;
}

// If it’s a directory without trailing slash, redirect to add slash
if (is_dir($path) && substr($uri, -1) !== '/') {
  $target = $uri . '/';
  header("Location: $target", true, 301);
  exit;
}

// If it’s a directory with index.html, serve it
if (is_dir($path) && is_file($path . '/index.html')) {
  readfile($path . '/index.html');
  exit;
}

// Fallback: try index.html relative to the request
if (is_file($path . '/index.html')) {
  readfile($path . '/index.html');
  exit;
}

// 404
http_response_code(404);
echo "Not Found";