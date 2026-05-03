<?php

declare(strict_types=1);

namespace Modules\SubEntity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SubEntity\Models\SubEntity;

class UpdateSubEntityAttributesSeeder extends Seeder
{
    private array $defaultAttributes = [
        'name',
        'email',
        'phone',
        'data_status',
        'branch',
        'job_title',
    ];

    private array $optionalAttributes = [
        'phone_code',
        'user-type',
        'companies',
        'nickname',
        'birthdate_gregorian',
        'birthdate_hijri',
        'nationality',
        'residence',
        'address',
        'postal_code',
        'landline_number',
        'other_phone',
        'marital-status',
        'management',
        'department',
        'job_type',
        'job_code',
        'attendance_constraint',
        'identity',
        'passport',
        'border_number',
        'work_permit',
        'whatsapp',
        'linkedin',
        'facebook',
        'instagram',
        'telegram',
        'snapchat',
        'currency',
        'time_zone',
        'language',
        'bank-info',
        'salary-info',
        'employment-info',
        'contact-info',
        'social-media',
        'family-info',
        'about-me',
        'cv',
        'certificates',
        'qualification',
        'experience',
        'courses',
        'work-license',
        'privileges',
        'official-data',
        'job-offer',
        'contract-work',
        'education',
        'passport-info',
        'residence-info',
        'broker',
        'number_of_projects',
        'end_date',
    ];

    public function run(): void
    {
        SubEntity::where('super_entity', 'users')
            ->orWhereNull('super_entity')
            ->each(function (SubEntity $subEntity) {
                $existing = $subEntity->default_attributes ?? [];

                $subEntity->update([
                    'default_attributes'  => array_values(array_unique(array_merge($existing, $this->defaultAttributes))),
                    'optional_attributes' => $this->optionalAttributes,
                ]);
            });
    }
}
