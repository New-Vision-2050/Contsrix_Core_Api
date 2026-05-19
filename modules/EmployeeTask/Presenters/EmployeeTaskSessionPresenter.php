<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\EmployeeTask\Models\EmployeeTaskSession;

final class EmployeeTaskSessionPresenter
{
    public function __construct(private readonly EmployeeTaskSession $session) {}

    public function toArray(): array
    {
        $s = $this->session;

        return [
            'id'               => $s->id,
            'start_time'       => $s->start_time?->format('Y-m-d H:i:s'),
            'end_time'         => $s->end_time?->format('Y-m-d H:i:s'),
            'duration_minutes' => $s->duration_minutes,
            'source'           => $s->source,
            'start_location'   => ($s->start_latitude && $s->start_longitude)
                ? ['latitude' => (float) $s->start_latitude, 'longitude' => (float) $s->start_longitude]
                : null,
            'end_location'     => ($s->end_latitude && $s->end_longitude)
                ? ['latitude' => (float) $s->end_latitude, 'longitude' => (float) $s->end_longitude]
                : null,
            'notes'            => $s->notes,
        ];
    }

    public static function collection(iterable $sessions): array
    {
        $result = [];
        foreach ($sessions as $session) {
            $result[] = (new self($session))->toArray();
        }
        return $result;
    }
}
