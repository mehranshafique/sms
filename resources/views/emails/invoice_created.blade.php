<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 20px 0; }
        .amount-box { background: #e9ecef; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; color: #495057; }
        .footer { font-size: 12px; color: #888; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $schoolName }}</h2>
        </div>
        
        <div class="content">
            <p>Dear Parent/Guardian of <strong>{{ $studentName }}</strong>,</p>
            
            <p>A new invoice has been generated for your attention.</p>
            
            <div class="amount-box">
                {{ $currency }} {{ $amount }}
            </div>
            
            <p><strong>Invoice Number:</strong> #{{ $invoiceNumber }}<br>
            <strong>Due Date:</strong> {{ $dueDate }}</p>
            
            <p>Please ensure payment is made before the due date to avoid any interruptions.</p>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ route('login') }}" class="btn">Login to View Details</a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ $schoolName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>