A. Validasi ke Form Request
===========================

1. buat form request baru
php artisan make:request UpdateMovieRequest

2. Atur aturan validasi di dalam UpdateMovieRequest.php

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
{
    // Mengizinkan semua user untuk melakukan request ini
    public function authorize(): bool
    {
        return true;
    }

    // Aturan validasi
    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'sinopsis' => 'required|string',
            'tahun' => 'required|integer',
            'pemain' => 'required|string',
            'foto_sampul' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}

3. Update MovieController agar pakai UpdateMovieRequest
Pada method update() di MovieController.php, kita ubah tipe request dari Request menjadi UpdateMovieRequest.

4. Hapus kode Validator::make() karena validasi sudah otomatis


B. Refactor Upload File ke Service Class
========================================
Tujuan:
Pisahkan proses upload dan manipulasi file (rename, simpan, hapus) dari controller ke dalam FileUploadService, agar controller tetap clean dan logika upload lebih mudah di-maintain.

1. buat step baru
buat di : app/Services/FileUploadService.php
isinya:

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

2. Daftarkan Service di Controller
use App\Services\FileUploadService

protected $fileService;

public function __construct(FileUploadService $fileService)
{
    $this->fileService = $fileService;
}

3. Gunakan Service di Method update()
ganti jadi:

public function update(UpdateMovieRequest $request, $id)
{
    $movie = Movie::findOrFail($id);

    if ($request->hasFile('foto_sampul')) {
        // Gunakan service untuk upload + hapus foto lama
        $fileName = $this->fileService->uploadImage($request->file('foto_sampul'), $movie->foto_sampul);

        // Update data film + foto baru
        $movie->update([
            'judul' => $request->judul,
            'sinopsis' => $request->sinopsis,
            'category_id' => $request->category_id,
            'tahun' => $request->tahun,
            'pemain' => $request->pemain,
            'foto_sampul' => $fileName,
        ]);
    } else {
        // Update data film tanpa mengganti foto
        $movie->update($request->only(['judul', 'sinopsis', 'category_id', 'tahun', 'pemain']));
    }

    return redirect('/movies/data')->with('success', 'Data berhasil diperbarui');
}

