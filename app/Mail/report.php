<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class report extends Mailable
{
    use Queueable, SerializesModels;


    public $subject;
    public $remaining;
    public $budget_percentage_used;
    public $spentSum;
    public $tillText;
    public $remainingDays;
    /**
     * Create a new message instance.
     */
    public function __construct(
        public $budget,
        )
    {

        if(isset($this->budget->attributes->spent[0])) {
            $this->remaining = number_format($this->budget->attributes->auto_budget_amount+$this->budget->attributes->spent[0]->sum,0);
            $this->budget_percentage_used = number_format(abs(($this->budget->attributes->spent[0]->sum/$this->budget->attributes->auto_budget_amount)*100),0);
            $this->spentSum = number_format($this->budget->attributes->spent[0]->sum,0);
        
        }else{
            $this->remaining = number_format($this->budget->attributes->auto_budget_amount,0);
            $this->budget_percentage_used = 0;
            $this->spentSum = 0;
        }
        $auto_budget_period = $this->budget->attributes->auto_budget_period;

        $this->tillText = __('report.tillnextperiod');
        $this->remainingDays = null;
        if($auto_budget_period=='monthly'){
            $this->tillText = __('report.tillnextmonth');
            // Starting from 1st of the month, how many days remiaing
            $this->remainingDays = date('t') - date('j');
        }elseif($auto_budget_period=='weekly'){
            $this->tillText = __('report.tillnextweek');
            // starting from Monday, how many days remaining
            $this->remainingDays = 7 - date('N');
        }elseif($auto_budget_period=='quarterly'){
            $this->tillText = __('report.tillnextquarter');
            // starting from 1st of the quarter, how many days remaining
            // Calculate the start of the next quarter
            $currentMonth = date('n');
            $currentQuarter = ceil($currentMonth / 3);
            $nextQuarterStartMonth = ($currentQuarter * 3) + 1;
            $nextQuarterStartYear = date('Y');
            if ($nextQuarterStartMonth > 12) {
                $nextQuarterStartMonth = 1;
                $nextQuarterStartYear++;
            }
            $nextQuarterStartDate = new \DateTime("$nextQuarterStartYear-$nextQuarterStartMonth-01");
            $currentDate = new \DateTime();
            $this->remainingDays = $currentDate->diff($nextQuarterStartDate)->days;
        }elseif($auto_budget_period=='yearly'){
            $this->tillText = __('report.tillnextyear');
            // starting from 1st of the year, how many days remaining
            $nextYearStartDate = new \DateTime((date('Y') + 1) . '-01-01');
            $currentDate = new \DateTime();
            $this->remainingDays = $currentDate->diff($nextYearStartDate)->days;
        }

        
        $this->subject = __('report.emailsubject', [
            'spentSum' => $this->spentSum,
            'budget_percentage_used' => $this->budget_percentage_used,
            'budgetName' => $this->budget->attributes->name
        ]);

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
