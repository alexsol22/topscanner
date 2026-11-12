<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>{{ $oggetto }}</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #1677a8; }
        .contenuto p { margin: 0 0 16px; }
        .contenuto a { color: #1677a8; }
        @media only screen and (max-width: 620px) {
            .contenitore { width: 100% !important; }
            .padding { padding-left: 20px !important; padding-right: 20px !important; }
            .titolo { font-size: 20px !important; }
            .colonna { display: block !important; width: 100% !important; text-align: left !important; padding-top: 0 !important; padding-bottom: 6px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f9fc;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">{{ $anteprima ?? $oggetto }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f9fc;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" class="contenitore" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
                    <tr>
                        <td style="background-color:#ffffff; border-radius:16px; border:1px solid #dce8f0; overflow:hidden;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="height:6px; line-height:6px; font-size:0; background-color:#152B38;">&nbsp;</td></tr>
                                <tr>
                                    <td class="padding" align="center" style="padding:28px 40px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-right:12px; vertical-align:middle;">
                                                    <img src="{{ $logo }}" alt="{{ $app }}" width="48" height="48" style="display:block; width:48px; height:48px; border:0;">
                                                </td>
                                                <td style="vertical-align:middle; font-family:Arial, Helvetica, sans-serif; font-size:18px; line-height:22px; font-weight:700; letter-spacing:-0.02em; color:#152B38;">
                                                    {{ $marca }}@if ($marchio) <span style="color:#b65d3b;">{{ $marchio }}</span>@endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="padding" style="padding:20px 40px 8px; font-family:Arial, Helvetica, sans-serif;">
                                        <h1 class="titolo" style="margin:0; font-size:24px; line-height:30px; color:#152B38; font-weight:700;">{{ $oggetto }}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="padding contenuto" style="padding:16px 40px 8px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:24px; color:#334155;">
                                        {!! $corpoHtml !!}
                                    </td>
                                </tr>
                                @if ($azione)
                                    <tr>
                                        <td class="padding" align="center" style="padding:8px 40px 24px;">
                                            <a href="{{ $azione['url'] }}" style="display:inline-block; background-color:#152B38; color:#ffffff; font-family:Arial, Helvetica, sans-serif; font-size:15px; font-weight:700; text-decoration:none; padding:13px 28px; border-radius:10px;">{{ $azione['etichetta'] }}</a>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="padding" style="padding:8px 40px 32px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:24px; color:#334155;">
                                        Cordiali saluti,<br><strong>Lo Staff {{ $app }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="padding" align="center" style="padding:24px 20px 8px; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:19px; color:#64748b;">
                            <strong style="color:#475569;">{{ $societa['ragione_sociale'] }}</strong><br>
                            @if ($societa['indirizzo']){{ $societa['indirizzo'] }}<br>@endif
                            @if ($societa['telefono'])Tel. {{ $societa['telefono'] }}@endif
                            @if ($societa['telefono'] && $societa['email']) &middot; @endif
                            @if ($societa['email'])<a href="mailto:{{ $societa['email'] }}" style="color:#1677a8; text-decoration:none;">{{ $societa['email'] }}</a>@endif
                            @if ($societa['iva'])<br>P.IVA {{ $societa['iva'] }}@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="padding" align="center" style="padding:8px 20px 16px; font-family:Arial, Helvetica, sans-serif; font-size:11px; line-height:17px; color:#94a3b8;">
                            Hai ricevuto questa email perché hai un account su {{ $app }}. Se pensi sia arrivata per errore, scrivici a {{ $societa['email'] ?: 'info' }}.<br>
                            Messaggio automatico, ti preghiamo di non rispondere direttamente.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
