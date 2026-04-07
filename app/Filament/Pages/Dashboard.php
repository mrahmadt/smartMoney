<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvalidSMSBanner;
use App\Filament\Widgets\PendingCategoryReviewsWidget;
use App\Filament\Widgets\PinnedAlertsBanner;
use App\Filament\Widgets\WebPushPrompt;
use App\Models\Alert;
use App\Models\SMS;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected ?string $heading = null;

    protected ?string $subheading = null;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.dashboard');
    }

    public function getTitle(): string
    {
        return '';
    }

    protected function hasPageHeader(): bool
    {
        return false;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getHeaderWidgets(): array
    {
        $widgets = [];

        $widgets[] = WebPushPrompt::class;

        if (Auth::id() === 1) {
            $invalidCount = SMS::where('is_valid', false)->count();
            if ($invalidCount > 0) {
                $widgets[] = InvalidSMSBanner::class;
            }
        }

        if (Alert::where('user_id', Auth::id())->where('is_pinned', true)->where('is_read', false)->exists()) {
            $widgets[] = PinnedAlertsBanner::class;
        }

        if (PendingCategoryReviewsWidget::canView()) {
            $widgets[] = PendingCategoryReviewsWidget::class;
        }

        return $widgets;
    }

    public function getWidgets(): array
    {
        $user = Auth::user();
        $enabledKeys = $user->dashboard_widgets ?? User::DEFAULT_DASHBOARD_WIDGETS;
        $widgetMap = UserSettings::WIDGET_MAP;

        $allowed = [];
        foreach ($enabledKeys as $key) {
            if (isset($widgetMap[$key])) {
                $allowed[] = $widgetMap[$key]['class'];
            }
        }

        return collect(parent::getWidgets())
            ->filter(fn ($widget) => in_array($widget, $allowed))
            ->values()
            ->all();
    }
}
