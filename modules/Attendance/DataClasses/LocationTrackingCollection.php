<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use Carbon\Carbon;
use InvalidArgumentException;
use Iterator;
use Countable;

/**
 * Data class representing a collection of location tracking points
 * 
 * This class manages multiple LocationTrackingPoint instances and provides
 * utility methods for analyzing location patterns, calculating time spent
 * in/out of radius, and detecting movement patterns.
 */
class LocationTrackingCollection implements Iterator, Countable
{
    /** @var LocationTrackingPoint[] */
    private array $points = [];
    private int $position = 0;

    public function __construct(array $points = [])
    {
        foreach ($points as $point) {
            $this->addPoint($point);
        }
        $this->sortByTimestamp();
    }

    /**
     * Create from array of tracking data
     */
    public static function fromArray(array $data): self
    {
        $points = [];
        foreach ($data as $pointData) {
            $points[] = LocationTrackingPoint::fromArray($pointData);
        }
        return new self($points);
    }

    /**
     * Add a tracking point to the collection
     */
    public function addPoint(LocationTrackingPoint $point): self
    {
        $this->points[] = $point;
        $this->sortByTimestamp();
        return $this;
    }

    /**
     * Get all points as array
     */
    public function toArray(): array
    {
        return array_map(fn($point) => $point->toArray(), $this->points);
    }

    /**
     * Get points within a specific time range
     */
    public function getPointsInTimeRange(Carbon $start, Carbon $end): self
    {
        $filteredPoints = array_filter($this->points, function ($point) use ($start, $end) {
            return $point->timestamp->between($start, $end);
        });

        return new self(array_values($filteredPoints));
    }

    /**
     * Get points within radius of given coordinates
     */
    public function getPointsWithinRadius(float $lat, float $lon, float $radiusMeters): self
    {
        $filteredPoints = array_filter($this->points, function ($point) use ($lat, $lon, $radiusMeters) {
            return $point->isWithinRadius($lat, $lon, $radiusMeters);
        });

        return new self(array_values($filteredPoints));
    }

    /**
     * Get points outside radius of given coordinates
     */
    public function getPointsOutsideRadius(float $lat, float $lon, float $radiusMeters): self
    {
        $filteredPoints = array_filter($this->points, function ($point) use ($lat, $lon, $radiusMeters) {
            return !$point->isWithinRadius($lat, $lon, $radiusMeters);
        });

        return new self(array_values($filteredPoints));
    }

    /**
     * Calculate total time spent outside radius in minutes
     */
    public function calculateTimeOutsideRadius(float $lat, float $lon, float $radiusMeters): int
    {
        $outsidePoints = $this->getPointsOutsideRadius($lat, $lon, $radiusMeters);
        
        if ($outsidePoints->count() === 0) {
            return 0;
        }

        $totalMinutes = 0;
        $previousPoint = null;
        $currentlyOutside = false;
        $outsideStartTime = null;

        foreach ($this->points as $point) {
            $isOutside = !$point->isWithinRadius($lat, $lon, $radiusMeters);

            if ($isOutside && !$currentlyOutside) {
                // Started being outside
                $currentlyOutside = true;
                $outsideStartTime = $point->timestamp;
            } elseif (!$isOutside && $currentlyOutside) {
                // Came back inside
                $currentlyOutside = false;
                if ($outsideStartTime) {
                    $totalMinutes += $outsideStartTime->diffInMinutes($point->timestamp);
                }
                $outsideStartTime = null;
            }

            $previousPoint = $point;
        }

        // If still outside at the end
        if ($currentlyOutside && $outsideStartTime && $previousPoint) {
            $totalMinutes += $outsideStartTime->diffInMinutes($previousPoint->timestamp);
        }

        return (int) round($totalMinutes);
    }

    /**
     * Get the first tracking point
     */
    public function getFirst(): ?LocationTrackingPoint
    {
        return $this->points[0] ?? null;
    }

    /**
     * Get the last tracking point
     */
    public function getLast(): ?LocationTrackingPoint
    {
        return end($this->points) ?: null;
    }

    /**
     * Get points with low battery
     */
    public function getPointsWithLowBattery(int $threshold = 20): self
    {
        $filteredPoints = array_filter($this->points, function ($point) use ($threshold) {
            return $point->hasLowBattery($threshold);
        });

        return new self(array_values($filteredPoints));
    }

    /**
     * Get points with poor accuracy
     */
    public function getPointsWithPoorAccuracy(float $maxAccuracy = 10.0): self
    {
        $filteredPoints = array_filter($this->points, function ($point) use ($maxAccuracy) {
            return !$point->hasAcceptableAccuracy($maxAccuracy);
        });

        return new self(array_values($filteredPoints));
    }

    /**
     * Get unique device IDs in the collection
     */
    public function getUniqueDeviceIds(): array
    {
        $deviceIds = array_map(fn($point) => $point->deviceId, $this->points);
        return array_unique($deviceIds);
    }

    /**
     * Check if collection has multiple devices
     */
    public function hasMultipleDevices(): bool
    {
        return count($this->getUniqueDeviceIds()) > 1;
    }

    /**
     * Get average accuracy across all points
     */
    public function getAverageAccuracy(): float
    {
        if (empty($this->points)) {
            return 0.0;
        }

        $totalAccuracy = array_sum(array_map(fn($point) => $point->accuracy, $this->points));
        return $totalAccuracy / count($this->points);
    }

    /**
     * Get time span of the tracking session in minutes
     */
    public function getTimeSpanInMinutes(): int
    {
        if (count($this->points) < 2) {
            return 0;
        }

        $first = $this->getFirst();
        $last = $this->getLast();

        return (int) round($first->timestamp->diffInMinutes($last->timestamp));
    }

    /**
     * Detect if there are significant gaps in tracking
     */
    public function hasTrackingGaps(int $maxGapMinutes = 30): bool
    {
        if (count($this->points) < 2) {
            return false;
        }

        for ($i = 1; $i < count($this->points); $i++) {
            $timeDiff = $this->points[$i-1]->timeDifferenceInMinutes($this->points[$i]);
            if ($timeDiff > $maxGapMinutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sort points by timestamp
     */
    private function sortByTimestamp(): void
    {
        usort($this->points, function ($a, $b) {
            return $a->timestamp->timestamp <=> $b->timestamp->timestamp;
        });
    }

    // Iterator interface implementation
    public function current(): LocationTrackingPoint
    {
        return $this->points[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->points[$this->position]);
    }

    // Countable interface implementation
    public function count(): int
    {
        return count($this->points);
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->points);
    }

    /**
     * Get all points as array (for backward compatibility)
     */
    public function getPoints(): array
    {
        return $this->points;
    }
}
