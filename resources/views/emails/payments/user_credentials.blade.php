<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.credentials_subject') }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f4f4; 
        }
        .container { 
            max-width: 600px; 
            margin: 30px auto; 
            padding: 0; 
            border-radius: 8px; 
            background-color: #ffffff; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        .header { 
            background-color: #4a6cf7; 
            color: #ffffff; 
            padding: 20px; 
            text-align: center; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
        }
        .content { 
            padding: 30px; 
        }
        .credentials-box { 
            background-color: #f8f9fa; 
            border-left: 4px solid #4a6cf7; 
            padding: 15px; 
            margin: 20px 0; 
        }
        .button { 
            display: inline-block; 
            padding: 12px 25px; 
            background-color: #4a6cf7; 
            color: #ffffff; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold; 
            margin-top: 10px; 
        }
        .footer { 
            background-color: #f1f1f1; 
            padding: 15px; 
            text-align: center; 
            font-size: 12px; 
            color: #666; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $school_name }}</h1>
        </div>
        <div class="content">
            <p>{{ __('emails.greeting', ['name' => $name]) }}</p>
            
            <p>{{ __('emails.account_created_body', ['school_name' => $school_name, 'role' => $role]) }}</p>
            
            <div class="credentials-box">
                <p style="margin-top: 0;"><strong>{{ __('emails.login_details') }}</strong></p>
                <p style="margin-bottom: 0;">
                    <strong>{{ __('emails.email') }}:</strong> {{ $email }}<br>
                    <strong>{{ __('emails.password') }}:</strong> {{ $password }}
                </p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $login_link }}" class="button">{{ __('emails.login_button') }}</a>
            </div>
            
            <p>
                {{ __('emails.closing') }}<br>
                {{ $school_name }} {{ __('emails.team') }}
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $school_name }}. {{ __('emails.footer_rights') }}
        </div>
    </div>
</body>
</html>