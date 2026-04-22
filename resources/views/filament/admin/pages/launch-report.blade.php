<x-filament-panels::page>
    <div class="p-6 bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->form }}
    </div>

    <div x-data="{ tab: 'overview' }">

        <x-filament::tabs label="Вкладки аналитики">
            <x-filament::tabs.item
                    alpine-active="tab === 'overview'"
                    x-on:click="tab = 'overview'"
                    icon="heroicon-m-presentation-chart-line"
            >
                Сводка по запуску
            </x-filament::tabs.item>

            {{-- НОВАЯ ВКЛАДКА ДЛЯ ПРОДУКТОВ --}}
            <x-filament::tabs.item
                    alpine-active="tab === 'products'"
                    x-on:click="tab = 'products'"
                    icon="heroicon-o-shopping-bag"
            >
                Тарифы и Продукты
            </x-filament::tabs.item>

            <x-filament::tabs.item
                    alpine-active="tab === 'traffic'"
                    x-on:click="tab = 'traffic'"
                    icon="heroicon-m-globe-alt"
            >
                Источники трафика (UTM)
            </x-filament::tabs.item>

            <x-filament::tabs.item
                    alpine-active="tab === 'webinars'"
                    x-on:click="tab = 'webinars'"
                    icon="heroicon-o-video-camera"
            >
                Вебинары потока
            </x-filament::tabs.item>

            <x-filament::tabs.item
                    alpine-active="tab === 'managers'"
                    x-on:click="tab = 'managers'"
                    icon="heroicon-o-users"
            >
                Менеджеры
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div x-show="tab === 'overview'" class="mt-6 space-y-6">

            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white mb-4">💰 Финансовая сводка</h2>
                @livewire(\App\Filament\Admin\Widgets\LaunchKPIs::class)
            </div>

            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white mb-4 mt-8">📊 Воронка и конверсии</h2>
                @livewire(\App\Filament\Admin\Widgets\LaunchFunnelKPIs::class)
            </div>

            @livewire(\App\Filament\Admin\Widgets\DailyRevenueTrend::class)
            @livewire(\App\Filament\Admin\Widgets\DailyOrdersFunnel::class)
            @livewire(\App\Filament\Admin\Widgets\DailyDealsTable::class)
        </div>

        {{-- НОВЫЙ БЛОК С ТАБЛИЦЕЙ ТАРИФОВ --}}
        <div x-show="tab === 'products'" class="mt-6 space-y-6" x-cloak>
            @livewire(\App\Filament\Admin\Widgets\ProductMixTable::class)
        </div>

        {{-- ОБНОВЛЕННАЯ ВКЛАДКА ТРАФИКА --}}
        <div x-show="tab === 'traffic'" class="mt-6 space-y-6" x-cloak>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    @livewire(\App\Filament\Admin\Widgets\UtmClientChart::class)
                </div>
                <div>
                    @livewire(\App\Filament\Admin\Widgets\UtmDealChart::class)
                </div>
            </div>

            @livewire(\App\Filament\Admin\Widgets\UtmPerformanceTable::class)
        </div>

        <div x-show="tab === 'webinars'" class="mt-6 space-y-6" x-cloak>
            @livewire(\App\Filament\Admin\Widgets\LaunchWebinarsTable::class)
        </div>

        <div x-show="tab === 'managers'" class="mt-6 space-y-6" x-cloak>
            @livewire(\App\Filament\Admin\Widgets\ManagerPerformanceTable::class)
        </div>

    </div>
</x-filament-panels::page>