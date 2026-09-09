<?php

declare(strict_types=1);

namespace Modules\Attendance\Exceptions;

use App\Exceptions\CustomException;

class AttendanceException extends CustomException
{
    /** @var array<int, array{type: ?string, severity: ?string, message: ?string}> */
    private array $violations = [];

    /**
     * Thrown when a clock-in is blocked by constraint violations.
     * Exception message is always a plain readable string — never JSON / nested objects.
     *
     * @param array<int|string, mixed> $violations
     */
    public static function clockInBlocked(array $violations): self
    {
        $list = self::normalizeViolationList($violations);
        $message = self::firstReadableMessage($list)
            ?? 'Clock-in blocked due to constraint violations';

        $instance = new self($message, 422);
        // Keep only slim fields so nothing dumps window/details payloads by accident.
        $instance->violations = array_map(
            static fn (array $v): array => [
                'type' => isset($v['type']) && is_string($v['type'])
                    ? $v['type']
                    : (isset($v['constraint_type']) && is_string($v['constraint_type']) ? $v['constraint_type'] : null),
                'severity' => isset($v['severity']) && is_scalar($v['severity']) ? (string) $v['severity'] : null,
                'message' => isset($v['message']) && is_string($v['message']) ? $v['message'] : null,
            ],
            $list
        );

        return $instance;
    }

    /** @return array<int, array{type: ?string, severity: ?string, message: ?string}> */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param array<int|string, mixed> $violations
     * @return list<array<string, mixed>>
     */
    private static function normalizeViolationList(array $violations): array
    {
        if ($violations === []) {
            return [];
        }

        // Single associative violation returned instead of a list.
        if (!array_is_list($violations) && (
            isset($violations['message'])
            || isset($violations['constraint_type'])
            || isset($violations['type'])
        )) {
            return [$violations];
        }

        $out = [];
        foreach ($violations as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Prefer the most specific human-readable string available.
     *
     * @param list<array<string, mixed>> $list
     */
    private static function firstReadableMessage(array $list): ?string
    {
        foreach ($list as $violation) {
            $nested = $violation['details']['violations'] ?? null;
            if (is_array($nested)) {
                foreach ($nested as $inner) {
                    if (is_array($inner) && is_string($inner['message'] ?? null)) {
                        $text = trim($inner['message']);
                        if ($text !== '') {
                            return $text;
                        }
                    }
                }
            }

            if (is_string($violation['message'] ?? null)) {
                $text = trim($violation['message']);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    public static function alreadyClockedIn(): self
    {
        return new self('You are already clocked in. Please clock out first.', 400);
    }

    public static function notClockedIn(): self
    {
        return new self('You are not currently clocked in.', 400);
    }

    public static function alreadyClockedOut(): self
    {
        return new self('You have already clocked out for today.', 400);
    }

    public static function onBreak(): self
    {
        return new self('You are currently on break. Please end your break first.', 400);
    }

    public static function notOnBreak(): self
    {
        return new self('You are not currently on break.', 400);
    }

    public static function alreadyOnBreak(): self
    {
        return new self('You are already on break. Please end your current break first.', 400);
    }

    public static function attendanceNotFound(): self
    {
        return new self('Attendance record not found.', 404);
    }

    public static function cannotModifyPastAttendance(): self
    {
        return new self('Cannot modify attendance records from previous days.', 403);
    }

    public static function invalidClockOutTime(): self
    {
        return new self('Clock out time cannot be before clock in time.', 400);
    }

    public static function attendanceAlreadyApproved(): self
    {
        return new self('This attendance record has already been approved.', 400);
    }

    public static function unauthorizedToApprove(): self
    {
        return new self('You are not authorized to approve this attendance record.', 403);
    }

    public static function cannotDeleteApprovedAttendance(): self
    {
        return new self('Cannot delete approved attendance records.', 403);
    }

    /**
     * Thrown when a user tries to reject an attendance record that is already approved.
     */
    public static function cannotRejectApprovedAttendance(): self
    {
        return new self('Cannot reject an attendance record that has already been approved.', 409);
    }

    /**
     * Thrown when a user is not found.
     */
    public static function userNotFound(): self
    {
        return new self('User not found.', 404);
    }

    /**
     * Thrown when updating/deleting a constraint while linked employees still have an open shift.
     */
    public static function cannotModifyConstraintWithOpenAttendance(): self
    {
        return new self(
            'Cannot modify this attendance constraint while any linked employee is still clocked in (clock out is pending).',
            409
        );
    }

    /**
     * Thrown when the employee has no profile photo on file to compare against.
     */
    public static function noProfilePhoto(): self
    {
        return new self(
            'No profile photo found on your account. Please upload a profile photo before using face verification.',
            422
        );
    }

    /**
     * Thrown when the captured photo does not match the stored profile photo closely enough.
     */
    public static function faceNotMatched(?float $similarity = null): self
    {
        $message = $similarity !== null
            ? sprintf('Face verification failed. The captured photo does not match your profile photo (similarity: %.1f%%).', $similarity)
            : 'Face verification failed. The captured photo does not match your profile photo.';

        return new self($message, 422);
    }

    /**
     * Thrown when AWS Rekognition cannot detect a comparable face in either image.
     */
    public static function faceNotDetected(): self
    {
        return new self(
            'No face could be detected in the photo. Please retake the photo with your face clearly visible.',
            422
        );
    }

    /**
     * Thrown when the face verification credentials are missing/misconfigured.
     */
    public static function faceVerificationMisconfigured(): self
    {
        return new self('Face verification is not configured on the server.', 500);
    }

    /**
     * Thrown when the AWS Rekognition call fails for a reason other than face detection.
     */
    public static function faceVerificationFailed(string $reason): self
    {
        return new self('Face verification failed: ' . $reason, 502);
    }
}
