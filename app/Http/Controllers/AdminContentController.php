<?php

namespace App\Http\Controllers;

use App\Support\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin > Konten Website: semua teks & foto halaman publik.
 */
class AdminContentController extends Controller
{
    public function index(Request $request): View
    {
        $pages = Content::pages();
        $page  = array_key_exists($request->query('page'), $pages) ? $request->query('page') : 'home';

        return view('admin.content.index', compact('pages', 'page'));
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $info = Content::section($section);
        abort_unless($info, 404);

        Content::save($section, $request);

        $message = $info['title'] . ' disimpan.';

        // Dari tombol ✎ Edit di halaman website: kembali ke bagian yang sama
        if ($request->input('return') === 'page') {
            $previous = strtok(url()->previous(), '#');

            return redirect()->to($previous . '#' . $info['anchor'])->with('content_saved', $message);
        }

        return redirect()
            ->route('admin.content.index', ['page' => $info['page']])
            ->withFragment('sec-' . $section)
            ->with('success', $message);
    }
}
