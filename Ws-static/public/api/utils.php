<?php
function json_response($success, $message, $extra = []) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
  exit;
}
function get_base_url($config) {
  if (!empty($config['site']['base_url'])) return rtrim($config['site']['base_url'], '/');
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host;
}
function safe_text($s, $max = 2000) {
  $s = trim((string)$s);
  $s = preg_replace("/\s+/", " ", $s);
  if (strlen($s) > $max) $s = substr($s, 0, $max);
  return $s;
}
function valid_email($email) {
  if ($email === '') return true;
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
function valid_phone($phone) {
  $digits = preg_replace('/\D/', '', $phone);
  $len = strlen($digits);
  return $len >= 10 && $len <= 13;
}
function ensure_dir($path) {
  if (!is_dir($path)) mkdir($path, 0775, true);
}
