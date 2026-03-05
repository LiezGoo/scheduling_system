# Email Notification Implementation - Registration Approval/Rejection

## ✅ Implementation Summary

### Overview
Automated email notifications are now sent when admin approves or rejects user registrations. Emails are queued for optimal performance and include relevant information for users.

---

## 📧 Email Types

### 1. Approval Email

**Subject:** "Your SorSU Scheduling System Account Has Been Approved"

**Sent When:** Admin clicks "Approve" on a pending registration

**Content Includes:**
- Personalized greeting with user's first name
- Approval confirmation message
- Dynamic login link (using APP_URL environment variable)
- Security notice for unrecognized account requests
- Professional signature

**Login URL Format:**
```
{{ config('app.url') }}/login
```
Example: `https://yourdomain.com/login`

---

### 2. Rejection Email

**Subject:** "Your SorSU Scheduling System Registration Update"

**Sent When:** Admin clicks "Reject" on a pending registration

**Content Includes:**
- Personalized greeting with user's first name
- Polite decline notification
- Admin-provided rejection reason (sanitized)
- Contact suggestion for disputes
- Professional signature

**Rejection Reason:** Admin must provide a reason (max 500 characters)

---

## 🔧 Technical Implementation

### Database Fields

**users table includes:**
- `approval_status` - enum: pending, approved, rejected
- `approved_by` - foreign key to users table (nullable)
- `approved_at` - timestamp (nullable)
- `rejected_at` - timestamp (nullable)
- `rejection_reason` - text (nullable, max 500 chars)

### Backend Architecture

#### Controller: `UserApprovalController`
**Location:** `app/Http/Controllers/Admin/UserApprovalController.php`

**Approval Method:**
```php
public function approve(User $user, Request $request)
{
    $user->approve(auth()->user());
    $user->notify(new UserApprovedNotification(auth()->user()));
    Mail::to($user->email)->send(new AccountApprovedMail($user));
    return back()->with('success', "...");
}
```

**Rejection Method:**
```php
public function reject(User $user, Request $request)
{
    $request->validate([
        'rejection_reason' => 'required|string|max:500',
    ]);
    $user->reject($request->rejection_reason);
    $user->notify(new UserRejectedNotification($request->rejection_reason));
    Mail::to($user->email)->send(new AccountRejectedMail($user, $request->rejection_reason));
    return back()->with('success', "...");
}
```

#### User Model Methods

**Approve Method:**
```php
public function approve(User $admin): bool
{
    return $this->update([
        'is_approved' => true,
        'approval_status' => self::APPROVAL_APPROVED,
        'approved_at' => now(),
        'approved_by' => $admin->id,
        'rejected_at' => null,
        'rejection_reason' => null,
    ]);
}
```

**Reject Method:**
```php
public function reject(?string $reason = null): bool
{
    return $this->update([
        'is_approved' => false,
        'approval_status' => self::APPROVAL_REJECTED,
        'approved_at' => null,
        'approved_by' => null,
        'rejected_at' => now(),
        'rejection_reason' => $reason,
    ]);
}
```

#### Mailable Classes

**AccountApprovedMail:**
- Location: `app/Mail/AccountApprovedMail.php`
- Implements: `ShouldQueue` for asynchronous sending
- View: `resources/views/emails/account-approved.blade.php`

**AccountRejectedMail:**
- Location: `app/Mail/AccountRejectedMail.php`
- Implements: `ShouldQueue` for asynchronous sending
- View: `resources/views/emails/account-rejected.blade.php`

---

## 🔒 Security Features

### ✅ Backend-Only Triggers
- Emails only sent from controller methods
- Requires admin authentication
- Route protection via middleware

### ✅ Input Validation
- Rejection reason: `required|string|max:500`
- Laravel's validation automatically sanitizes input
- XSS protection via Blade templating engine

### ✅ Email Validation
- User email validated during registration
- Must be official SorSU email (@sorsu.edu.ph)

