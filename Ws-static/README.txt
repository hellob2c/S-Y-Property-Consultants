S&Y Property Consultants — Starter Website (HTML + Tailwind + PHP Enquiry + Upload + Email)

SETUP
1) Upload the contents of /public to your hosting public_html folder.
2) Edit: public/api/config.php (admin email + SMTP details)
3) Ensure folders writable: public/uploads, public/data

SMTP EMAIL (Recommended)
Install PHPMailer (best via Composer):
  - On your local machine:
      cd public/api
      composer require phpmailer/phpmailer
  - Upload the generated vendor/ folder into public/api/vendor on your hosting.

NOTES
- Files are not attached to the email (avoids size/spam issues).
  Instead, you receive secure download links in email.
- uploads/ is protected by .htaccess. Files can be downloaded only via api/download.php with token.
