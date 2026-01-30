<?php
session_start();

$config = require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';

$honeypot = $config['security']['honeypot_field'] ?? 'company_website';
if (!empty($_POST[$honeypot] ?? '')) {
  json_response(false, 'Invalid submission.');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$window = (int)($config['security']['rate_limit_window_seconds'] ?? 600);
$max = (int)($config['security']['rate_limit_max'] ?? 5);

if (!isset($_SESSION['rate'])) $_SESSION['rate'] = [];
if (!isset($_SESSION['rate'][$ip])) $_SESSION['rate'][$ip] = [];
$_SESSION['rate'][$ip] = array_values(array_filter($_SESSION['rate'][$ip], function($t) use ($window) {
  return ($t + $window) > time();
}));
if (count($_SESSION['rate'][$ip]) >= $max) {
  json_response(false, 'Too many requests. Please try again in a few minutes.');
}
$_SESSION['rate'][$ip][] = time();

$full_name = safe_text($_POST['full_name'] ?? '', 120);
$phone = safe_text($_POST['phone'] ?? '', 30);
$email = safe_text($_POST['email'] ?? '', 120);
$service = safe_text($_POST['service'] ?? '', 80);
$callback_time = safe_text($_POST['callback_time'] ?? '', 40);
$message = trim((string)($_POST['message'] ?? ''));

if ($full_name === '' || !valid_phone($phone) || $service === '' || trim($message) === '') {
  json_response(false, 'Please fill all required fields correctly.');
}
if (!valid_email($email)) {
  json_response(false, 'Please enter a valid email.');
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
if ($uploadsDir === false) {
  ensure_dir(__DIR__ . '/../uploads');
  $uploadsDir = realpath(__DIR__ . '/../uploads');
}
$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false) {
  ensure_dir(__DIR__ . '/../data');
  $dataDir = realpath(__DIR__ . '/../data');
}

$maxMb = (int)($config['uploads']['max_file_mb'] ?? 10);
$maxBytes = $maxMb * 1024 * 1024;
$allowed = $config['uploads']['allowed_ext'] ?? ['pdf','jpg','jpeg','png','doc','docx'];

$saved = [];
if (!empty($_FILES['documents']) && isset($_FILES['documents']['name']) && is_array($_FILES['documents']['name'])) {
  $count = count($_FILES['documents']['name']);
  for ($i=0; $i<$count; $i++) {
    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
    if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
      json_response(false, 'File upload error. Please try again.');
    }
    $origName = $_FILES['documents']['name'][$i];
    $size = (int)$_FILES['documents']['size'][$i];
    $tmp = $_FILES['documents']['tmp_name'][$i];

    if ($size > $maxBytes) {
      json_response(false, 'One or more files exceed the maximum size (' . $maxMb . 'MB).');
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
      json_response(false, 'Invalid file type detected.');
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $uploadsDir . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($tmp, $dest)) {
      json_response(false, 'Could not save uploaded file.');
    }

    $token = bin2hex(random_bytes(16));
    $saved[] = [
      'original' => $origName,
      'stored' => $newName,
      'token' => $token,
      'size' => $size,
      'uploaded_at' => date('c'),
    ];
  }
}

$mapFile = $dataDir . DIRECTORY_SEPARATOR . 'uploads.json';
$map = [];
if (file_exists($mapFile)) {
  $raw = file_get_contents($mapFile);
  $map = json_decode($raw, true);
  if (!is_array($map)) $map = [];
}
foreach ($saved as $item) {
  $map[$item['stored']] = [
    'token' => $item['token'],
    'original' => $item['original'],
    'uploaded_at' => $item['uploaded_at'],
  ];
}
file_put_contents($mapFile, json_encode($map, JSON_PRETTY_PRINT));

$baseUrl = get_base_url($config);
$downloadLinks = [];
foreach ($saved as $item) {
  $downloadLinks[] = $baseUrl . '/api/download.php?file=' . urlencode($item['stored']) . '&token=' . urlencode($item['token']);
}

$subject = 'New Enquiry — ' . ($config['site']['name'] ?? 'Website');
$body = "New enquiry received:\n\n";
$body .= "Name: {$full_name}\n";
$body .= "Phone: {$phone}\n";
$body .= "Email: " . ($email ?: '-') . "\n";
$body .= "Service: {$service}\n";
$body .= "Preferred Time: " . ($callback_time ?: '-') . "\n";
$body .= "IP: {$ip}\n";
$body .= "\nMessage:\n{$message}\n\n";

if (!empty($downloadLinks)) {
  $body .= "Uploaded Documents (secure links):\n";
  foreach ($downloadLinks as $l) $body .= "- {$l}\n";
  $body .= "\n";
} else {
  $body .= "No documents uploaded.\n\n";
}

$sent = false;
$error = '';

$adminTo = $config['admin']['to_email'] ?? '';

$smtpEnabled = (bool)($config['smtp']['enabled'] ?? false);
$vendorAutoload = __DIR__ . '/vendor/autoload.php';

if ($smtpEnabled && file_exists($vendorAutoload)) {
  require $vendorAutoload;
  try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->Port = (int)$config['smtp']['port'];
    $enc = strtolower($config['smtp']['encryption'] ?? 'tls');
    if ($enc === 'ssl') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    else $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($adminTo);
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $mail->addReplyTo($email, $full_name);
    }
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
    $sent = true;
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

if (!$sent) {
  $headers = [];
  $from = $config['smtp']['from_email'] ?? 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
  $headers[] = 'From: ' . $from;
  if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $headers[] = 'Reply-To: ' . $email;
  }
  $headers[] = 'Content-Type: text/plain; charset=UTF-8';

  $sent = @mail($adminTo, $subject, $body, implode("\r\n", $headers));
  if (!$sent && $error === '') $error = 'Mail sending failed.';
}

if (!$sent) {
  json_response(false, 'Submission saved but email could not be sent. Please contact by phone.', ['debug' => $error]);
}

json_response(true, 'Thank you! Your enquiry has been submitted. We will contact you shortly.');
