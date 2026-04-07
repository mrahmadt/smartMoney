<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentTransactions;
use App\Filament\Widgets\SpendingCategoriesChart;
use App\Filament\Widgets\SpendingChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopCategories;
use App\Filament\Widgets\TopMerchants;
use App\Filament\Widgets\TopTransactions;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.user-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.settings');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public const WIDGET_MAP = [
        'stats_overview' => [
            'class' => StatsOverview::class,
            'label' => 'settings.widget_stats_overview',
            'description' => 'settings.widget_stats_overview_desc',
        ],
        'recent_transactions' => [
            'class' => RecentTransactions::class,
            'label' => 'settings.widget_recent_transactions',
            'description' => 'settings.widget_recent_transactions_desc',
        ],
        'top_transactions' => [
            'class' => TopTransactions::class,
            'label' => 'settings.widget_top_transactions',
            'description' => 'settings.widget_top_transactions_desc',
        ],
        'top_categories' => [
            'class' => TopCategories::class,
            'label' => 'settings.widget_top_categories',
            'description' => 'settings.widget_top_categories_desc',
        ],
        'spending_chart' => [
            'class' => SpendingChart::class,
            'label' => 'settings.widget_spending_chart',
            'description' => 'settings.widget_spending_chart_desc',
        ],
        'top_merchants' => [
            'class' => TopMerchants::class,
            'label' => 'settings.widget_top_merchants',
            'description' => 'settings.widget_top_merchants_desc',
        ],
        'spending_categories_chart' => [
            'class' => SpendingCategoriesChart::class,
            'label' => 'settings.widget_spending_categories_chart',
            'description' => 'settings.widget_spending_categories_chart_desc',
        ],
    ];

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $user = Auth::user();
        $enabledWidgets = $user->dashboard_widgets ?? User::DEFAULT_DASHBOARD_WIDGETS;

        $widgetData = [];
        foreach (self::WIDGET_MAP as $key => $config) {
            $widgetData["widget_{$key}"] = in_array($key, $enabledWidgets);
        }

        $this->form->fill(array_merge([
            'language' => $user->language ?? 'en',
            'alert_via_email' => $user->alert_via_email ?? false,
            'current_password' => '',
            'new_password' => '',
            'new_password_confirmation' => '',
        ], $widgetData));
    }

    public function form(Schema $schema): Schema
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $widgetCheckboxes = [];
        foreach (self::WIDGET_MAP as $key => $config) {
            $widgetCheckboxes[] = Checkbox::make("widget_{$key}")
                ->label(__($config['label']))
                ->helperText(__($config['description']));
        }

        return $schema
            ->components([
                Form::make([
                    Section::make(__('settings.general'))
                        ->schema([
                            Select::make('language')
                                ->label(__('settings.language'))
                                ->options([
                                    'en' => 'English',
                                    'ar' => 'العربية',
                                ])
                                ->required(),
                            Toggle::make('alert_via_email')
                                ->label(__('settings.alert_via_email')),
                        ]),

                    Section::make(__('settings.change_password'))
                        ->schema([
                            TextInput::make('current_password')
                                ->label(__('settings.current_password'))
                                ->password()
                                ->revealable()
                                ->autocomplete('current-password'),
                            TextInput::make('new_password')
                                ->label(__('settings.new_password'))
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->autocomplete('new-password'),
                            TextInput::make('new_password_confirmation')
                                ->label(__('settings.confirm_password'))
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password'),
                        ]),

                    Section::make(__('settings.dashboard_widgets'))
                        ->description(__('settings.dashboard_widgets_desc'))
                        ->schema($widgetCheckboxes),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        SchemaActions::make([
                            Action::make('save')
                                ->label(__('widget.save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Auth::user();

        // Handle password change
        if (! empty($state['new_password'])) {
            if (empty($state['current_password'])) {
                throw ValidationException::withMessages([
                    'data.current_password' => __('settings.current_password_required'),
                ]);
            }

            if (! Hash::check($state['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'data.current_password' => __('settings.current_password_incorrect'),
                ]);
            }

            if ($state['new_password'] !== $state['new_password_confirmation']) {
                throw ValidationException::withMessages([
                    'data.new_password_confirmation' => __('settings.password_confirmation_mismatch'),
                ]);
            }

            $user->update(['password' => $state['new_password']]);
        }

        // Collect enabled widgets
        $enabledWidgets = [];
        foreach (self::WIDGET_MAP as $key => $config) {
            if (! empty($state["widget_{$key}"])) {
                $enabledWidgets[] = $key;
            }
        }

        $user->update([
            'language' => $state['language'],
            'alert_via_email' => $state['alert_via_email'],
            'dashboard_widgets' => $enabledWidgets,
        ]);

        Notification::make()
            ->success()
            ->title(__('widget.saved'))
            ->send();
    }
}
