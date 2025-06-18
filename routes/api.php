<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\ContentListingController;
use App\Http\Controllers\Api\PricesController;
use App\Http\Controllers\Api\PropertyContentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\SinglePropertyListingController;
use Illuminate\Support\Facades\Route;

// Accounts
Route::get('accounts', [AccountController::class, 'index']);
Route::post('fetch-accounts', [AccountController::class, 'fetch']);

// availability
Route::get('availability', [AvailabilityController::class, 'index']);
Route::post('fetch-availability', [AvailabilityController::class, 'fetch']);

// Properties
Route::get('properties/{accountid}', [PropertyController::class, 'index']);
Route::post('fetch-properties/{accountId}', [PropertyController::class, 'fetch']);

// Configuration
Route::get('configurations/{accountid}', [ConfigurationController::class, 'index']);
// Route::post('/configurations/fetch', [ConfigurationController::class, 'fetch']);
Route::post('/configurations/{accountId}', [ConfigurationController::class, 'fetch']);

// Prices
Route::get('prices', [PricesController::class, 'index']);

// single property listing
Route::get('single-property-listing/{id}', [SinglePropertyListingController::class, 'index']);

// Content Listing
Route::get('content-listing/{id}', [ContentListingController::class, 'index']);

// Property Content
Route::get('property-content/{property}', [PropertyContentController::class, 'index']);
Route::post('fetch-property-content/{property}', [PropertyContentController::class, 'fetch']);
