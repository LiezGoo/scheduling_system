<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>

<body
    style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; background-color: #f5f5f5; color: #3c3c3c; margin: 0; padding: 0; width: 100%; -webkit-text-size-adjust: none;">
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0"
        style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; background-color: #f5f5f5; margin: 0; padding: 0; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
        <tr>
            <td align="center" style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 0;">
                <table class="content" width="100%" cellpadding="0" cellspacing="0"
                    style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; margin: 0; padding: 0; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                    <tr>
                        <td class="header"
                            style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 25px 0; text-align: center;">
                            <a href="{{ url('/') }}"
                                style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; color: #3869d4; text-decoration: none;">
                                {{ config('app.name') }}
                            </a>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; background-color: #ffffff; border-bottom: 1px solid #edeff2; border-top: 1px solid #edeff2; margin: 0; padding: 0; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                            <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"
                                style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; background-color: #ffffff; margin: 0 auto; padding: 0; width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell"
                                        style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 32px;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 0;">
                            <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0"
                                style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; margin: 0 auto; padding: 0; text-align: center; width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                                <tr>
                                    <td class="content-cell" align="center"
                                        style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 32px;">
                                        <p
                                            style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; line-height: 1.5em; margin-top: 0; color: #aeaeae; font-size: 12px;">
                                            &copy; {{ now()->year }} {{ config('app.name') }}. @lang('All rights reserved.')
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
