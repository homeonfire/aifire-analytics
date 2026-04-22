<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnifiedClient;
use App\Models\School;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function importUsersFromGC(Request $request, $school_uuid)
    {
        Log::info('GetCourse User Webhook:', $request->all());

        if (!$request->filled('email')) {
            return response()->json(['error' => 'Email is required'], 400);
        }

        // Находим школу по UUID из URL (если такой нет - выдаст 404)
        $school = School::where('uuid', $school_uuid)->firstOrFail();

        // Ищем/создаем клиента СТРОГО внутри этой школы
        $client = UnifiedClient::updateOrCreate(
            [
                'email' => $request->input('email'),
                'school_id' => $school->id // Изоляция
            ],
            [
                'phone'        => $request->input('phone'),
                'first_name'   => $request->input('first_name'),
                'last_name'    => $request->input('last_name'),
                'getcourse_id' => $request->input('getcourse_id'),
                'salebot_id'   => $request->input('sb_id'),
                'payed_money'   => (int) preg_replace('/[^0-9]/', '', preg_split('/[,.]/', $request->input('payed_money', '0'))[0]),
                'utm_source'   => $request->input('utm_source'),
                'utm_medium'   => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
            ]
        );

        return response()->json(['status' => 'success', 'client_id' => $client->id]);
    }
}