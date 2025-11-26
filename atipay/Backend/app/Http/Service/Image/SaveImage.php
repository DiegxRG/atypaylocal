<?php

declare(strict_types=1);

namespace App\Http\Service\Image;

use Illuminate\Http\UploadedFile;

trait SaveImage
{
    public function upload(UploadedFile $image, string $folder = 'images'): string
    {
        return $image->store($folder, 'public');
    }
}
