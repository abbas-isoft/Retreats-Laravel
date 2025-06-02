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
use App\Http\Controllers\PropertyContentController;

Route::redirect('/', '/accounts');

// Accounts
Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
Route::get('fetch-accounts', [AccountController::class, 'fetch'])->name('fetch.accounts');


// Properties  
Route::get('properties/{accountid}', [PropertyController::class, 'index'])->name('properties');
Route::get('fetch-properties/{accountid}', [PropertyController::class, 'fetch'])->name('fetch.properties');


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
Route::get('price-listing/{property_id}', [PriceListingController::class, 'index'])->name('price.listing');
Route::post('fetch-price-listing', [PriceListingController::class, 'fetch'])->name('price.listing.data');



// Content Listing
Route::get('/content-listing/{id}', [ContentListingController::class, 'index'])->name('content.index');
Route::post('/content-listing/fetch-update/{id}', [ContentListingController::class, 'fetchAndUpdate'])->name('content.fetchUpdate');


// Price Listing
Route::get('/price-listing', [PriceListingController::class, 'index'])->name('price.index');
Route::post('/price-listing/fetch', [PriceListingController::class, 'fetch'])->name('price.fetch');


// Property Content
Route::get('/property-content/{id}', [PropertyContentController::class, 'index'])->name('property-content.index');

Route::post('/property-content/fetch-update/{id}', [PropertyContentController::class, 'fetchAndUpdate'])->name('property-content.fetchUpdate');
