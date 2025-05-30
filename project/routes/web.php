<?php

use App\Models\RadioChannel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $stations = RadioChannel::query()->where('audio_url', '!=', null)->where('published', true)->get();

    return view('welcome', compact('stations'));
});


Route::get('/channels', function () {
    return RadioChannel::query()->where('published', true)->get();
});

Route::get('/channels/null', function () {
    return RadioChannel::where('audio_url', null)->get();
});
