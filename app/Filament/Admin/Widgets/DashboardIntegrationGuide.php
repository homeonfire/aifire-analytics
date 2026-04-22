<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\UnifiedClient;
use App\Models\Deal;
use Filament\Facades\Filament;

class DashboardIntegrationGuide extends Widget
{
    protected static string $view = 'filament.admin.widgets.dashboard-integration-guide';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    // В Livewire переменные для шаблона объявляются так:
    public ?string $ordersUrl = null;
    public ?string $usersUrl = null;
    public ?string $schoolName = null;

    // Метод mount() запускается один раз при загрузке виджета
    public function mount(): void
    {
        $school = Filament::getTenant();
        $uuid = $school ? $school->uuid : 'ОШИБКА_UUID';

        $this->schoolName = $school ? $school->name : 'вашего проекта';

        $this->ordersUrl = "https://aifire-tech.ru/api/webhooks/{$uuid}/getcourse/orders?email={object.user.email}&phone={object.user.phone}&first_name={object.user.first_name}&last_name={object.user.last_name}&getcourse_id={object.user.id}&city={object.user.city}&avatar={object.user.avatar_url}&sb_id={object.user.sb_id}&utm_source={object.deal_utm_source}&utm_medium={object.deal_utm_medium}&utm_campaign={object.deal_utm_campaign}&gc_number={object.number}&product_title={object.positions}&status={object.status}&cost={object.cost_money_value}&created_at={object.created_at format='Y-m-d H:i:s'}&payed_at={object.payed_at format='Y-m-d H:i:s'}&payed_money={object.payed_money}&manager_name={object.manager}&manager_email={manager_email}&manager_phone={manager_phone}";

        $this->usersUrl = "https://aifire-tech.ru/api/webhooks/{$uuid}/getcourse/users?email={email}&phone={phone}&first_name={first_name}&last_name={last_name}&getcourse_id={id}&sb_id={sb_id}&utm_source={utm_source}&utm_medium={utm_medium}&utm_campaign={utm_campaign}";
    }

    public static function canView(): bool
    {
        $school = Filament::getTenant();
        if (!$school) return false;

        return !UnifiedClient::where('school_id', $school->id)->exists()
            && !Deal::where('school_id', $school->id)->exists();
    }
}