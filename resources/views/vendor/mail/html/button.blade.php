<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation"
    style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; margin: 30px auto; padding: 0; text-align: center; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
    <tr>
        <td align="center" style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 0;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; margin: 0; padding: 0; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                <tr>
                    <td align="center"
                        style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; padding: 0;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; margin: 0; padding: 0; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
                            <tr>
                                <td
                                    style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; border-radius: 4px; padding: 0;">
                                    <a href="{{ $url }}" class="button button-{{ $color ?? 'primary' }}"
                                        target="_blank" rel="noopener"
                                        style="font-family: Helvetica, Arial, sans-serif; box-sizing: border-box; border-radius: 4px; color: #fff; cursor: pointer; display: inline-block; overflow: hidden; text-decoration: none; background-color: @if ($color === 'success') #48bb78 @elseif ($color === 'error') #e53e3e @else #3869d4 @endif; border-bottom: 8px solid @if ($color === 'success') #38a169 @elseif ($color === 'error') #c53030 @else #2d5aa4 @endif; border-left: 18px solid @if ($color === 'success') #48bb78 @elseif ($color === 'error') #e53e3e @else #3869d4 @endif; border-right: 18px solid @if ($color === 'success') #48bb78 @elseif ($color === 'error') #e53e3e @else #3869d4 @endif; border-top: 8px solid @if ($color === 'success') #38a169 @elseif ($color === 'error') #c53030 @else #2d5aa4 @endif; padding: 12px 30px; text-transform: capitalize;">
                                        {{ $slot }}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
