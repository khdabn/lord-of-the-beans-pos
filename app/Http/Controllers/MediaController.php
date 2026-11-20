<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
class MediaController extends Controller
{
    public function __invoke(string $filename)
    {
        abort_unless(preg_match('/^[a-f0-9-]{36}\.jpg$/', $filename), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists('media/'.$filename), 404);
        return response()->file($disk->path('media/'.$filename), [
            'Content-Type'=>'image/jpeg', 'X-Content-Type-Options'=>'nosniff', 'Cache-Control'=>'private, max-age=3600',
        ]);
    }
}