### ✅ Queued Processing
- Emails sent via queue system (database driver)
- Non-blocking user experience
- Automatic retry on failure
- Prevents email sending delays

### ✅ Audit Trail
- `approved_by` tracks approving admin
- `approved_at` / `rejected_at` timestamp events
- Full history maintained in database

---

## 🔐 Login Access Control

### Login Validation Logic
**Location:** `app/Http/Controllers/AuthController.php`

**Status-Based Messages:**

| Status | Message | Access |
|--------|---------|--------|
| PENDING | "Your account is pending approval." | ❌ Blocked |
| REJECTED | "Your registration was rejected. Please check your email." | ❌ Blocked |
| APPROVED | (none - login successful) | ✅ Allowed |

**Implementation:**
```php
if ($user->approval_status !== User::APPROVAL_APPROVED && $user->role !== 'admin') {
    Auth::logout();
    
    $errorMessage = match($user->approval_status) {
        User::APPROVAL_PENDING => 'Your account is pending approval.',
        User::APPROVAL_REJECTED => 'Your registration was rejected. Please check your email.',
        default => 'Your account is not approved for system access.',
    };
    
    return back()->withErrors(['email' => $errorMessage]);
}
```

**Note:** Admin users bypass approval check.

---

## ⚙️ Queue Configuration

### Current Setup
- **Driver:** `database`
- **Table:** `jobs`
- **Config File:** `config/queue.php`

### Running Queue Worker

**Development:**
```bash
php artisan queue:work
```

**Production (Supervisor recommended):**
```bash
php artisan queue:work --daemon
```

### Testing Email Queue

**Check pending jobs:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

---

## 📋 Admin Workflow

### Approval Process

1. Admin navigates to User Approvals page
2. Reviews pending registration
3. Clicks "Approve" button
4. **System Actions:**
   - Updates user status to APPROVED
   - Records approval timestamp
   - Records approving admin ID
   - Enables login access
   - Sends in-app notification
   - **Queues approval email**

### Rejection Process

1. Admin navigates to User Approvals page
2. Reviews pending registration
3. Clicks "Reject" button
4. **Modal appears prompting for rejection reason**
5. Admin enters reason (required)
6. Confirms rejection
7. **System Actions:**
   - Updates user status to REJECTED
   - Records rejection timestamp
   - Saves rejection reason
   - Blocks login access
   - Sends in-app notification
   - **Queues rejection email with reason**

---

## 📝 Email Templates

### Approval Email Template
**File:** `resources/views/emails/account-approved.blade.php`

**Features:**
- Responsive HTML design
- SorSU branded header with logo
- Centered call-to-action button
- Professional styling
- Mobile-friendly layout

**Dynamic Elements:**
- `{{ $user->first_name }}` - User's first name
- `{{ config('app.url') }}/login` - Login URL

### Rejection Email Template
**File:** `resources/views/emails/account-rejected.blade.php`

**Features:**
- Responsive HTML design
- SorSU branded header
- Highlighted reason box
- Professional styling
- Mobile-friendly layout

**Dynamic Elements:**
- `{{ $user->first_name }}` - User's first name
- `{{ $reason }}` - Admin-provided rejection reason

---

## 🚀 Environment Configuration

### Required .env Variables

```env
# Application URL (for email links)
APP_URL=https://yourdomain.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sorsu.edu.ph
MAIL_FROM_NAME="SorSU Scheduling System"

# Queue Configuration
QUEUE_CONNECTION=database
```

### Testing Configuration

For local development/testing without sending real emails:

```env
MAIL_MAILER=log
```

This logs emails to `storage/logs/laravel.log` instead of sending them.

---

## 🧪 Testing the Implementation

### Manual Testing Steps

#### Test Approval Email:

1. Create a test user registration:
   ```bash
   # Register via website
   # Or seed test data
   ```

2. Login as admin

3. Navigate to: `/admin/users/approvals`

4. Click "Approve" on pending user

