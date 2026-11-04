<p>Salve <strong>{{ $nome }}</strong>,</p>
<p>abbiamo creato il tuo account per accedere all'area riservata. Qui di seguito trovi i tuoi dati provvisori:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f7fc; border:1px solid #dbe7f3; border-radius:12px; margin:0 0 16px;">
    <tr>
        <td class="colonna" width="35%" style="padding:14px 18px 4px; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Nome utente</td>
        <td class="colonna" style="padding:14px 18px 4px; font-size:16px; font-weight:700; color:#0f3d5e;">{{ $utente }}</td>
    </tr>
    <tr>
        <td class="colonna" width="35%" style="padding:4px 18px 14px; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#64748b;">Password</td>
        <td class="colonna" style="padding:4px 18px 14px; font-family:'Courier New', monospace; font-size:18px; font-weight:700; color:#0f3d5e;">{{ $password }}</td>
    </tr>
</table>
<p>Ti consigliamo di cambiare la password al primo accesso, dalla sezione <em>Profilo</em>.</p>
