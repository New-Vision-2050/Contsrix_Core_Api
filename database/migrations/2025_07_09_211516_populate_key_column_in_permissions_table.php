<?php

use Illuminate\Database\Migrations\Migration;
use Modules\RoleAndPermission\Models\Permission;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = config('permissions.permissions');

        if (empty($permissions)) {
            return;
        }

        foreach ($permissions as $key => $name) {
            $permission = Permission::where('name', $name);
            if ($permission)
                $permission->update(['key' => $key]);
            else
                Permission::create(['name' => $name, 'key' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To make this reversible, we can set the key back to null.
        // This assumes that all permissions managed by this config had a null key before.
        Permission::whereIn('name', array_values(config('permissions.permissions')))
            ->update(['key' => null]);
    }
};
