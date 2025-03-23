<?php

declare(strict_types=1);

namespace Modules\Tenant\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Models\Tenant;

class TenantWelcomeNotification extends Notification
{
    use Queueable;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $temporaryPassword;
    
    /**
     * @var string
     */
    protected $role;

    /**
     * Create a new notification instance.
     *
     * @param Tenant $tenant
     * @param string $temporaryPassword
     * @param string $role
     */
    public function __construct(Tenant $tenant, string $temporaryPassword, string $role = 'user')
    {
        $this->tenant = $tenant;
        $this->temporaryPassword = $temporaryPassword;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param CompanyUser $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Get the tenant domain
        $domain = 'your-tenant-domain.com';
        foreach ($this->tenant->domains as $tenantDomain) {
            $domain = $tenantDomain->domain;
            break;
        }
        
        $tenantUrl = "https://{$domain}";
        $loginUrl = "{$tenantUrl}/login";
        
        // Get the company name
        $companyName = $this->tenant->company ? $this->tenant->company->name : 'Your Company';
        
        return (new MailMessage)
            ->subject("Welcome to {$companyName}'s Tenant Portal")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Welcome to {$companyName}'s tenant portal. Your account has been created successfully.")
            ->line("You can access your tenant portal at: {$tenantUrl}")
            ->line("Here are your login credentials:")
            ->line("Email: {$notifiable->email}")
            ->line("Temporary Password: {$this->temporaryPassword}")
            ->line("Role: {$this->role}")
            ->line("Please change your password after your first login for security reasons.")
            ->action('Login Now', $loginUrl)
            ->line('If you have any questions, please contact your administrator.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'company_name' => $this->tenant->company ? $this->tenant->company->name : null,
        ];
    }
}