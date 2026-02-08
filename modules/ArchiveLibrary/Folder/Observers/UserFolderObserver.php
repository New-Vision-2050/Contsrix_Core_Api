<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Observers;

use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\User\Models\User;

class UserFolderObserver
{
    /**
     * Handle the User "created" event.
     * Creates a personal folder for the user with company_id from user's company.
     */
    public function created(User $user): void
    {
//        Folder::create([
//            'name' => $user->name,
//            'company_id' => $user->company_id,
//            'access_type' => 'public',
//            'status' => 1,
//        ]);
    }

    /**
     * Handle the User "updated" event.
     * Updates the folder name when user name changes.
     */
    public function updated(User $user): void
    {
//        if ($user->isDirty('name')) {
//            $originalName = $user->getOriginal('name');
//
//            $folder = Folder::where('company_id', $user->company_id)
//                ->where('name', $originalName)
//                ->first();
//
//            if ($folder) {
//                $folder->update(['name' => $user->name]);
//            }
//        }
    }

    /**
     * Handle the User "deleted" event.
     * Marks the folder name with (draft) instead of deleting.
     */
    public function deleted(User $user): void
    {
//        $folder = Folder::where('company_id', $user->company_id)
//            ->where('name', $user->name)
//            ->first();
//
//        if ($folder) {
//            $folder->update(['name' => $folder->name . ' (draft)']);
//        }
    }
}
