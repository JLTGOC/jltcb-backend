<?php

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

if (!function_exists('upload_image')) {
    function upload_image(Request $request, string $keyName, string $folderName)
    {
        $directory = storage_path("app/public/{$folderName}");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $uniqueName = (string) Str::uuid();

        if ($request->hasFile($keyName)) {
            $file = $request->file($keyName);
            $extension = $file->getClientOriginalExtension();
            
            return $file->storeAs($folderName, $uniqueName . '.' . $extension, 'public');
        }

        $base64String = $request->input($keyName);
        
        if ($base64String && preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $extension = strtolower($matches[1]);
            if ($extension === 'jpeg') $extension = 'jpg';

            $imageData = substr($base64String, strpos($base64String, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) return null;

            $filename = $folderName . '/' . $uniqueName . '.' . $extension;
            
            Storage::disk('public')->put($filename, $imageData);

            return $filename;
        }

        return null;
    }
}