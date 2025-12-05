<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome Email</title>
</head>
<body>
<h2>Welcome {{ $user->name }}!</h2>

<p>Your account has been created successfully.</p>

<p><strong>Email:</strong> {{ $user->email }}</p>
<p><strong>Password:</strong> {{ $passwordPlain }}</p>

<p>Please log in and change your password immediately.</p>

<br>
<p>Regards,<br>
    {{ config('app.name') }}</p>
</body>
</html>
