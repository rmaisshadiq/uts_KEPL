<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMovieRequest;
use App\Models\Movie;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{

    public function index()
    {

        $query = Movie::latest();
        if (request('search')) {
            $query->where('judul', 'like', '%' . request('search') . '%')
                ->orWhere('sinopsis', 'like', '%' . request('search') . '%');
        }
        $movies = $query->paginate(6)->withQueryString();
        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::find($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(UpdateMovieRequest $request)
    {
        // Ambil data yang sudah tervalidasi
        $validated = $request->validated();

        // Kalau ada file foto, proses pakai service
        if ($request->hasFile('foto_sampul')) {
            $validated['foto_sampul'] = $this->fileService->uploadImage($request->file('foto_sampul'));
        }

        // Simpan ke database
        Movie::create($validated);

        return redirect()->route('movies.index')->with('success', 'Film berhasil ditambahkan.');
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(10);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::find($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

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


    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        // Delete the movie's photo if it exists
        if (File::exists(public_path('images/' . $movie->foto_sampul))) {
            File::delete(public_path('images/' . $movie->foto_sampul));
        }

        // Delete the movie record from the database
        $movie->delete();

        return redirect('/movies/data')->with('success', 'Data berhasil dihapus');
    }
}
