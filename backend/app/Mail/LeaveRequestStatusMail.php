<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class LeaveRequestStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public LeaveRequest $leave;
    public string $context;

    /**
     * Create a new message instance.
     */
    public function __construct(LeaveRequest $leave, string $context)
    {
        $this->leave = $leave;
        $this->context = $context;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->context === 'status_update'
            ? 'Cap nhat don nghi'
            : 'Don nghi moi can duyet';

        return $this->subject($subject)
            ->view('emails.leave_status')
            ->with([
                'leave' => $this->leave,
                'context' => $this->context,
            ]);
    }
}
