<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('chatbot.admin_dashboard') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .date { font-size: 12px; color: #666; margin-top: 5px; }
        
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; background-color: #f4f4f4; padding: 8px; border-left: 5px solid #007bff; margin-bottom: 15px; }
        
        .stats-table { width: 100%; border-collapse: collapse; }
        .stats-table th, .stats-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .stats-table th { background-color: #f9f9f9; font-weight: bold; width: 40%; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">{{ __('chatbot.admin_dashboard') }}</div>
        <div class="date">{{ __('exam_schedule.generated_on') }} {{ now()->format('d M, Y h:i A') }}</div>
    </div>

    <div class="section">
        <div class="section-title">{{ __('finance.summary') }}</div>
        <table class="stats-table">
            <tr>
                <th>{{ __('institute.institute_list') }}</th>
                <td>{{ $stats['schools'] }}</td>
            </tr>
            <tr>
                <th>{{ __('student.total_students') }}</th>
                <td>{{ $stats['students'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ __('finance.balance_overview') }}</div>
        <table class="stats-table">
            <tr>
                <th>{{ __('finance.paid_students') }}</th>
                <td>{{ $stats['paid_students'] }} ({{ $stats['paid_percentage'] }}%)</td>
            </tr>
            <tr>
                <th>{{ __('finance.total_collected') }}</th>
                <td>{{ $stats['amount_paid'] }}</td>
            </tr>
            <tr>
                <th>{{ __('finance.total_outstanding') }}</th>
                <td>{{ $stats['outstanding'] }}</td>
            </tr>
            <tr>
                <th>{{ __('finance.total_invoiced') }}</th>
                <td>{{ $stats['total_balance'] }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ __('results.computer_generated') }} | {{ config('app.name') }}
    </div>

</body>
</html>