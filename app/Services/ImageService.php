<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class ImageService
{
    public function store(UploadedFile $file, string $field, bool $avatar = false): string
    {
        $info = @getimagesize($file->getRealPath());
        if (!$info || $info[0] > 6000 || $info[1] > 6000 || $info[0] * $info[1] > 16000000) {
            throw ValidationException::withMessages([$field=>'Use an image at most 6000 pixels per side and 16 megapixels.']);
        }
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$source) throw ValidationException::withMessages([$field=>'The image could not be opened. Please choose another JPG, PNG, or WebP.']);
        $size = $avatar ? 256 : 720;
        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 246, 244, 239));
        // Avatars are center-cropped; products are contained without distortion.
        if ($avatar) {
            $edge = min($info[0], $info[1]);
            imagecopyresampled($canvas, $source, 0, 0, (int)(($info[0]-$edge)/2), (int)(($info[1]-$edge)/2), $size, $size, $edge, $edge);
        } else {
            $scale = min($size/$info[0], $size/$info[1]);
            $width = max(1, (int)round($info[0]*$scale)); $height = max(1, (int)round($info[1]*$scale));
            imagecopyresampled($canvas, $source, (int)(($size-$width)/2), (int)(($size-$height)/2), 0, 0, $width, $height, $info[0], $info[1]);
        }
        ob_start(); imagejpeg($canvas, null, 86); $bytes = ob_get_clean();
        imagedestroy($source); imagedestroy($canvas);
        $name = Str::uuid().'.jpg';
        Storage::disk('local')->put('media/'.$name, $bytes);
        return $name;
    }
    public function delete(?string $filename): void
    {
        if ($filename && preg_match('/^[a-f0-9-]{36}\.jpg$/', $filename)) {
            Storage::disk('local')->delete('media/'.$filename);
        }
    }
}
