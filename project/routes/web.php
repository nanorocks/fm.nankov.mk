<?php

use App\Models\RadioChannel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/channels', function () {
    return RadioChannel::all();
});

Route::get('/channels/null', function () {
    return RadioChannel::where('audio_url', null)->get();
});
