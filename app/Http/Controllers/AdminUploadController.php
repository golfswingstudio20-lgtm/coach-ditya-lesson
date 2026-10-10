<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/** Membuat link upload langsung ke Supabase Storage (dipakai public/js/direct-upload.js). */
class AdminUploadController extends Controller
{
    public function sign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folder'   => ['required', Rule::in(Uploads::FOLDERS)],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        if (! Uploads::directEnabled()) {
            return response()->json(['message' => 'Upload langsung belum aktif.'], 409);
        }

        try {
            return response()->json(Uploads::sign($data['folder'], $data['filename']));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Gagal menyiapkan upload: ' . $e->getMessage()], 502);
        }
    }
}
