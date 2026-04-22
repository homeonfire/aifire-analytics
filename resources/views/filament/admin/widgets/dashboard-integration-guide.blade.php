<x-filament-widgets::widget>
    <x-filament::section>
        <div class="text-center mb-8 mt-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 mb-4">
                <x-heroicon-o-rocket-launch class="w-8 h-8" />
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Добро пожаловать в «{{ $schoolName }}»!
            </h2>
            <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
                Сейчас на вашем дашборде пусто. Чтобы здесь появились красивые графики, аналитика выручки и конверсии, настройте передачу данных из GetCourse по ссылкам ниже:
            </p>
        </div>

        <div class="space-y-6 max-w-4xl mx-auto pb-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    🛒 Вызов URL для Процесса по ЗАКАЗАМ (Создание / Оплата)
                </label>
                <textarea
                        readonly
                        rows="4"
                        onclick="this.select()"
                        class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-white/10 dark:text-gray-300"
                >{{ $ordersUrl }}</textarea>
            </div>

            <hr class="border-gray-200 dark:border-white/10">

            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    👤 Вызов URL для Процесса по ПОЛЬЗОВАТЕЛЯМ (Регистрация)
                </label>
                <textarea
                        readonly
                        rows="3"
                        onclick="this.select()"
                        class="block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-white/10 dark:text-gray-300"
                >{{ $usersUrl }}</textarea>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>