5. **Verify:**
   - ✅ Success message appears
   - ✅ Email queued in `jobs` table
   - ✅ User status = APPROVED
   - ✅ User can now login
   - ✅ Email received with login link

#### Test Rejection Email:

1. Create another test user registration

2. Login as admin

3. Navigate to: `/admin/users/approvals`

4. Click "Reject" on pending user

5. Enter rejection reason: "Invalid university email domain"

6. Confirm rejection

7. **Verify:**
   - ✅ Success message appears
   - ✅ Email queued in `jobs` table
   - ✅ User status = REJECTED
   - ✅ User cannot login
   - ✅ Email received with reason

#### Test Login Blocking:

1. **Pending User:** Attempt login
   - Expected: "Your account is pending approval."

2. **Rejected User:** Attempt login
   - Expected: "Your registration was rejected. Please check your email."

3. **Approved User:** Attempt login
   - Expected: Successful login

---

## 📊 Database Queries for Verification

### Check user approval status:
```sql
SELECT id, first_name, last_name, email, approval_status, approved_at, rejected_at, rejection_reason
FROM users
WHERE registration_source = 'self_registration';
```

### Check pending jobs:
```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

### Check failed jobs:
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

---

## 🔍 Troubleshooting

### Emails Not Sending

**Check queue worker is running:**
```bash
ps aux | grep "queue:work"
```

**Restart queue worker:**
```bash
php artisan queue:restart
```

**Check failed jobs:**
```bash
php artisan queue:failed
```

### Email Content Issues

**Clear view cache:**
```bash
php artisan view:clear
```

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

### Login Still Works for Rejected Users

**Check approval status validation:**
- Verify `AuthController` has approval check
- Ensure middleware is applied
- Clear route cache: `php artisan route:clear`

---

## 📚 Related Files

### Controllers
- `app/Http/Controllers/Admin/UserApprovalController.php`
- `app/Http/Controllers/AuthController.php`

### Models
- `app/Models/User.php`

### Mailable Classes
- `app/Mail/AccountApprovedMail.php`
- `app/Mail/AccountRejectedMail.php`

### Email Views
- `resources/views/emails/account-approved.blade.php`
- `resources/views/emails/account-rejected.blade.php`

### Migrations
- `database/migrations/2026_02_24_000001_add_approval_to_users_table.php`
- `database/migrations/2026_02_24_024353_add_rejection_fields_to_users_table.php`

### Configuration
- `config/queue.php`
- `config/mail.php`

---

## ✨ Best Practices Implemented

✅ **Queued Emails** - Non-blocking performance  
✅ **Input Sanitization** - Laravel validation  
✅ **Audit Trail** - Timestamps and admin tracking  
✅ **Security** - Backend-only triggers  
✅ **Responsive Design** - Mobile-friendly emails  
✅ **Professional Branding** - SorSU branded templates  
✅ **Clear Messaging** - User-friendly content  
✅ **Error Handling** - Graceful failure recovery  
✅ **Environment Config** - Dynamic URLs  
✅ **Status-Based Access** - Login restrictions  

---

## 🎯 Next Steps (Optional Improvements)

### Recommended Enhancements:

1. **Email Logging**
   - Track email delivery status
   - Store email sent timestamps

2. **Retry Mechanism**
   - Configure max attempts
   - Exponential backoff

3. **Admin Notifications**
   - Notify admins of pending approvals
   - Daily summary emails

4. **User Re-application**
   - Allow rejected users to reapply
   - Separate workflow for re-applications

5. **Bulk Actions**
   - Approve/reject multiple users at once
   - Batch email sending

6. **Email Preferences**
   - Allow users to opt-out of non-critical emails
   - Preference management page

---

## 📞 Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check queue status: `php artisan queue:monitor`
- Review this documentation
- Contact system administrator

---

**Last Updated:** March 2, 2026  
**Version:** 2.0  
**Status:** ✅ Fully Implemented and Updated
