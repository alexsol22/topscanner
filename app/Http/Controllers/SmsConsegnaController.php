<?php

namespace App\Http\Controllers;

use App\Services\Sms;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Callback del provider SMS (return/delivery.php di Yii): registra l'esito di consegna. */
class SmsConsegnaController extends Controller
{
    public function __invoke(Request $request, Sms $sms): Response
    {
        $password = (string) config('sms.delivery_password');

        abort_unless($password !== '' && hash_equals($password, (string) $request->input('psw')), 403);

        $sms->registraConsegna($request->only(['id_send', 'delivery_number', 'delivery_status', 'delivery_data']));

        return response('OK');
    }
}
