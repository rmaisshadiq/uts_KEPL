<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class FileUploadService
{
    /**
     * Upload gambar dan kembalikan nama file barunya.
     */
    public function uploadImage($file, $oldFileName = null, $destinationPath = 'images')
    {
        // Generate nama file acak
        $fileName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        // Simpan file ke folder tujuan (default: public/images)
        $file->move(public_path($destinationPath), $fileName);

        // Hapus file lama jika ada
        if ($oldFileName && File::exists(public_path("$destinationPath/$oldFileName"))) {
            File::delete(public_path("$destinationPath/$oldFileName"));
        }

        // Kembalikan nama file baru
        return $fileName;
    }
}
