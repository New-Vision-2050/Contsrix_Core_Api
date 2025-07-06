<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\AttendanceConstraint;
use Carbon\Carbon;

/**
 * Service for caching attendance constraints to improve validation performance
 */
class ConstraintCacheService
{
    /**
     * Cache key prefix for constraints
     */
    protected const CACHE_PREFIX = 'attendance_constraint:';
    
    /**
     * Cache key prefix for user constraints
     */
    protected const USER_CACHE_PREFIX = 'user_attendance_constraints:';
    
    /**
     * Default cache TTL in minutes
     */
    protected const DEFAULT_TTL = 60;

    /**
     * Cache a constraint
     *
     * @param AttendanceConstraint $constraint The constraint to cache
     * @param int|null $ttlMinutes Cache TTL in minutes (null for default)
     * @return bool Success status
     */
    public function cacheConstraint(AttendanceConstraint $constraint, ?int $ttlMinutes = null): bool
    {
        try {
            $key = $this->getConstraintCacheKey($constraint->id);
            $ttl = $ttlMinutes ?? $this->getConstraintTtl($constraint);
            
            Cache::put($key, $constraint, Carbon::now()->addMinutes($ttl));
            
            // Also cache in the user's constraint list if applicable
            if ($constraint->user_id) {
                $this->addToUserConstraintsList($constraint->user_id, $constraint->id);
            }
            
            // Cache in company constraint list
            $this->addToCompanyConstraintsList($constraint->company_id, $constraint->id);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache constraint', [
                'constraint_id' => $constraint->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get a cached constraint by ID
     *
     * @param string $constraintId Constraint ID
     * @return AttendanceConstraint|null The cached constraint or null if not found
     */
    public function getConstraint(string $constraintId): ?AttendanceConstraint
    {
        $key = $this->getConstraintCacheKey($constraintId);
        return Cache::get($key);
    }
    
    /**
     * Get all cached constraints for a user
     *
     * @param string $userId User ID
     * @return array Array of constraints
     */
    public function getUserConstraints(string $userId): array
    {
        $userKey = $this->getUserConstraintsCacheKey($userId);
        $constraintIds = Cache::get($userKey, []);
        
        $constraints = [];
        foreach ($constraintIds as $constraintId) {
            $constraint = $this->getConstraint($constraintId);
            if ($constraint) {
                $constraints[] = $constraint;
            }
        }
        
        return $constraints;
    }
    
    /**
     * Get all cached constraints for a company
     *
     * @param string $companyId Company ID
     * @return array Array of constraints
     */
    public function getCompanyConstraints(string $companyId): array
    {
        $companyKey = $this->getCompanyConstraintsCacheKey($companyId);
        $constraintIds = Cache::get($companyKey, []);
        
        $constraints = [];
        foreach ($constraintIds as $constraintId) {
            $constraint = $this->getConstraint($constraintId);
            if ($constraint) {
                $constraints[] = $constraint;
            }
        }
        
        return $constraints;
    }
    
    /**
     * Invalidate a cached constraint
     *
     * @param string $constraintId Constraint ID
     * @return bool Success status
     */
    public function invalidateConstraint(string $constraintId): bool
    {
        $key = $this->getConstraintCacheKey($constraintId);
        return Cache::forget($key);
    }
    
    /**
     * Invalidate all cached constraints for a user
     *
     * @param string $userId User ID
     * @return bool Success status
     */
    public function invalidateUserConstraints(string $userId): bool
    {
        $userKey = $this->getUserConstraintsCacheKey($userId);
        $constraintIds = Cache::get($userKey, []);
        
        foreach ($constraintIds as $constraintId) {
            $this->invalidateConstraint($constraintId);
        }
        
        return Cache::forget($userKey);
    }
    
    /**
     * Invalidate all cached constraints for a company
     *
     * @param string $companyId Company ID
     * @return bool Success status
     */
    public function invalidateCompanyConstraints(string $companyId): bool
    {
        $companyKey = $this->getCompanyConstraintsCacheKey($companyId);
        $constraintIds = Cache::get($companyKey, []);
        
        foreach ($constraintIds as $constraintId) {
            $this->invalidateConstraint($constraintId);
        }
        
        return Cache::forget($companyKey);
    }
    
    /**
     * Get cache key for a constraint
     *
     * @param string $constraintId Constraint ID
     * @return string Cache key
     */
    protected function getConstraintCacheKey(string $constraintId): string
    {
        return self::CACHE_PREFIX . $constraintId;
    }
    
    /**
     * Get cache key for user constraints list
     *
     * @param string $userId User ID
     * @return string Cache key
     */
    protected function getUserConstraintsCacheKey(string $userId): string
    {
        return self::USER_CACHE_PREFIX . $userId;
    }
    
    /**
     * Get cache key for company constraints list
     *
     * @param string $companyId Company ID
     * @return string Cache key
     */
    protected function getCompanyConstraintsCacheKey(string $companyId): string
    {
        return 'company_attendance_constraints:' . $companyId;
    }
    
    /**
     * Add a constraint ID to a user's constraints list cache
     *
     * @param string $userId User ID
     * @param string $constraintId Constraint ID
     * @return bool Success status
     */
    protected function addToUserConstraintsList(string $userId, string $constraintId): bool
    {
        $userKey = $this->getUserConstraintsCacheKey($userId);
        $constraintIds = Cache::get($userKey, []);
        
        if (!in_array($constraintId, $constraintIds)) {
            $constraintIds[] = $constraintId;
            Cache::put($userKey, $constraintIds, Carbon::now()->addMinutes(self::DEFAULT_TTL));
        }
        
        return true;
    }
    
    /**
     * Add a constraint ID to a company's constraints list cache
     *
     * @param string $companyId Company ID
     * @param string $constraintId Constraint ID
     * @return bool Success status
     */
    protected function addToCompanyConstraintsList(string $companyId, string $constraintId): bool
    {
        $companyKey = $this->getCompanyConstraintsCacheKey($companyId);
        $constraintIds = Cache::get($companyKey, []);
        
        if (!in_array($constraintId, $constraintIds)) {
            $constraintIds[] = $constraintId;
            Cache::put($companyKey, $constraintIds, Carbon::now()->addMinutes(self::DEFAULT_TTL));
        }
        
        return true;
    }
    
    /**
     * Calculate appropriate TTL for a constraint based on its type
     *
     * @param AttendanceConstraint $constraint The constraint
     * @return int TTL in minutes
     */
    protected function getConstraintTtl(AttendanceConstraint $constraint): int
    {
        // Different TTLs based on constraint types
        $ttlMap = [
            AttendanceConstraint::TYPE_TIME => 120,      // Time constraints change less frequently
            AttendanceConstraint::TYPE_LOCATION => 60,   // Location constraints moderate frequency
            AttendanceConstraint::TYPE_DEVICE => 30,     // Device constraints may change more often
            AttendanceConstraint::TYPE_ROLE => 240,      // Role constraints change rarely
            AttendanceConstraint::TYPE_BEHAVIORAL => 60, // Behavioral constraints moderate frequency
            AttendanceConstraint::TYPE_SECURITY => 15,   // Security constraints may change often
            AttendanceConstraint::TYPE_COMPLIANCE => 180 // Compliance constraints change occasionally
        ];
        
        return $ttlMap[$constraint->type] ?? self::DEFAULT_TTL;
    }
}
