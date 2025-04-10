<!DOCTYPE html>
<html>
<head>
    <title>{{ __('emails.change-your-email') }}</title>
</head>
<body>
    <p>{{ __('emails.you-tried-to-change-email-with', ['email' => $email]) }}</p>
    <p>{{ __('emails.your-verification-code-is') }}: {{ $otp }}</p>
    <p>{{ __('emails.will-expire', ['time' => 3]) }}</p>
</body>
</html>
