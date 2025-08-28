# SendGrid Email Setup for Listerino

## Prerequisites Completed ✅
- Domain authentication (listerino.com) - DONE
- SendGrid account created - DONE

## Step 1: Create SendGrid API Key

1. Log in to SendGrid Dashboard
2. Go to **Settings** → **API Keys**
3. Click **Create API Key**
4. Name it: `Listerino Production` (or `Listerino Local` for testing)
5. Select **Full Access** or **Restricted Access** with these permissions:
   - Mail Send (Required)
   - Template Engine (Optional, for custom templates)
   - Tracking (Optional, for analytics)
6. Copy the API key immediately (it won't be shown again)

## Step 2: Configure Environment Variables

### Local Development (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=donotreply@listerino.com
MAIL_FROM_NAME="Listerino"
```

### Production (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=donotreply@listerino.com
MAIL_FROM_NAME="Listerino"

# Optional: For better tracking
SENDGRID_API_KEY=YOUR_SENDGRID_API_KEY_HERE
```

**Important Notes:**
- Username is literally the word "apikey" (not your email)
- Password is your actual SendGrid API key
- No need for actual email accounts at listerino.com
- `donotreply@listerino.com` is perfect for transactional emails

## Step 3: Update Production Server

```bash
# SSH to server
ssh root@137.184.113.161

# Edit production .env
cd /var/www/listerino
nano .env

# Update the MAIL_* variables as shown above
# Save and exit (Ctrl+X, Y, Enter)

# Clear cache and restart
docker exec -w /app listerino_app_manual php artisan config:cache
docker exec -w /app listerino_app_manual php artisan optimize
```

## Step 4: Test Email Sending

### Quick Test (Local)
```bash
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email from Listerino via SendGrid', function ($message) {
    $message->to('your-personal-email@gmail.com')
            ->subject('SendGrid Test - Listerino');
});
```

If successful, you'll see `null` returned.

### Test Password Reset Flow
1. Go to http://localhost:5173/forgot-password (or https://listerino.com/forgot-password)
2. Enter a valid user email
3. Check if the reset email arrives
4. The email should be from `donotreply@listerino.com`

## Step 5: Monitor Email Delivery

1. Log in to SendGrid Dashboard
2. Go to **Activity** → **Activity Feed**
3. You can see:
   - Delivered emails
   - Bounces
   - Blocks
   - Spam reports

## Common Issues & Solutions

### "Authentication failed"
- Verify the API key is correct
- Make sure username is exactly "apikey"
- Check API key has Mail Send permission

### "550 The from address does not match a verified Sender Identity"
- This shouldn't happen since your domain is authenticated
- If it does, add a Single Sender Verification for donotreply@listerino.com

### Emails going to spam
- Domain authentication is already done ✅
- Consider adding:
  - DMARC record to DNS
  - Custom unsubscribe links for marketing emails (not needed for transactional)

## Email Templates

The system currently uses Laravel's default email templates. To customize:

### For Password Reset Emails
1. Create custom notification:
```bash
php artisan make:notification CustomResetPasswordNotification
```

2. Override in User model:
```php
public function sendPasswordResetNotification($token)
{
    $this->notify(new CustomResetPasswordNotification($token));
}
```

### Email Footer Customization
Edit `resources/views/vendor/mail/html/footer.blade.php`:
```blade
@component('mail::footer')
© {{ date('Y') }} Listerino. All rights reserved.
@endcomponent
```

## Production Checklist

- [x] Domain authenticated with SendGrid
- [ ] API key created with proper permissions
- [ ] Production .env updated with SendGrid credentials
- [ ] Test email sent successfully
- [ ] Password reset tested
- [ ] Activity monitoring confirmed in SendGrid dashboard

## Email Types Configured

1. **Password Reset** ✅
   - Endpoint: `/api/forgot-password`
   - From: donotreply@listerino.com
   
2. **Email Verification** (Ready when needed)
   - Can be enabled in User model
   - From: donotreply@listerino.com

3. **Future Emails** (Can be added)
   - Welcome emails
   - List shared notifications
   - Comment notifications
   - Weekly digests

## Rate Limits

SendGrid Free Plan:
- 100 emails/day forever free

SendGrid Essentials (if needed):
- Starts at $19.95/month
- 50,000 emails/month

Current usage should be well within free tier for testing and initial launch.

---

Last Updated: December 2024
Status: Ready for configuration