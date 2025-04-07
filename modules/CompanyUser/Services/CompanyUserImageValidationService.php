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
                if ($width < 600 || $height < 800) {
                    array_push($errors, [
                        "sentence" => "حجم الصورة غير مناسب. يفضل أن يكون العرض أكبر من 600 والطول أكبر من 800 بكسل",
                        "sub_title" => null,
                        "status" => -1
                    ]);
                } else {
                    array_push($errors, [
                        "sentence" => "الصورة مناسبة",
                        "sub_title" => null,
                        "status" => 1
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
    public function checkImageTenant( $image): int
    {
        $manager = new ImageManager(new Driver());
        $img = $manager->read($image);
        $width = $img->width();
        $height = $img->height();
        $white = 0;
        $color = 0;

// Iterate over each pixel
        for ($x = 0; $x < 2; $x++) {
            for ($y = 0; $y < $width; $y++) {
                $rgb = $img->pickColor($x, $y)->toArray();
                // Check if the pixel color is white (255, 255, 255)
                if ($rgb[0] == 255 || $rgb[1] == 255 || $rgb[2] == 255) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        for ($x = 0; $x < 2; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = $img->pickColor($y, $x)->toArray();
                // Check if the pixel color is white (255, 255, 255)
                if ($rgb[0] == 255 || $rgb[1] == 255 || $rgb[2] == 255) {
                    $white++;
                } else {
                    $color++;
                }
            }
        }

        $percentage = ($white / ($white + $color)) * 100;
//        return response(['kk' => $percentage]);


        if ($percentage > 70) {
            return 1;
        } else {
            return 0;
        }

    }

}
