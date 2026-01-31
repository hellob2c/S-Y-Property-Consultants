<?php
// api/submit.php
// Handles mini form, enquiry form and contact form (multipart with optional docs[]).
// Configure email targets in config below.

header('Content-Type: application/json; charset=utf-8');

$config = [
  'site_name' => 'S&Y Property Consultants',
  'admin_email' => 'info@sypropertyconsultants.in', // CHANGE THIS
  // If your hosting supports mail(), this works without SMTP.
  // If you want SMTP, see README.txt for PHPMailer setup.
  'max_total_upload_bytes' => 10 * 1024 * 1024,
  'allowed_ext' => ['pdf','jpg','jpeg','png','doc','docx'],
  'uploads_dir' => __DIR__ . '/../uploads',
  'data_file' => __DIR__ . '/../data/uploads.json',
];

function respond($ok, $message, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

function clean($v) {
  $v = trim((string)$v);
  $v = str_replace(["\r","\n"], " ", $v);
  return $v;
}

$name = clean($_POST['name'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$email = clean($_POST['email'] ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

if ($name === '' || $phone === '') {
  respond(false, 'Name and phone are required.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Please enter a valid email.');
}
if ($message === '' && $service === '') {
  // mini form may not include message; allow but keep a default
  $message = 'Callback requested via website.';
}

if (!is_dir($config['uploads_dir'])) { @mkdir($config['uploads_dir'], 0755, true); }
if (!is_file($config['data_file'])) { @file_put_contents($config['data_file'], '{}'); }

$uploaded = [];
$total = 0;

if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
  $count = count($_FILES['docs']['name']);
  for ($i=0; $i<$count; $i++) {
    $err = $_FILES['docs']['error'][$i];
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) respond(false, 'File upload error.');

    $tmp = $_FILES['docs']['tmp_name'][$i];
    $orig = basename($_FILES['docs']['name'][$i]);
    $size = (int)($_FILES['docs']['size'][$i] ?? 0);

    $total += $size;
    if ($total > $config['max_total_upload_bytes']) {
      respond(false, 'Total attachment size must be under 10MB.');
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_ext'], true)) {
      respond(false, 'Unsupported file type: ' . $ext);
    }

    $token = bin2hex(random_bytes(16));
    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $orig);
    $dest = $config['uploads_dir'] . '/' . $token . '_' . $safeName;

    if (!move_uploaded_file($tmp, $dest)) {
      respond(false, 'Failed to save uploaded file.');
    }

    $uploaded[] = [
      'token' => $token,
      'file' => basename($dest),
      'name' => $orig,
      'size' => $size,
    ];
  }
}

// Store download map
if (!empty($uploaded)) {
  $map = json_decode(@file_get_contents($config['data_file']), true);
  if (!is_array($map)) $map = [];
  foreach ($uploaded as $u) {
    $map[$u['token']] = $u['file'];
  }
  @file_put_contents($config['data_file'], json_encode($map));
}

$subject = $config['site_name'] . ' — New Enquiry';
$bodyLines = [];
$bodyLines[] = "New enquiry received:";
$bodyLines[] = "Name: $name";
$bodyLines[] = "Phone: $phone";
if ($email !== '') $bodyLines[] = "Email: $email";
if ($service !== '') $bodyLines[] = "Service: $service";
$bodyLines[] = "Message: $message";
$bodyLines[] = "";
if (!empty($uploaded)) {
  $bodyLines[] = "Uploaded documents (download links):";
  foreach ($uploaded as $u) {
    // download.php expects token, returns file if exists
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $url = $proto . '://' . $host . $base . '/download.php?token=' . urlencode($u['token']);
    $bodyLines[] = "- {$u['name']} ({$u['size']} bytes): $url";
  }
}

$body = implode("\n", $bodyLines);

// Try mail() first (works on many shared hostings). If it fails, still respond OK but warn.
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=utf-8';
$headers[] = 'From: ' . $config['site_name'] . ' <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>';
if ($email !== '') $headers[] = 'Reply-To: ' . $email;

$sent = @mail($config['admin_email'], $subject, $body, implode("\r\n", $headers));

if (!$sent) {
  // Many hosts block mail(); still keep the submission stored.
  respond(true, 'Saved, but email could not be sent. Configure SMTP / PHPMailer.', ['email_sent' => false]);
}

respond(true, 'Sent successfully.', ['email_sent' => true]);
<?php
// api/submit.php
// Handles mini form, enquiry form and contact form (multipart with optional docs[]).
// Configure email targets in config below.

header('Content-Type: application/json; charset=utf-8');

$config = [
  'site_name' => 'S&Y Property Consultants',
  'admin_email' => 'info@sypropertyconsultants.in', // CHANGE THIS

  // OPTIONAL: set a real from email on your domain to improve deliverability
  // Example: 'no-reply@sypropertyconsultants.in'
  'from_email' => '',

  // If your hosting supports mail(), this works without SMTP.
  // If you want SMTP, see README.txt for PHPMailer setup.
  'max_total_upload_bytes' => 10 * 1024 * 1024,
  'allowed_ext' => ['pdf','jpg','jpeg','png','doc','docx'],
  'uploads_dir' => __DIR__ . '/../uploads',
  'data_file' => __DIR__ . '/../data/uploads.json',
];

function respond($ok, $message, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Invalid request method. Use POST.');
}

function clean($v) {
  $v = trim((string)$v);
  $v = str_replace(["\r","\n"], " ", $v);
  return $v;
}

$name    = clean($_POST['name'] ?? '');
$phone   = clean($_POST['phone'] ?? '');
$email   = clean($_POST['email'] ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

if ($name === '' || $phone === '') {
  respond(false, 'Name and phone are required.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Please enter a valid email.');
}
if ($message === '' && $service === '') {
  // mini form may not include message; allow but keep a default
  $message = 'Callback requested via website.';
}

// Ensure upload + data folders exist
$uploadsDir = $config['uploads_dir'];
$dataFile   = $config['data_file'];
$dataDir    = dirname($dataFile);

if (!is_dir($uploadsDir)) {
  if (!@mkdir($uploadsDir, 0755, true)) {
    respond(false, 'Server error: uploads directory could not be created.');
  }
}

if (!is_dir($dataDir)) {
  if (!@mkdir($dataDir, 0755, true)) {
    respond(false, 'Server error: data directory could not be created.');
  }
}

if (!is_file($dataFile)) {
  if (@file_put_contents($dataFile, '{}') === false) {
    respond(false, 'Server error: data file could not be created.');
  }
}

$uploaded = [];
$total = 0;

if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
  $count = count($_FILES['docs']['name']);
  for ($i = 0; $i < $count; $i++) {
    $err = $_FILES['docs']['error'][$i];

    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) respond(false, 'File upload error.');

    $tmp  = $_FILES['docs']['tmp_name'][$i];
    $orig = basename($_FILES['docs']['name'][$i]);
    $size = (int)($_FILES['docs']['size'][$i] ?? 0);

    $total += $size;
    if ($total > $config['max_total_upload_bytes']) {
      respond(false, 'Total attachment size must be under 10MB.');
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_ext'], true)) {
      respond(false, 'Unsupported file type: ' . $ext);
    }

    $token = bin2hex(random_bytes(16));
    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $orig);
    $dest = $uploadsDir . '/' . $token . '_' . $safeName;

    if (!move_uploaded_file($tmp, $dest)) {
      respond(false, 'Failed to save uploaded file.');
    }

    $uploaded[] = [
      'token' => $token,
      'file'  => basename($dest),
      'name'  => $orig,
      'size'  => $size,
    ];
  }
}

