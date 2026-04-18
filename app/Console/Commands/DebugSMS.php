<?php

namespace App\Console\Commands;

use App\Jobs\parseSMSJob;
use App\Models\SMS;
use App\Models\SMSSender;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

class DebugSMS extends Command
{
    protected $signature = 'sms:debug';

    protected $description = 'Debug SMS parsing without creating a transaction';

    public function handle(): int
    {
        $senders = SMSSender::where('is_active', true)->pluck('sender')->toArray();
        if (empty($senders)) {
            $this->error('No active senders found.');

            return self::FAILURE;
        }

        $sender = select(label: 'Select sender', options: $senders);
        $message = textarea(label: 'Paste SMS message', required: true);

        $this->newLine();
        $this->runDebug($sender, $message);

        return self::SUCCESS;
    }

    protected function runDebug(string $sender, string $message): void
    {
        $sms = new SMS;
        $sms->sender = strtolower($sender);
        $sms->message = SMS::removeHiddenChars($message);
        $sms->content = ['query' => ['sender' => $sender, 'message' => ['text' => $message]]];

        $job = new parseSMSJob($sms, dryRun: true);
        $job->handle();

        $this->renderOutput($job->dryRunOutput);
    }

    protected function renderOutput(array $output): void
    {
        foreach ($output as $entry) {
            match ($entry['type']) {
                'error' => $this->error('✗ '.$entry['message']),
                'info' => $this->line('<fg=green>✓</> '.$entry['message']),
                'transaction' => $this->renderTransaction($entry['data']),
                default => $this->line($entry['message']),
            };

            if ($entry['type'] === 'info' && ! empty($entry['data'])) {
                $this->table(
                    ['Field', 'Value'],
                    collect($entry['data'])
                        ->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : (string) $v])
                        ->values()
                        ->toArray()
                );
            }
        }
    }

    protected function renderTransaction(array $transaction): void
    {
        $this->newLine();
        $this->info('━━━ Parsed Transaction ━━━');
        $this->table(
            ['Field', 'Value'],
            collect($transaction)
                ->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : (string) $v])
                ->values()
                ->toArray()
        );
    }
}
