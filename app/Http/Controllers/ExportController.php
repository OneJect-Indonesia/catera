<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function download(Request $request, string $filename)
    {
        $path = 'exports/'.$filename;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
