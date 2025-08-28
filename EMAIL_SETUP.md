# Email Configuration Guide for Listerino

## Overview
This guide covers setting up transactional emails for password resets, email verification, and other system notifications.

## Recommended Services (Choose One)

### Option 1: Postmark (Recommended for Production)
Best for transactional emails with excellent deliverability.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your-postmark-server-token
MAIL_PASSWORD=your-postmark-server-token
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="Listerino"
```

**Setup Steps:**
1. Sign up at https://postmarkapp.com
2. Create a server token
3. Verify your domain
4. Add the token to your .env file

### Option 2: SendGrid
Popular choice with good API and SMTP support.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="Listerino"
```

**Setup Steps:**
1. Sign up at https://sendgrid.com
2. Create an API key with "Mail Send" permissions
3. Verify your sender identity
4. Use "apikey" as username and your API key as password

### Option 3: Mailgun
Developer-friendly with good documentation.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-mailgun-smtp-username
MAIL_PASSWORD=your-mailgun-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="Listerino"
```

**Setup Steps:**
1. Sign up at https://mailgun.com
2. Add and verify your domain
3. Get SMTP credentials from domain settings
4. Add credentials to .env file

### Option 4: Gmail (For Testing Only)
Quick setup for development/testing. NOT recommended for production.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="Listerino"
```

**Setup Steps:**
1. Enable 2-factor authentication on your Gmail account
2. Generate an app-specific password: https://myaccount.google.com/apppasswords
3. Use your Gmail address as username
4. Use the app-specific password as password

### Option 5: Amazon SES
Cost-effective for high volume.

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@listerino.com"
MAIL_FROM_NAME="Listerino"
```

**Setup Steps:**
1. Set up AWS account and SES
2. Verify your domain in SES
3. Move out of sandbox mode for production
4. Install AWS SDK: `composer require aws/aws-sdk-php`

## Testing Email Configuration

### 1. Test SMTP Connection
```bash
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

### 2. Test Password Reset
1. Go to /forgot-password
2. Enter a registered email
3. Check if email is received

### 3. Test Email Verification
1. Register a new user
2. Check if verification email is sent

## Email Templates Location
- Password Reset: `resources/views/auth/reset-password.blade.php`
- Email Verification: Uses Laravel's default notification

## Custom Email Templates

To customize email templates, create notification classes:

```bash
php artisan make:notification CustomResetPassword
php artisan make:notification CustomVerifyEmail
```

## Troubleshooting

### Common Issues

1. **"Connection could not be established"**
   - Check firewall allows outbound connections on port 587
   - Verify credentials are correct
   - Try telnet to test connection: `telnet smtp.gmail.com 587`

2. **"Authentication failed"**
   - Double-check username and password
   - For Gmail, ensure using app-specific password
   - For SendGrid, username should be "apikey"

3. **Emails going to spam**
   - Set up SPF records for your domain
   - Configure DKIM signing
   - Use a reputable email service
   - Ensure FROM address matches your domain

4. **Rate limiting errors**
   - Implement queue for emails: `QUEUE_CONNECTION=database`
   - Add delays between bulk emails
   - Upgrade email service plan if needed

## Production Checklist

- [ ] Choose and configure email service
- [ ] Verify domain ownership
- [ ] Set up SPF/DKIM records
- [ ] Test all email types (password reset, verification)
- [ ] Configure email queues for better performance
- [ ] Set up email logging/monitoring
- [ ] Create custom email templates matching brand
- [ ] Test email deliverability with mail-tester.com

## Queue Configuration (Recommended)

For better performance, queue your emails:

```env
QUEUE_CONNECTION=database
```

Then run:
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

For production, use Supervisor to keep queue worker running:
```ini
[program:listerino-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/listerino/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/listerino/storage/logs/worker.log
```

---

## Quick Start (Using Gmail for Testing)

1. Update your local .env:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="Listerino"
```

2. Clear config cache:
```bash
php artisan config:clear
php artisan config:cache
```

3. Test email:
```bash
php artisan tinker
>>> Mail::raw('Test email from Listerino', function ($m) {
...     $m->to('your-email@gmail.com')->subject('Test Email');
... });
```

If successful, you'll see "null" returned and receive the test email.

---

Last Updated: December 2024