<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
        protected ?string $heading = null;
    protected ?string $subheading = null;

    public function getTitle(): string
    {
        return '';
    }

    protected function hasPageHeader(): bool
    {
        return false;
    }

}
