<?php
return [
  'site' => [
    'name' => 'S&Y Property Consultants',
    'base_url' => '', // e.g. https://yourdomain.com (leave blank to auto-detect)
  ],
  'admin' => [
    'to_email' => 'enquiry@yourdomain.com',  // <-- change
    'to_name'  => 'S&Y Property Consultants',
  ],
  'smtp' => [
    'enabled'  => true,
    'host'     => 'smtp.yourdomain.com', // <-- change
    'port'     => 587,                   // 587 (TLS) or 465 (SSL)
    'username' => 'enquiry@yourdomain.com', // <-- change
    'password' => 'YOUR_EMAIL_PASSWORD',    // <-- change
    'encryption' => 'tls', // 'tls' or 'ssl'
    'from_email' => 'enquiry@yourdomain.com', // <-- change
    'from_name'  => 'S&Y Property Consultants',
  ],
  'uploads' => [
    'max_file_mb' => 10,
    'allowed_ext' => ['pdf','jpg','jpeg','png','doc','docx'],
  ],
  'security' => [
    'honeypot_field' => 'company_website',
    'rate_limit_max' => 5,
    'rate_limit_window_seconds' => 600,
  ],
];
