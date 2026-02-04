<?php

declare(strict_types=1);

namespace Modules\Audit\Presenters;

use Modules\Audit\Models\Audit;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AuditPresenter extends AbstractPresenter
{
    private Audit $audit;

    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->audit->id,
            'user' => [
                'id' => $this->audit?->user?->id,
                'name' => $this->audit->user?->name,
                'email' => $this->audit->user?->email,
            ],
            'event' => $this->audit->event,
            'auditable_id' => $this->audit->auditable_id,
            'auditable_type' => $this->audit->auditable_type,
            'title' => $this->generateTitle(),
            'url' => $this->audit->url,
            'ip_address' => $this->audit->ip_address,
            'user_agent' => $this->audit->user_agent,
            'tags' => $this->audit->tags,
            'created_at' => $this->audit->created_at,
            'updated_at' => $this->audit->updated_at,
        ];
    }

    /**
     * Generate Arabic title based on auditable type and event
     */
    private function generateTitle(): string
    {
        if($this->audit->auditable_type == "Modules\\Attendance\\Models\\Attendance"){
            $auditableClass = $this->audit->auditable_type;

            $entity = $auditableClass::find($this->audit->auditable_id);
            $event = "خروج من الدوام";
            if($entity->clock_in_time != null && $entity->clock_out_time == null){
                $event = "دخول الدوام";
            }elseif ($entity->clock_in_time == null && $entity->clock_out_time == null){
                $event=" الغياب او الاجازه";
            }
            return "لقد سجل {$event}";
        }elseif ($this->audit->auditable_type == "Modules\\Company\\CompanyCore\\Models\\CompanyAddress"){
            $event = $this->audit->event;
            $message = "تم انشاء عنوان جديد للشركة";
            if($event=="created")
            {
                $message = " تم انشاء عنوان جديد للشركة";
            }elseif($event=="updated"){
                $message = "تم تعديل عنوان الشركة";
            }
            return $message;
        }
        $modelName = $this->getModelNameFromType($this->audit->auditable_type);
        $event = $this->audit->event;

        // Get Arabic event text
        $eventText = $this->getEventText($event);

        // Get Arabic model name
        $modelNameArabic = $this->getModelNameArabic($modelName);

        // Get actual name of the auditable entity
        $entityName = $this->getAuditableEntityName();

        return $eventText . $modelNameArabic . $entityName;
    }

    /**
     * Extract model name from full class path
     */
    private function getModelNameFromType(string $auditableType): string
    {
        $parts = explode('\\', $auditableType);
        return end($parts);
    }

    /**
     * Get Arabic event text based on event type
     */
    private function getEventText(string $event): string
    {
        $eventTexts = [
            'created' => 'لقد تم انشاء ',
            'updated' => 'لقد تم تعديل ',
            'deleted' => 'لقد تم حذف ',
            'restored' => 'لقد تم استرجاع ',
        ];

        return $eventTexts[$event] ?? 'لقد تم ' . $event . ' ';
    }

    /**
     * Get Arabic model name based on model type
     */
    private function getModelNameArabic(string $modelName): string
    {
        $modelNames = [
            'User' => 'مستخدم بأسم ',
            'Company' => 'شركة بأسم ',
            'Role' => 'دور بأسم ',
            'Permission' => 'صلاحية بأسم ',
            'Branch' => 'فرع بأسم ',
            'Department' => 'قسم بأسم ',
            'JobTitle' => 'مسمى وظيفي بأسم ',
            'Employee' => 'موظف بأسم ',
            'Client' => 'عميل بأسم ',
            'Broker' => 'وسيط بأسم ',
            'Folder' => 'مجلد بأسم ',
            'File' => 'ملف بأسم ',
            'AttendanceConstraint'=>"محدد جضور بأسم ",
            'Attendance'=>"حضور بأسم ",
            "ManagementHierarchy"=>"هيكل تنظيمي(فرع - اداره - قسم) باسم ",
            "CompanyAddress"=>"عنوان شركة بأسم ",


        ];

        return $modelNames[$modelName] ?? $modelName . ' بأسم ';
    }

    /**
     * Get the actual name of the auditable entity
     */
    private function getAuditableEntityName(): string
    {
        try {
            // Get the auditable model class
            $auditableClass = $this->audit->auditable_type;

            if (!class_exists($auditableClass)) {
                return 'غير معروف';
            }

            // Find the entity by ID
            $entity = $auditableClass::find($this->audit->auditable_id);

            if (!$entity) {
                return 'غير موجود';
            }

            // Try different name attributes
            if (isset($entity->name)) {
                return $entity->name;
            } elseif (isset($entity->title)) {
                return $entity->title;
            } elseif (isset($entity->constraint_name )) {
                return $entity->constraint_name ;
            } elseif (isset($entity->email)) {
                return $entity->email;
            } elseif (isset($entity->username)) {
                return $entity->username;
            } elseif (method_exists($entity, 'getDisplayName')) {
                return $entity->getDisplayName();
            } else {
                return 'ID: ' . $this->audit->auditable_id;
            }
        } catch (\Exception $e) {
            return 'غير متاح';
        }
    }
}
