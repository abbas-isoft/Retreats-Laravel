<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContentListingController;
use App\Http\Controllers\PriceListingController;
use App\Http\Controllers\PricesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SinglePropertyListingController;
use App\Http\Controllers\SuperControlController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/accounts');

// Accounts
Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
Route::get('fetch-accounts', [AccountController::class, 'fetch'])->name('fetch.accounts');


// Properties  
Route::get('fetch-properties', [PropertyController::class, 'fetch'])->name('fetch.properties');
Route::get('properties', [PropertyController::class, 'index'])->name('properties');


// Configuration
Route::get('configurations/{id}', [ConfigurationController::class, 'index'])->name('configurations.index');
Route::get('fetch-configuration/{id}', [ConfigurationController::class, 'fetch'])->name('fetch.configuration');


// Prices
Route::get('prices', [PricesController::class, 'index'])->name('prices.index');
Route::post('prices/fetch', [PricesController::class, 'fetch'])->name('prices.fetch');


// availability
Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
Route::get('fetch-availability', [AvailabilityController::class, 'fetch'])->name('fetch.availability');

 

//single property listing
Route::get('single-property-listing/{id}', [SinglePropertyListingController::class, 'index'])->name('single.property.listing');
Route::get('update-single-configuration', [SinglePropertyListingController::class, 'update'])->name('single.configuration.update');
Route::get('fetch-single-configuration/{id}', [SinglePropertyListingController::class, 'fetch'])->name('single.configuration.fetch');


// Price Listing
Route::get('price-listing', [PriceListingController::class, 'index'])->name('price.listing');
Route::get('fetch-price-listing', [PriceListingController::class, 'fetch'])->name('price.listing.data');


// content listing
Route::get('content-listing', [ContentListingController::class, 'index'])->name('content.listing');
Route::post('content-listing/update/{id}', [ContentListingController::class, 'update'])->name('content.update');

Route::get('/content-listing', [ContentListingController::class, 'index'])->name('content.listing');
Route::post('/content-listing', [ContentListingController::class, 'fetchAndUpdate']);


Route::get('/content-listing', [ContentListingController::class, 'index'])->name('content.listing');

// Bulk fetch & update all records
Route::post('/content-listing/fetch-update', [ContentListingController::class, 'fetchAndUpdate'])->name('content.fetchUpdate');

// Update a single record by ID (for the per-row update button)
Route::post('/content-listing/update/{id}', [ContentListingController::class, 'update'])->name('content.update');








Route::get('/price-listing', [PriceListingController::class, 'index'])->name('price.listing');

Route::get('/price-listing/fetch', [PriceListingController::class, 'fetch'])->name('price.listing.fetch');




Route::get('/price-listing/fetch', [PriceListingController::class, 'fetch'])->name('price.listing.fetch');

Route::get('/price-listing', [PriceListingController::class, 'index'])->name('price.listing');




Route::get('/price-listing', [PricelistingController::class, 'index'])->name('price.index');
Route::post('/fetch-price-data', [PricelistingController::class, 'fetch'])->name('price.fetch');



Route::get('/price-listing', [PriceListingController::class, 'index'])->name('price.index');
Route::post('/price-listing/fetch', [PriceListingController::class, 'fetch'])->name('price.fetch');
