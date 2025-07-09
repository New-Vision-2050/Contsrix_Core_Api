<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;

/**
 * Base class for all constraint services with common utility methods.
 */
abstract class BaseConstraintService
{
    /**
     * Extract severity level from constraint configuration.
     * 
     * @param array $config The constraint configuration
     * @return string The severity level (high, medium, low)
     */
    protected function getSeverityFromConfig(array $config): string
    {
        // Get severity from config or default to medium
        return $config['severity'] ?? 'medium';
    }
    
    /**
     * Convert HH:MM time string to minutes since midnight.
     * 
     * @param string $timeString Time string in HH:MM format
     * @return int Minutes since midnight
     */
    protected function timeToMinutes(string $timeString): int
    {
        if (!preg_match('/^(\d{2}):(\d{2})$/', $timeString, $parts)) {
            // Return 0 or throw an exception for invalid format
            return 0;
        }
        return (int)$parts[1] * 60 + (int)$parts[2];
    }
    
    /**
     * Check if a time is within a given range.
     * 
     * @param string $time Time to check in HH:MM format
     * @param string $start Start time in HH:MM format
     * @param string $end End time in HH:MM format
     * @return bool True if time is within range, false otherwise
     */
    protected function isTimeWithinRange(string $time, string $start, string $end): bool
    {
        $timeMinutes = $this->timeToMinutes($time);
        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);
        
        // Handle overnight ranges (where end time is earlier than start time)
        if ($endMinutes < $startMinutes) {
            return $timeMinutes >= $startMinutes || $timeMinutes <= $endMinutes;
        }
        
        return $timeMinutes >= $startMinutes && $timeMinutes <= $endMinutes;
    }
}
