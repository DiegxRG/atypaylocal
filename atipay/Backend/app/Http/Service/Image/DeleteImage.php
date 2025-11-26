<?php

declare(strict_types=1);

namespace App\Http\Service\Image;

use Illuminate\Support\Facades\Storage;

trait DeleteImage
{
    public function deleteImage(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
