@component('mail::message')
    # Reset Your Password

    Hello {{ $firstName }},

    We received a request to reset your password for your SorSU Scheduling System (SSS) account.

    If you did not request this password reset, please ignore this email. Your account remains secure.

    @component('mail::button', ['url' => $resetUrl, 'color' => 'primary'])
        Reset Password
    @endcomponent

    **Link Expiration:** This password reset link will expire in {{ $expirationMinutes }} minutes.

    **Security Notice:** For security reasons, this link is unique and can only be used once.

    ---

    ### 📋 Additional Information

    If the button above doesn't work, you can also copy and paste this link into your browser:

    <{{ $resetUrl }}>

        ### 🔒 Security Tips

        - Never share your password reset link with anyone
        - Your password should contain uppercase, lowercase, numbers, and special characters
        - If you continue to have problems, please contact the administrator

        ---

        Thanks,
        {{ config('app.name') }} - SSS Admin
    @endcomponent
