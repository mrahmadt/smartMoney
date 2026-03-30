<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Alerts\AlertResource;
use App\Models\Alert;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PinnedAlertsBanner extends Widget
{
    protected string $view = 'filament.widgets.pinned-alerts-banner';

    protected int | string | array $columnSpan = 'full';

    public array $alerts = [];

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        $this->alerts = Alert::where('user_id', Auth::id())
            ->where('is_pinned', true)
            ->where('is_read', false)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($alert) => [
                'id' => $alert->id,
                'title' => $alert->title,
                'message' => $alert->message,
                'url' => AlertResource::getUrl('view', ['record' => $alert->id]),
            ])
            ->toArray();
    }

    public function dismiss(int $id): void
    {
        Alert::where('id', $id)->where('user_id', Auth::id())->update(['is_pinned' => false]);
        $this->alerts = array_values(array_filter($this->alerts, fn ($a) => $a['id'] !== $id));
    }
}
