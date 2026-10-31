<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $messageData['subject'] }}</title></head>
<body style="margin:0;padding:0;background:#f3f5f9;color:#172338;font-family:Arial,Helvetica,sans-serif">
<div style="display:none;max-height:0;overflow:hidden">{{ $messageData['heading'] }} · {{ $messageData['context']['aplikasi'] }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f5f9"><tr><td align="center" style="padding:32px 12px">
    <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#fff;border:1px solid #dfe5ee;border-radius:14px">
        <tr><td style="padding:26px 30px 20px;border-bottom:4px solid {{ $messageData['accent'] }}"><strong style="font-size:21px;letter-spacing:-.3px">{{ $messageData['context']['aplikasi'] }}</strong><p style="margin:7px 0 0;color:#627188;font-size:12px;letter-spacing:1px">PEMBARUAN AKUN &amp; RILIS MUSIK</p></td></tr>
        <tr><td style="padding:28px 30px">
            <span style="display:inline-block;padding:7px 12px;background:#f1ecfc;color:{{ $messageData['accent'] }};border-radius:20px;font-size:12px;font-weight:bold">{{ $messageData['context']['status'] }}</span>
            <h1 style="font-size:25px;line-height:1.3;margin:18px 0;color:#172338">{{ $messageData['heading'] }}</h1>
            <div style="font-size:15px;line-height:1.8;color:#35455c;overflow-wrap:anywhere">{!! nl2br(e($messageData['body'])) !!}</div>
            @if(!empty($messageData['context']['judul_rilis']))
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:22px;background:#f7f9fc;border:1px solid #e3e9f1;border-radius:9px;font-size:13px">
                @foreach(['judul_rilis'=>'Rilis','artis'=>'Artis','upc'=>'UPC','tanggal_rilis'=>'Tanggal Rilis'] as $key=>$label)
                <tr><td style="padding:11px 14px;color:#6b778b;width:110px;vertical-align:top">{{ $label }}</td><td style="padding:11px 14px;color:#172338;font-weight:bold;overflow-wrap:anywhere">{{ $messageData['context'][$key] ?: '—' }}</td></tr>
                @endforeach
            </table>
            @endif
            @if(!empty($messageData['button']))<table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:26px"><tr><td bgcolor="{{ $messageData['accent'] }}" style="border-radius:8px"><a href="{{ $messageData['context']['tautan'] }}" style="display:inline-block;padding:14px 23px;color:#fff;font-size:14px;font-weight:bold;text-decoration:none">{{ $messageData['button'] }}</a></td></tr></table>@endif
            <p style="margin:22px 0 0;font-size:12px;line-height:1.7;color:#6b778b">Jika tombol tidak dapat dibuka, gunakan tautan ini:<br><a href="{{ $messageData['context']['tautan'] }}" style="color:{{ $messageData['accent'] }};overflow-wrap:anywhere">{{ $messageData['context']['tautan'] }}</a></p>
        </td></tr>
        <tr><td style="padding:20px 30px;background:#f7f9fc;border-top:1px solid #e3e9f1;font-size:12px;line-height:1.7;color:#68778b">{!! nl2br(e($messageData['footer'])) !!}</td></tr>
    </table>
</td></tr></table>
</body></html>
