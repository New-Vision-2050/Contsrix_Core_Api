<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class UuidCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Return null if the value is null to avoid the fromString error
        if ($value === null) {
            return null;
        }
        
        return Uuid::fromString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Handle null values in the set method as well
        if ($value === null) {
            return null;
        }
        
        return $value instanceof Uuid ? $value->toString() : Uuid::fromString($value)->toString(); // Convert Uuid object to string

    }
}
