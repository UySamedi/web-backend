<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentStatusNotification extends Notification
{
    use Queueable;

    public $status;
    public $courseTitle;

    public function __construct($status, $courseTitle)
    {
        $this->status = $status;
        $this->courseTitle = $courseTitle;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Send via email and store in DB
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Enrollment ' . ucfirst($this->status))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your enrollment for the course '{$this->courseTitle}' has been {$this->status}.")
            ->line('Thank you for using our University Enrollment System.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'course' => $this->courseTitle,
            'message' => "Your enrollment for '{$this->courseTitle}' has been {$this->status}."
        ];
    }
}
