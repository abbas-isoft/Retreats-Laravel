<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PricesController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\ContentListingController;
use App\Http\Controllers\Api\PropertyContentController;
use App\Http\Controllers\Api\AvailabilityCalendarController;
use App\Http\Controllers\Api\SinglePropertyListingController;

// Accounts
Route::get('accounts', [AccountController::class, 'index']);

// availability
Route::get('availability', [AvailabilityController::class, 'index']);

// Properties
Route::get('properties/{accountid}', [PropertyController::class, 'index']);

// Configuration
Route::get('configurations/{accountid}', [ConfigurationController::class, 'index']);

Route::get('availabilities/{accountid}', [AvailabilityController::class, 'index']);

// Prices
Route::get('/prices/{accountId}', [PricesController::class, 'index']);

// single property listing
Route::get('single-property-listing/{propertyId}', [SinglePropertyListingController::class, 'index']);

// Content Listing
// Route::get('content-listing/{id}', [ContentListingController::class, 'index']);
Route::get('content-listing/{propertyId}', [ContentListingController::class, 'index']);

// Property Content
Route::get('property-content/{propertyId}', [PropertyContentController::class, 'index']);


// Availability Calendar
Route::get('availability-calendars/{propertyId}', [AvailabilityCalendarController::class, 'index']);
