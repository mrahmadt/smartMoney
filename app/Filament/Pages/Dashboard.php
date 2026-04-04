<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SMS\SMSResource;
use App\Models\SMS;
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

        $widgets[] = \App\Filament\Widgets\WebPushPrompt::class;

        if (Auth::id() === 1) {
            $invalidCount = SMS::where('is_valid', false)->count();
            if ($invalidCount > 0) {
                $widgets[] = \App\Filament\Widgets\InvalidSMSBanner::class;
            }
        }

        if (\App\Models\Alert::where('user_id', Auth::id())->where('is_pinned', true)->where('is_read', false)->exists()) {
            $widgets[] = \App\Filament\Widgets\PinnedAlertsBanner::class;
        }

        if (\App\Filament\Widgets\PendingCategoryReviewsWidget::canView()) {
            $widgets[] = \App\Filament\Widgets\PendingCategoryReviewsWidget::class;
        }

        return $widgets;
    }
}