// Store download map
if (!empty($uploaded)) {
  $map = json_decode(@file_get_contents($dataFile), true);
  if (!is_array($map)) $map = [];

  foreach ($uploaded as $u) {
    $map[$u['token']] = $u['file'];
  }

  @file_put_contents($dataFile, json_encode($map));
}

// Build email
$subject = $config['site_name'] . ' — New Enquiry';
$bodyLines = [];
$bodyLines[] = "New enquiry received:";
$bodyLines[] = "Name: $name";
$bodyLines[] = "Phone: $phone";
if ($email !== '')   $bodyLines[] = "Email: $email";
if ($service !== '') $bodyLines[] = "Service: $service";
$bodyLines[] = "Message: $message";
$bodyLines[] = "";

if (!empty($uploaded)) {
  $bodyLines[] = "Uploaded documents (download links):";

  $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host  = $_SERVER['HTTP_HOST'] ?? '';
  $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // typically /api
  // download.php is in the same /api folder
  $downloadBase = $proto . '://' . $host . $scriptDir . '/download.php?token=';

  foreach ($uploaded as $u) {
    $url = $downloadBase . urlencode($u['token']);
    $bodyLines[] = "- {$u['name']} ({$u['size']} bytes): $url";
  }
}

$body = implode("\n", $bodyLines);

// Headers
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=utf-8';

$hostForFrom = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fromEmail = $config['from_email'] !== '' ? $config['from_email'] : ('no-reply@' . preg_replace('/^www\./', '', $hostForFrom));
$headers[] = 'From: ' . $config['site_name'] . ' <' . $fromEmail . '>';

if ($email !== '') $headers[] = 'Reply-To: ' . $email;

// Try mail()
$sent = @mail($config['admin_email'], $subject, $body, implode("\r\n", $headers));

if (!$sent) {
  respond(true, 'Saved, but email could not be sent. Configure SMTP / PHPMailer.', ['email_sent' => false]);
}

respond(true, 'Sent successfully.', ['email_sent' => true]);
