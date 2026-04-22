<x-filament-panels::page>

    {{-- Наша новая форма настроек API --}}
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary">
                Сохранить настройки
            </x-filament::button>
        </div>
    </form>

    {{-- Твои старые настройки вебхуков (оставляем их ниже как легаси или альтернативу) --}}
    <div class="mt-8 p-6 bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-lg font-bold mb-4">Настройка старых Webhook</h2>
        <p class="text-sm text-gray-500 mb-4">Для проекта: <strong>{{ $schoolName }}</strong></p>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Ссылка для процессов (Заказы)</label>
            <input type="text" readonly value="{{ $ordersUrl }}" class="w-full rounded-lg border-gray-300 bg-gray-50 p-2 text-sm dark:bg-gray-800 dark:border-gray-700">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Ссылка для процессов (Пользователи)</label>
            <input type="text" readonly value="{{ $usersUrl }}" class="w-full rounded-lg border-gray-300 bg-gray-50 p-2 text-sm dark:bg-gray-800 dark:border-gray-700">
        </div>
    </div>

</x-filament-panels::page>