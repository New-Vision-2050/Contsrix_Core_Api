<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use Illuminate\Support\Facades\DB;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class FileFavouritesService
{
    public function __construct(
        private FileRepository $fileRepository,
    ) {
    }

    /**
     * Add files to user's favourites list with access validation
     *
     * @param User $user
     * @param array $fileIds
     * @return array
     */
    public function addToFavourites(User $user, array $fileIds): array
    {
        $results = [
            'added' => [],
            'already_favourite' => [],
            'access_denied' => [],
            'not_found' => []
        ];

        foreach ($fileIds as $fileId) {
            try {
                $file = $this->fileRepository->find(Uuid::fromString($fileId));

                if (!$file) {
                    $results['not_found'][] = [
                        'file_id' => $fileId,
                        'error' => 'File not found'
                    ];
                    continue;
                }

                // Check if file is already in favourites
                if ($user->favouriteFiles()->where('file_id', $fileId)->exists()) {
                    $results['already_favourite'][] = [
                        'file_id' => $fileId,
                        'file_name' => $file->name
                    ];
                    continue;
                }

                // Validate access for private files
                if (!$this->canAccessFile($user, $file)) {
                    $results['access_denied'][] = [
                        'file_id' => $fileId,
                        'file_name' => $file->name,
                        'reason' => 'No permission to access this private file'
                    ];
                    continue;
                }

                // Add to favourites
                $user->favouriteFiles()->attach($fileId);
                $results['added'][] = [
                    'file_id' => $fileId,
                    'file_name' => $file->name,
                    'access_type' => $file->access_type
                ];

            } catch (\Exception $e) {
                $results['not_found'][] = [
                    'file_id' => $fileId,
                    'error' => 'Error occurred: ' . $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Remove files from user's favourites list
     *
     * @param User $user
     * @param array $fileIds
     * @return int Number of files removed
     */
    public function removeFromFavourites(User $user, array $fileIds): int
    {
        return $user->favouriteFiles()->detach($fileIds);
    }

    /**
     * Get user's favourite files with relationships
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFavourites(User $user)
    {
        return $user->favouriteFiles()
            ->with(['folder', 'media'])
            ->get();
    }

    /**
     * Check if user can access a file
     * Public files are accessible to all, private files require explicit permission
     *
     * @param User $user
     * @param File $file
     * @return bool
     */
    private function canAccessFile(User $user, File $file): bool
    {
        // Public files are accessible to everyone
        if ($file->access_type !== 'private') {
            return true;
        }

        // For private files, check if user has explicit permission
        return $file->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if a file is in user's favourites
     *
     * @param User $user
     * @param string $fileId
     * @return bool
     */
    public function isFavourite(User $user, string $fileId): bool
    {
        return $user->favouriteFiles()
            ->where('file_id', $fileId)
            ->exists();
    }

    /**
     * Get count of user's favourite files
     *
     * @param User $user
     * @return int
     */
    public function getFavouritesCount(User $user): int
    {
        return $user->favouriteFiles()->count();
    }

    /**
     * Toggle favourite status for a file
     *
     * @param User $user
     * @param string $fileId
     * @return array
     */
    public function toggleFavourite(User $user, string $fileId): array
    {
        if ($this->isFavourite($user, $fileId)) {
            $this->removeFromFavourites($user, [$fileId]);
            return [
                'action' => 'removed',
                'is_favourite' => false
            ];
        }

        $result = $this->addToFavourites($user, [$fileId]);
        
        if (!empty($result['added'])) {
            return [
                'action' => 'added',
                'is_favourite' => true
            ];
        }

        return [
            'action' => 'failed',
            'is_favourite' => false,
            'reason' => $result['access_denied'][0]['reason'] ?? $result['not_found'][0]['error'] ?? 'Unknown error'
        ];
    }
}
