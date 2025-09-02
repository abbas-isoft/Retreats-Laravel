<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\PricesController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PricesApiController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\ContentListingController;
use App\Http\Controllers\Api\PropertyContentController;
use App\Http\Controllers\Api\AvailabilityCalendarController;
use App\Http\Controllers\Api\SinglePropertyListingController;
use App\Http\Controllers\Api\HomepageContentController;
use App\Http\Controllers\Api\RetreatSearchController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CollectionSectionController;
use App\Http\Controllers\Api\FeatureBoxController;
use App\Http\Controllers\Api\HeroSectionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\LatestArrivalController;
use App\Http\Controllers\Api\GroupContactFormController;
use App\Http\Controllers\Api\AboutUsController;
use App\Http\Controllers\Api\BookingGuideController;
use App\Http\Controllers\Api\PageSectionController;
use App\Http\Controllers\Api\TestBookingController;
use App\Http\Controllers\Api\NavbarController;
use App\Http\Controllers\AboutSectionController;
use App\Http\Controllers\FAQSectionController;
use App\Http\Controllers\Api\PropertyGridController;
use App\Http\Controllers\Api\PropertySearchController;
use App\Http\Controllers\Api\BookingStepController;
use App\Http\Controllers\Api\ListYourPropertyController;
use App\Http\Controllers\Api\HospitalitySectionController;
use App\Http\Controllers\Api\ListPropertyController;
use App\Http\Controllers\Api\WhyPartnerBoxController;
use App\Http\Controllers\Api\OwnerSaySectionController;
use App\Http\Controllers\Api\SearchDropdownController;
use App\Http\Controllers\Api\PropertyPriceController;
use App\Http\Controllers\Api\PropertyApiController;

// Accounts
Route::get('accounts', [AccountController::class, 'index']);

// availability
// Route::get('availability', [AvailabilityController::class, 'index']);
// routes/api.php
Route::get('/property/{propertyId}/total-price', [PropertyPriceController::class, 'getTotalPrice']);

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
// Route::get('property-content/{propertyId}', [PropertyContentController::class, 'index']);
Route::get('/property-content/{propertyId}', [PropertyContentController::class, 'index']);

Route::get('/property-grid', [PropertyGridController::class, 'index']);


  Route::get('filter-by-collection/{id}', [PropertyContentController::class, 'getCollection']);
// Availability Calendar
Route::get('availability-calendars/{propertyId}', [AvailabilityCalendarController::class, 'index']);
Route::get('/properties/{id}/price', [PropertyContentController::class, 'getPropertyPrice']);

// PriceListing
Route::get('prices-listing/{propertyId}', [PricesApiController::class, 'index']);
Route::get('similar_properties/{id}', [PropertyContentController::class, 'getSimilar']);


// Create Booking
Route::post('/book-property', [BookingController::class, 'bookProperty']);

// Test Booking
Route::post('/test-book-property', [TestBookingController::class, 'bookTestProperty']);


// Cancel Booking
Route::post('/sync-availability/{propertyId}', [BookingController::class, 'syncAvailability']);
Route::post('/cancel-booking', [BookingController::class, 'cancelBooking']);


Route::get('/homepage-content', [HomepageContentController::class, 'index']);
Route::post('/homepage', [HomepageContentController::class, 'store']);

Route::get('/properties', [PropertyApiController::class, 'getProperties']);
// Search
Route::post('/retreats/search', [RetreatSearchController::class, 'index']);

Route::get('/homepage', [HomepageContentController::class, 'index']);
Route::post('/homepage', [HomepageContentController::class, 'store']);

Route::get('/search-properties', [PropertySearchController::class, 'search']);

Route::get('/collections', [CollectionController::class, 'index']);
Route::post('/collections', [CollectionController::class, 'store']);

Route::get('/collection-section/{key}', [CollectionSectionController::class, 'show']);


Route::get('/sections/{key}', [CollectionSectionController::class, 'show']);


Route::get('/feature-boxes', [FeatureBoxController::class, 'index']);

Route::get('/about-detail', [HospitalitySectionController::class, 'index']);
Route::post('/about-detail/update', [HospitalitySectionController::class, 'update']);

Route::get('/hero-section', [HeroSectionController::class, 'show']);

Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);

Route::get('/latest-arrivals', [LatestArrivalController::class, 'index']);

Route::get('/locations', [PropertyContentController::class, 'getLocations']);
// Navbar with Collection dropdown
Route::get('/navbar', [NavbarController::class, 'getNavbarData']);

Route::post('/group-contact', [GroupContactFormController::class, 'store']);


Route::get('/about-us', [AboutUsController::class, 'GetAboutPage']);
Route::post('/about-us', [AboutUsController::class, 'storeOrUpdate']);

//Building the future of Hospitality
Route::get('/booking-guide', [BookingGuideController::class, 'index']);
Route::post('/booking-guide', [BookingGuideController::class, 'store']);

//Route::prefix('v1')->group(function () {
Route::post('/section', [PageSectionController::class, 'store']);
Route::get('/section/{section_name}', [PageSectionController::class, 'show']);
Route::put('/section/{section_name}', [PageSectionController::class, 'update']);
//});

// Route::get('/booking-steps', [BookingStepController::class, 'show']);
// Route::post('/booking-steps', [BookingStepController::class, 'store']);

Route::get('/how-to-make-booking', [BookingStepController::class, 'getBookingSteps']);
Route::post('/how-to-make-booking', [BookingStepController::class, 'store']);

Route::post('faqs-tab', [AboutSectionController::class, 'storeAboutSection']);
Route::get('faqs-tab', [AboutSectionController::class, 'GetFaqsPage']);


Route::post('faq-section', [FAQSectionController::class, 'storeFAQSection']);
Route::get('faq-section', [FAQSectionController::class, 'getFAQSection']);

// Route::get('/list-your-property-content', [ListYourPropertyController::class, 'index']);
// Route::post('/list-your-property-content', [ListYourPropertyController::class, 'store']);


Route::get('/list-property', [ListPropertyController::class, 'GetListProperty']);
Route::post('/list-property', [ListPropertyController::class, 'PostListProperty']);

Route::get('/why-partner', [WhyPartnerBoxController::class, 'index']);
Route::post('/why-partner', [WhyPartnerBoxController::class, 'store']);

Route::get('/owner-say', [OwnerSaySectionController::class, 'index']);
Route::post('/owner-say', [OwnerSaySectionController::class, 'store']);


Route::get('/search-filters', [SearchDropdownController::class, 'index']);
