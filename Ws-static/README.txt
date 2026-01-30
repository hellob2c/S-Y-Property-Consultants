S&Y Property Consultants — Interactive Microsite (HTML + Tailwind + JS + PHP)

WHAT YOU GET
- Single-page interactive microsite (index.html) similar to the provided reference
- Smooth navigation + scrollspy + reveal animations
- Service details modal + Quick Enquiry modal (with multi-file upload)
- Contact form with upload + AJAX submission
- PHP endpoint (api/submit.php) for saving uploads and sending email using mail()

IMPORTANT (EMAIL SENDING)
1) Open: api/submit.php
2) Set: $config['admin_email'] to your email address
3) Upload the entire folder to your hosting (Hostinger / shared hosting works)

If your hosting does NOT allow PHP mail(), you have 2 options:
A) Configure SMTP with PHPMailer
   - In api/ folder run: composer require phpmailer/phpmailer
   - Upload the generated vendor/ folder to api/vendor/
   - Then update submit.php to use PHPMailer (or ask me and I’ll update the zip for you)
B) Use your hosting email service SMTP and PHPMailer (recommended)

LOCAL TEST
- Use any local PHP server:
  php -S localhost:8080 -t .
- Then open:
  http://localhost:8080

IMAGE SOURCES (FREE UNDER UNSPLASH LICENSE)
- Hero image: https://unsplash.com/photos/sXPsWMPCuXk
- Team 1: https://unsplash.com/photos/bbOOTiq-EPA
- Team 2: https://unsplash.com/photos/iFBwCKkF7Ns
