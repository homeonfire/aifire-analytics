<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GigaProxyController extends Controller
{
    public function handle(Request $request)
    {
        // Достаем ключ из .env (готовый Authorization Key)
        $authKey = env('GIGACHAT_AUTH_KEY');
        $scope = 'GIGACHAT_API_PERS';
        $rqUid = Str::uuid()->toString();

        if (!$authKey) {
            return response()->json(['error' => 'GIGACHAT_AUTH_KEY не задан'], 500);
        }

        // --- 1. Получаем токен ---
        $authResponse = Http::asForm()
            ->withoutVerifying() // Игнорируем проблемы с SSL Минцифры
            ->withHeaders([
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => 'application/json',
                'RqUID'         => $rqUid,
                'Authorization' => 'Basic ' . $authKey,
            ])
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => $scope,
            ]);

        if ($authResponse->failed()) {
            return response()->json([
                'error' => 'Ошибка авторизации',
                'details' => $authResponse->json()
            ], $authResponse->status());
        }

        $accessToken = $authResponse->json('access_token');

        // --- 2. Пробрасываем универсальный запрос к нейросети ---
        // Берем весь payload из Google Sheets ($request->all()) и кидаем его в Сбер
        $chatResponse = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])
            ->withoutVerifying()
            ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', $request->all());

        // Возвращаем полный оригинальный ответ Сбера обратно в таблицу
        return response()->json($chatResponse->json(), $chatResponse->status());
    }
}