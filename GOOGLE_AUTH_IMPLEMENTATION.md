# Google Authentication Implementation Guide

## Overview
The SorSU Scheduling System has been updated to support Google OAuth authentication alongside the existing email/password login system. Users can now:
- Register and login using Google email addresses
- Use Google Sign-In for quick authentication
- Account approval workflow remains unchanged

## Changes Made

### 1. Database Schema
**Migration File:** `database/migrations/2026_03_06_000001_add_google_auth_to_users_table.php`

New fields added to `users` table:
- `google_id` (string, unique, nullable): Google's unique identifier for the user
- `auth_provider` (enum: 'local' | 'google', default: 'local'): Authentication method used

**Run Migration:**
```bash
php artisan migrate
```

### 2. User Model Updates
**File:** `app/Models/User.php`

Added to `$fillable` array:
- `google_id`
- `auth_provider`

### 3. Registration Validation
**File:** `app/Http/Requests/RegisterRequest.php`

**Changes:**
- Removed SorSU email domain requirement
- Added Gmail-only validation: `regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/`
- Updated validation messages

**Email Rules:**
```php
'email' => [
    'required',
    'email:rfc,dns',
    Rule::unique('users', 'email'),
    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/',
],
```

### 4. Registration Controller Updates
**File:** `app/Http/Controllers/RegistrationController.php`

- Set `auth_provider` to `'local'` for email/password registrations
- Comment updated to reflect Gmail requirement

### 5. Google OAuth Controller
**File:** `app/Http/Controllers/GoogleAuthController.php`

**Features:**
- `redirectToGoogle()`: Initiates OAuth flow with CSRF protection
- `handleGoogleCallback()`: Processes Google callback
- `getGoogleUser()`: Exchanges auth code for user data
- `isValidGmailAddress()`: Validates Gmail-only requirement
- `redirectToDashboard()`: Routes to appropriate user dashboard

**Security Features:**
- CSRF state verification
- Google token validation
- Gmail domain enforcement
- Account status checks before login
- Admin approval workflow compliance

**User Flow:**
1. **Existing User** → Logs in immediately (if approved)
2. **New User** → Created with `approval_status = PENDING`, email notification sent to admins

### 6. Configuration
**File:** `config/services.php`

Added Google OAuth configuration:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
],
```

### 7. Routes
**File:** `routes/web.php`

Added guest routes:
```php
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');
```

### 8. Login Page Updates
**File:** `resources/views/auth/login.blade.php`

Added:
- Divider between email login and OAuth options
- "Continue with Google" button with:
  - Google SVG icon
  - White background with subtle border/shadow
  - Full width styling matching login button

### 9. Registration Page Updates
**File:** `resources/views/auth/register.blade.php`

**Email Field Changes:**
- Label: "SorSU Email" → "Google Email Address"
- Placeholder: "your.email@sorsu.edu.ph" → "example@gmail.com"
- Helper Text: "You must use your official SorSU email address." → "Please use your Google email address to register."

Added:
- Divider between email form and OAuth options
- "Sign up with Google" button with consistent styling

## Environment Configuration

Add these to your `.env` file:

```env
# Google OAuth Credentials
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

### How to Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Enable the Google+ API
4. Create OAuth 2.0 credentials (Web application type)
5. Set Authorized redirect URIs to:
   - `http://localhost:8000/auth/google/callback` (development)
   - `https://yourdomain.com/auth/google/callback` (production)
6. Copy Client ID and Client Secret to .env file

## Database Changes Summary

```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE NULLABLE AFTER password;
ALTER TABLE users ADD COLUMN auth_provider ENUM('local', 'google') DEFAULT 'local' AFTER google_id;
```

## Testing Checklist

### Email/Password Registration
- [ ] User can register with Gmail address (example@gmail.com)
- [ ] Non-Gmail addresses are rejected
- [ ] Password validation works (uppercase, lowercase, numbers, special chars)
- [ ] User receives pending approval message
- [ ] Admin receives notification

### Google OAuth Registration
- [ ] Click "Sign up with Google" button
- [ ] Redirects to Google login
- [ ] Google account is selected
- [ ] Redirects back to app with user created
- [ ] User receives pending approval message
- [ ] Admin receives notification (marked as "Google")
- [ ] First/last name populated from Google profile

### Email/Password Login
- [ ] Approved user can login
- [ ] Pending user sees approval message
- [ ] Rejected user sees rejection message
- [ ] Deactivated user sees deactivation message

### Google OAuth Login
- [ ] Approved existing user can login via Google
- [ ] Pending user sees approval message
- [ ] Rejected user sees rejection message
- [ ] New Google user account created and pending

### Admin Approval
- [ ] Admin can approve Google OAuth registrations
- [ ] Admin can reject Google OAuth registrations
- [ ] User receives email notifications
- [ ] Approved user can now login

## Expected Results

✅ Users can register using Google email addresses  
✅ Users can login with email/password  
✅ Users can login/register with Google Sign-In  
✅ New Google accounts require admin approval  
✅ Admin approval workflow unchanged  
✅ Browser tab shows "SorSU Scheduling System | User Approval Management"  
✅ Email validation enforces @gmail.com  
✅ Dropdown menus not highlighted on approval page  

## Security Considerations

1. **CSRF Protection**: State parameter validated on callback
2. **Token Verification**: Google access token exchanged securely
3. **Email Domain Enforcement**: Only Gmail addresses accepted
4. **Session Security**: Sessions regenerated after login
5. **Account Status Checks**: User approval status verified before login
6. **Password Hashing**: Local passwords hashed with bcrypt
7. **NULL Passwords**: Google users have NULL passwords (cannot login without Google)

## Rollback (If Needed)

To revert the migration:
```bash
php artisan migrate:rollback --step=1
```

This will remove the `google_id` and `auth_provider` columns.

## Notes

- The admin approval workflow is completely unchanged
- Google users cannot login if still pending approval
- Google users with NULL passwords cannot use email/password login
- All security middleware and user status checks are maintained
- Account deactivation works for both local and Google users
