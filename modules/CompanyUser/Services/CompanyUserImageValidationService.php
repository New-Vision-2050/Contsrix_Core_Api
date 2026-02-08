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
                $allowedWidth = 1920;
                $allowedHeight = 1080;
                $tolerance = 0.1;

                $minWidth = $allowedWidth - ($allowedWidth * $tolerance);
                $maxWidth = $allowedWidth + ($allowedWidth * $tolerance);
                $minHeight = $allowedHeight - ($allowedHeight * $tolerance);
                $maxHeight = $allowedHeight + ($allowedHeight * $tolerance);
                array_push($errors, [
                    "sentence" => "الصورة مناسبة",
                    "sub_title" => null,
                    "status" => 1
                ]);
                // Validate dimensions
//                if ($width >= $minWidth && $width <= $maxWidth && $height >= $minHeight && $height <= $maxHeight) {
//
//                } else {
//                    array_push($errors, [
//                        "sentence" => "أبعاد الصورة غير صحيحة. يجب أن تكون الأبعاد بين  1920*1080",
//                        "sub_title" => null,
//                        "status" => -1
//                    ]);
//                }

                $serviceUser =  $this->checkImageTenant($image);

                if ($serviceUser === 0) {
                    array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => $serviceUser, "status" => -1]);
                } else {
                    array_push($errors, ["sentence" => "تأكد ان الخلفية بيضاء", "sub_title" => $serviceUser, "status" => 1]);
                }

            }
            return $errors;
        }
    }
    public function checkImageTenant($image)//: int
    {
        $manager = new ImageManager(new Driver());
        $img = $manager->read($image);
        $width = $img->width();
        $height = $img->height();

        $white = 0;
        $color = 0;

        // Coordinates of the 4 corners
        $corners = [
            [0, 0],                    // top-left
            [$width - 1, 0],           // top-right
            [0, $height - 1],          // bottom-left
            [$width - 1, $height - 1], // bottom-right
        ];

        foreach ($corners as [$x, $y]) {
            $rgb = $img->pickColor($x, $y)->toArray();
            if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) { // allow slight variation
                $white++;
            } else {
                $color++;
            }
        }

        // Sample horizontal and vertical edges (skip corners)
        for ($i = 10; $i < $width - 10; $i += 20) {
            foreach ([0, $height - 1] as $y) {
                $rgb = $img->pickColor($i, $y)->toArray();
                if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        for ($i = 10; $i < $height - 10; $i += 20) {
            foreach ([0, $width - 1] as $x) {
                $rgb = $img->pickColor($x, $i)->toArray();
                if ($rgb[0] >= 240 && $rgb[1] >= 240 && $rgb[2] >= 240) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        $percentage = ($white / ($white + $color)) * 100;

        return $percentage >= 25 ? 1 : 0;
    }


}
