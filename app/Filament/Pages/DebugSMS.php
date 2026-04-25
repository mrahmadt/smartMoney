<?php

namespace App\Filament\Pages;

use App\Jobs\parseSMSJob;
use App\Models\SMS;
use App\Models\SMSSender;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class DebugSMS extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.debug-sms';

    public ?array $data = [];

    public ?array $results = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.config');
    }

    public static function getNavigationLabel(): string
    {
        return 'Debug SMS';
    }

    public function getTitle(): string
    {
        return 'Debug SMS';
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Select::make('sender')
                        ->label(__('menu.sender'))
                        ->options(SMSSender::where('is_active', true)->pluck('sender', 'sender'))
                        ->searchable()
                        ->required(),
                    Textarea::make('message')
                        ->label(__('widget.message'))
                        ->required()
                        ->rows(8)
                        ->extraInputAttributes(fn ($state) => array_merge(
                            [
                                'x-on:input' => "if (/[\\u0600-\\u06FF\\u0750-\\u077F\\u08A0-\\u08FF\\uFB50-\\uFDFF\\uFE70-\\uFEFF\\u0590-\\u05FF]/.test(\$el.value)) { \$el.dir = 'rtl'; \$el.style.textAlign = 'right'; } else { \$el.dir = 'ltr'; \$el.style.textAlign = 'left'; }",
                            ],
                            preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $state ?? '')
                                ? ['dir' => 'rtl', 'style' => 'text-align: right;']
                                : [],
                        )),
                ])
                    ->livewireSubmitHandler('parse')
                    ->footer([
                        SchemaActions::make([
                            Action::make('parse')
                                ->label('Parse')
                                ->submit('parse'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function parse(): void
    {
        $state = $this->form->getState();

        $sms = new SMS;
        $sms->sender = strtolower($state['sender']);
        $originalMessage = SMS::removeHiddenChars($state['message']);
        $smsSender = SMSSender::where('sender', $sms->sender)->where('is_active', true)->first();
        $sms->message = SMS::preClean($originalMessage, $smsSender?->id);
        $sms->content = ['query' => ['sender' => $state['sender'], 'message' => ['text' => $state['message']]]];
        $job = new parseSMSJob($sms, dryRun: true);
        $job->handle();

        $this->results = $job->dryRunOutput;
        if ($originalMessage !== $sms->message) {
            array_unshift($this->results, ['type' => 'info', 'message' => 'Cleaned message: '.$sms->message]);
        }
    }
}
