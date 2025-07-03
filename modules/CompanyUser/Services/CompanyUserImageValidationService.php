<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
class CompanyUserImageValidationService
{
    private $errors = [];

    public function validateName($request)
    {
       $errors = [];

        // Ensure the image is uploaded
        if (!$request->hasFile('image')) {

            array_push($errors, ["sentence" => "الصورة مطلوبة", "sub_title" => null, "status" => -1]);
        } else {
            $image = $request->file('image');

            // Check if the file is an image and is valid
            if (!$image->isValid() || !in_array($image->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/web'])) {
                array_push($errors, [
                    "sentence" => "يجب أن تكون الصورة من نوع JPG أو PNG", "sub_title" => null, "status" => -1
                ]);
            } else {

                $maxSizeInMB = 5;
                $fileSizeInMB = $image->getSize() / (1024 * 1024); // Convert bytes to MB

                if ($fileSizeInMB > $maxSizeInMB) {
                     array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => -1]);
                } else {
                    array_push($errors, ["sentence" => "حجم الصورة يجب أن لا يتعدى 5 ميجابايت", "sub_title" => null, "status" => 1]);
                }

                list($width, $height) = getimagesize($image->getPathname());

                // Validate dimensions
                if ($width == 1920 && $height == 1080) {
                    array_push($errors, [
                        "sentence" => "الصورة مناسبة",
                        "sub_title" => null,
                        "status" => 1
                    ]);
                } else {
                    array_push($errors, [
                        "sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080",
                        "sub_title" => null,
                        "status" => -1
                    ]);
                }

                $serviceUser =  $this->checkImageTenant($image);

                if ($serviceUser === 0) {
                    array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => -1]);
                } else {
                    array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => null, "status" => 1]);
                }

            }
            return $errors;
        }
    }
public function checkImageTenant($image): int
{
    try {
        $manager = new ImageManager(new Driver());
        $img = $manager->read($image);
        $width = $img->width();
        $height = $img->height();

        $lightPixelCount = 0;
        $totalSampledPixels = 0;

        // A brightness of 255 is pure white. We'll consider anything above 230 as "very light".
        // هذا هو الرقم الذي يمكنك تعديله. كلما قللته، زادت مرونة الكود.
        $brightnessThreshold = 230;

        // Coordinates of the 4 corners (offset slightly to avoid artifacts)
        $pointsToCheck = [
            [1, 1],                    // top-left
            [$width - 2, 1],           // top-right
            [1, $height - 2],          // bottom-left
            [$width - 2, $height - 2], // bottom-right
        ];

        // Add more sample points along the edges for better accuracy
        $horizontalStep = floor($width / 10);
        for ($i = $horizontalStep; $i < $width - $horizontalStep; $i += $horizontalStep) {
            $pointsToCheck[] = [$i, 1];           // Top edge
            $pointsToCheck[] = [$i, $height - 2]; // Bottom edge
        }

        $verticalStep = floor($height / 10);
        for ($i = $verticalStep; $i < $height - $verticalStep; $i += $verticalStep) {
            $pointsToCheck[] = [1, $i];           // Left edge
            $pointsToCheck[] = [$width - 2, $i];  // Right edge
        }

        foreach ($pointsToCheck as [$x, $y]) {
            // Pick the color of the pixel
            $rgb = $img->pickColor($x, $y)->toArray();

            // --- The Key Change is Here ---
            // Calculate the average brightness of the pixel
            $brightness = ($rgb[0] + $rgb[1] + $rgb[2]) / 3;

            // Check if the brightness is above our threshold
            if ($brightness > $brightnessThreshold) {
                $lightPixelCount++;
            }

            $totalSampledPixels++;
        }

        // If no pixels were sampled, fail safely
        if ($totalSampledPixels === 0) {
            return 0;
        }

        // Calculate the percentage of light pixels among those sampled
        $percentage = ($lightPixelCount / $totalSampledPixels) * 100;

        // If more than 80% of the sampled edge pixels are light, we approve it.
        // يمكنك تعديل هذه النسبة أيضاً إذا أردت.
        return $percentage > 80 ? 1 : 0;

    } catch (\Exception $e) {
        // If anything goes wrong reading the image, assume it's invalid.
        return 0;
    }
}


}
