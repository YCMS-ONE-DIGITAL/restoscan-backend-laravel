<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/media/{path}', function ($path) {
    $fullPath = public_path($path);

    if (!File::exists($fullPath)) {
        return abort(404);
    }

    return Response::file($fullPath);

})->where('path', '.*');
