<!DOCTYPE html>
<html>
<head>
    <title>{{ __('transfer.cert_header') }}</title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 40px; border: 10px double #333; height: 90%; }
        .header { text-align: center; margin-bottom: 40px; }
        .school-name { font-size: 30px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .address { font-size: 14px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
        
        .content { font-size: 18px; line-height: 2; margin-bottom: 50px; }
        .field { border-bottom: 1px dotted #000; font-weight: bold; padding: 0 10px; }
        
        .footer { width: 100%; position: fixed; bottom: 100px; }
        .signature { float: right; border-top: 1px solid #000; padding-top: 5px; text-align: center; width: 200px; }
        .date { float: left; }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">{{ $student->institution->name }}</div>
        <div class="address">{{ $student->institution->address ?? '' }}</div>
        <div class="title">{{ __('transfer.cert_header') }}</div>
    </div>

    <div class="content">
        {{ __('transfer.cert_body_1') }} <span class="field">{{ $student->full_name }}</span>, 
        {{ __('transfer.cert_adm_no') }} <span class="field">{{ $student->admission_number }}</span>, 
        {{ __('transfer.cert_body_2') }} <span class="field">{{ $student->admission_date ? $student->admission_date->format('d M, Y') : 'N/A' }}</span> 
        {{ __('transfer.cert_to') }} <span class="field">{{ $transfer->transfer_date->format('d M, Y') }}</span>.
        <br><br>
        {{ __('transfer.cert_body_3') }} <span class="field">{{ $transfer->leaving_class }}</span>.
        <br><br>
        <strong>{{ __('transfer.cert_reason') }}</strong> <span class="field">{{ $transfer->reason }}</span>
        <br>
        <strong>{{ __('transfer.cert_conduct') }}</strong> <span class="field">{{ $transfer->conduct }}</span>
        <br><br>
        {{ __('transfer.cert_footer') }}
    </div>

    <div class="footer">
        <div class="date">{{ __('transfer.date') }} {{ date('d M, Y') }}</div>
        <div class="signature">{{ __('transfer.signature') }}</div>
    </div>

</body>
</html>