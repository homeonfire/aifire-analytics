<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TildaLead;
use App\Models\UnifiedClient;
use Illuminate\Support\Facades\Log;

class TildaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Тильда проверяет вебхук при добавлении на сайт
        if ($request->input('test') === 'test') {
            return response()->json(['status' => 'ok', 'message' => 'Webhook is ready!']);
        }

        // Логируем входящие данные (полезно для дебага)
        Log::info('Tilda Webhook:', $request->all());

        $email = $request->input('email');
        // Очищаем телефон от скобок, плюсов и пробелов
        $phone = $request->input('phone') ? preg_replace('/[^0-9]/', '', $request->input('phone')) : null;

        if (!$email && !$phone) {
            return response()->json(['status' => 'error', 'message' => 'No email or phone'], 400);
        }

        // 2. Ищем основного клиента или создаем нового
        $client = null;
        $clientQuery = UnifiedClient::query();

        if ($email) {
            $clientQuery->where('email', $email);
        }
        if ($phone) {
            $clientQuery->orWhere('phone', $phone);
        }

        $client = $clientQuery->first();

        // Если клиента еще нет в базе - создаем болванку
        if (!$client) {
            $client = UnifiedClient::create([
                'email' => $email,
                'phone' => $phone,
                'name'  => $request->input('name'),
                'utm_source' => $request->input('utm_source'), // Сохраним ему первую UTM как основную
            ]);
        }

        // 3. Сохраняем заявку из Тильды
        TildaLead::create([
            'unified_client_id' => $client->id,
            'name'         => $request->input('name'),
            'email'        => $email,
            'phone'        => $phone,
            'utm_source   ' => $request->input('utm_source'),
            'utm_medium'   => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_content'  => $request->input('utm_content'),
            'utm_term'     => $request->input('utm_term'),
        ]);

        return response()->json(['status' => 'success']);
    }
}
