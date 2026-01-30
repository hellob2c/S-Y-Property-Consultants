<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';

$file = basename($_GET['file'] ?? '');
$token = $_GET['token'] ?? '';

if ($file === '' || $token === '') {
  http_response_code(400);
  echo 'Bad request.';
  exit;
}

$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false) {
  http_response_code(500);
  echo 'Server misconfiguration.';
  exit;
}

$mapFile = $dataDir . DIRECTORY_SEPARATOR . 'uploads.json';
if (!file_exists($mapFile)) {
  http_response_code(404);
  echo 'Not found.';
  exit;
}

$map = json_decode(file_get_contents($mapFile), true);
if (!is_array($map) || !isset($map[$file])) {
  http_response_code(404);
  echo 'Not found.';
  exit;
}

if (!hash_equals((string)$map[$file]['token'], (string)$token)) {
  http_response_code(403);
  echo 'Forbidden.';
  exit;
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$path = $uploadsDir . DIRECTORY_SEPARATOR . $file;
if (!file_exists($path)) {
  http_response_code(404);
  echo 'Not found.';
  exit;
}

$original = $map[$file]['original'] ?? $file;
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
$mimeMap = [
  'pdf' => 'application/pdf',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'doc' => 'application/msword',
  'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
if (isset($mimeMap[$ext])) $mime = $mimeMap[$ext];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $original) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
