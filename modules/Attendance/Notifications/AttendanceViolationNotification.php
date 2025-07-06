<?php

namespace Modules\Attendance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Attendance\Models\AttendanceViolation;

class AttendanceViolationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var AttendanceViolation
     */
    protected $violation;

    /**
     * @var string
     */
    protected $notificationType;

    /**
     * Create a new notification instance.
     *
     * @param AttendanceViolation $violation
     * @param string $notificationType The type of notification (new, escalated, resolved)
     */
    public function __construct(AttendanceViolation $violation, string $notificationType = 'new')
    {
        $this->violation = $violation;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Default channels are database and broadcast for real-time notifications
        $channels = ['database', 'broadcast'];
        
        // For high severity violations, also send email
        if ($this->violation->severity >= 3 || $this->notificationType === 'escalated') {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $user = $this->violation->attendance->user;
        $subject = "[{$this->getViolationSeverityText()}] Attendance Violation";
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a notification about an attendance violation.")
            ->line("Employee: {$user->name}")
            ->line("Violation Type: {$this->violation->type}")
            ->line("Severity: {$this->getViolationSeverityText()}")
            ->line("Date: {$this->violation->created_at->format('Y-m-d H:i')}")
            ->line("Description: {$this->violation->description}")
            ->action('View Details', url("/attendance/violations/{$this->violation->id}"))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $user = $this->violation->attendance->user;
        
        return [
            'id' => $this->violation->id,
            'type' => 'attendance_violation',
            'notification_type' => $this->notificationType,
            'severity' => $this->violation->severity,
            'severity_text' => $this->getViolationSeverityText(),
            'violation_type' => $this->violation->type,
            'description' => $this->violation->description,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'attendance_id' => $this->violation->attendance_id,
            'created_at' => $this->violation->created_at->toIso8601String(),
            'company_id' => $this->violation->attendance->company_id,
            'branch_id' => $this->violation->attendance->branch_id ?? null,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'notification' => $this->toArray($notifiable),
            'notifiable' => [
                'id' => $notifiable->id,
                'type' => get_class($notifiable),
            ]
        ]);
    }
    
    /**
     * Get the notification's broadcast channels.
     *
     * @return array
     */
    public function broadcastOn()
    {
        // Private channel for the manager
        return ['private-user.' . $this->notifiable->id];
    }
    
    /**
     * Get readable text representation of violation severity
     * 
     * @return string
     */
    protected function getViolationSeverityText(): string
    {
        switch ($this->violation->severity) {
            case 1:
                return 'Low';
            case 2:
                return 'Medium';
            case 3:
                return 'High';
            case 4:
                return 'Critical';
            default:
                return 'Unknown';
        }
    }
}
