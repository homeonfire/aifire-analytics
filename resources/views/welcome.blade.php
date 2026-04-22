<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIFire Tech | Платформа сквозной аналитики</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-gray-900 antialiased selection:bg-brand-100 selection:text-brand-900">

<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gray-900 rounded flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="font-bold text-xl tracking-tight text-gray-900">AIFire Tech</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="/admin/login" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Войти</a>
                <a href="/admin/register" class="hidden sm:inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 transition-colors shadow-sm">
                    Создать аккаунт
                </a>
            </div>
        </div>
    </div>
</header>

<section class="relative pt-24 pb-20 lg:pt-32 lg:pb-28 overflow-hidden bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200 mb-8 shadow-sm">
                Сквозная аналитика для инфобизнеса
            </span>
        <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-gray-900 mb-6">
            Вся правда о продажах <br class="hidden md:block">
            в одном окне
        </h1>
        <p class="mt-4 max-w-2xl mx-auto text-lg md:text-xl text-gray-600 mb-10">
            Автоматический сбор данных из GetCourse и Bizon365. Единая карточка клиента, аналитика запусков и прозрачный отдел продаж. Никаких Google Таблиц.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="/admin/register" class="inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 transition-colors shadow-sm">
                Начать работу бесплатно
            </a>
            <a href="#features" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                Смотреть интерфейс
            </a>
        </div>

        <div class="mt-20 relative mx-auto max-w-5xl">
            <div class="rounded-xl bg-gray-200/50 p-2 ring-1 ring-inset ring-gray-200 lg:rounded-2xl lg:p-4">
                <div class="aspect-[16/9] w-full rounded-lg bg-white shadow-xl ring-1 ring-gray-200 flex items-center justify-center overflow-hidden">
                    <img src="/images/landing/main_screen.png" alt="AIFire Tech Дашборд" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 tracking-tight">Как вы работаете сейчас <span class="text-gray-300 mx-2 font-normal">vs</span> Как будете завтра</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-gray-50 rounded-xl p-8 border border-gray-200">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center border border-red-200">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Хаос и рутина</h3>
                </div>
                <ul class="space-y-4 text-gray-600">
                    <li class="flex items-start gap-3">
                        <span class="text-red-500 mt-0.5">✗</span>
                        Сводка данных руками в Google Таблицах, ошибки в формулах и потраченные часы.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-red-500 mt-0.5">✗</span>
                        Метки Last-touch перезаписывают историю. Непонятно, откуда клиент пришел изначально.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-red-500 mt-0.5">✗</span>
                        Дубли в базе из-за разных email-адресов одного человека.
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-lg shadow-gray-100 ring-1 ring-gray-900/5">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center border border-green-200">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Система и контроль</h3>
                </div>
                <ul class="space-y-4 text-gray-700 font-medium">
                    <li class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                        Полная автоматизация. Вебхуки обновляют данные секунда в секунду.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                        Матрица UTM. Сохраняем First-touch и источники каждого отдельного заказа.
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></span>
                        Умная склейка профилей. Вся история клиента — от первого вебинара до флагмана.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">

        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h3 class="text-3xl font-bold text-gray-900 mb-4 tracking-tight">Единая карточка клиента</h3>
                <p class="text-lg text-gray-600 mb-6">Больше никаких разрозненных данных. Платформа собирает цифровой след пользователя из всех сервисов в единый профиль.</p>
                <ul class="space-y-3">
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Матрица UTM:</b> Точка входа в базу и источники каждой оплаты.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Продукты:</b> Все трипваеры, флагманы и подписки пользователя.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Вебинары:</b> Посещения эфиров и фактическое время просмотра.</span>
                    </li>
                </ul>
            </div>
            <div class="rounded-xl bg-white p-2 border border-gray-200 shadow-sm">
                <div class="aspect-[4/3] rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 overflow-hidden">
                    <img src="/images/landing/second.png" alt="AIFire Tech Дашборд" class="w-full h-full object-cover">
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center md:flex-row-reverse">
            <div class="md:order-2">
                <h3 class="text-3xl font-bold text-gray-900 mb-4 tracking-tight">Когортный анализ запусков</h3>
                <p class="text-lg text-gray-600 mb-6">Объединяйте вебинары, тарифы и продукты в когорты (Запуски). Управляйте маркетингом, опираясь на жесткую статистику.</p>
                <ul class="space-y-3">
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Воронка доходимости:</b> Считаем ядро аудитории с 1-го дня запуска.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Аналитика эфира:</b> Поиск поминутных отвалов аудитории.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Конверсии:</b> Точные переходы из регистрации в зрителя и в оплату.</span>
                    </li>
                </ul>
            </div>
            <div class="rounded-xl bg-white p-2 border border-gray-200 shadow-sm md:order-1">
                <div class="aspect-[4/3] rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 overflow-hidden">
                    <img src="/images/landing/third.png" alt="AIFire Tech Дашборд" class="w-full h-full object-cover">
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h3 class="text-3xl font-bold text-gray-900 mb-4 tracking-tight">Прозрачный отдел продаж</h3>
                <p class="text-lg text-gray-600 mb-6">Хватит верить менеджерам на слово. Оценивайте реальную эффективность каждого сотрудника и находите узкие места воронки.</p>
                <ul class="space-y-3">
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Воронка статусов:</b> Сделки в работе, полные оплаты и рассрочки.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Цикл сделки:</b> Точное время от создания заказа до получения денег.</span>
                    </li>
                    <li class="flex gap-3 text-gray-700">
                        <span class="text-brand-600">→</span>
                        <span><b>Мгновенный контроль:</b> Переход к заказу в GetCourse в один клик.</span>
                    </li>
                </ul>
            </div>
            <div class="rounded-xl bg-white p-2 border border-gray-200 shadow-sm">
                <div class="aspect-[4/3] rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 overflow-hidden">
                    <img src="/images/landing/four.png" alt="AIFire Tech Дашборд" class="w-full h-full object-cover">
                </div>
            </div>
        </div>

    </div>
</section>

<section class="py-20 bg-white border-y border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-8 tracking-tight">Бесшовная экосистема интеграций</h2>

        <div class="flex flex-wrap justify-center gap-3">
            <div class="px-5 py-2.5 rounded-lg bg-white border border-gray-200 shadow-sm text-gray-900 font-medium flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span> GetCourse
            </div>
            <div class="px-5 py-2.5 rounded-lg bg-white border border-gray-200 shadow-sm text-gray-900 font-medium flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span> Bizon365
            </div>
            <div class="px-5 py-2.5 rounded-lg bg-gray-50 border border-gray-200 border-dashed text-gray-500 font-medium text-sm flex items-center">
                Tilda (Скоро)
            </div>
            <div class="px-5 py-2.5 rounded-lg bg-gray-50 border border-gray-200 border-dashed text-gray-500 font-medium text-sm flex items-center">
                SaleBot (Скоро)
            </div>
            <div class="px-5 py-2.5 rounded-lg bg-gray-50 border border-gray-200 border-dashed text-gray-500 font-medium text-sm flex items-center">
                BotHelp (Скоро)
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-gray-900 relative">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">Готовы увидеть реальные цифры?</h2>
        <p class="text-lg text-gray-400 mb-10">
            Выходим из тени Google Таблиц. Зарегистрируйтесь и настройте интеграцию за 15 минут.
        </p>
        <a href="/admin/register" class="inline-flex justify-center items-center px-8 py-4 text-base font-bold rounded-lg text-gray-900 bg-white hover:bg-gray-100 transition-colors shadow-lg">
            Создать проект
        </a>
    </div>
</section>

</body>
</html>