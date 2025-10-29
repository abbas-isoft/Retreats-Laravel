<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
// Protected Scribe Docs route

Route::middleware(['web', 'protect.scribe'])->group(function () {
    Route::match(['get', 'post'], '/docs', function () {
        return view('scribe.index');
    })->name('scribe.docs');
});