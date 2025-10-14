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
use App\Http\Controllers\Api\PriceCalculationController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\PropertyExtraController;
use App\Http\Controllers\Api\AdminCollectionController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\AdminRegionController;
use App\Http\Controllers\Api\VideoUploadController;
use App\Http\Controllers\Api\IconUploadController;
// Accounts
Route::get('accounts', [AccountController::class, 'index']);

// availability
// Route::get('availability', [AvailabilityController::class, 'index']);
// routes/api.php
Route::get('/property/{propertyId}/total-price', [PropertyPriceController::class, 'getTotalPrice']);
Route::post('/calculate-price', [PriceCalculationController::class, 'calculatePrice']);
Route::get('/other-properties', [PropertyContentController::class, 'getOtherProperties']);
Route::post('/create-payment-intent', [TestBookingController::class, 'getClientSecret']);
// Properties
Route::get('properties/{accountid}', [PropertyController::class, 'index']);
Route::post('/save-payment-method', [TestBookingController::class, 'savePaymentMethod']);

// Configuration
Route::get('configurations/{accountid}', [ConfigurationController::class, 'index']);

Route::get('availabilities/{accountid}', [AvailabilityController::class, 'index']);
Route::get('/booking/success/{booking}', [TestBookingController::class, 'checkoutSuccess'])->name('booking.success');
Route::get('/booking/cancel/{booking}', [TestBookingController::class, 'checkoutCancel'])->name('booking.cancel');
Route::post('/stripe/webhook', [TestBookingController::class, 'handleStripeWebhook']);

// Prices
Route::get('/prices/{accountId}', [PricesController::class, 'index']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::post('/bookings/{booking}/deposit/release', [TestBookingController::class, 'releaseDeposit']);
Route::post('/bookings/{booking}/deposit/capture', [TestBookingController::class, 'captureDeposit']);
// single property listing
Route::get('single-property-listing/{propertyId}', [SinglePropertyListingController::class, 'index']);
Route::get('/properties/{propertyId}/extras', [PropertyExtraController::class, 'index']);

// Content Listing
// Route::get('content-listing/{id}', [ContentListingController::class, 'index']);
Route::get('content-listing/{propertyId}', [ContentListingController::class, 'index']);
Route::get('/properties/{propertyId}/booking-info', [PropertyContentController::class, 'GetBookingInfo']);

// Property Content
// Route::get('property-content/{propertyId}', [PropertyContentController::class, 'index']);
Route::get('/property-content/{propertyId}', [PropertyContentController::class, 'index']);

Route::get('/property-grid', [PropertyGridController::class, 'index']);


  Route::get('filter-by-collection/{id}', [PropertyContentController::class, 'getCollection']);
// Availability Calendar
Route::get('availability-calendars/{propertyId}', [AvailabilityCalendarController::class, 'index']);
Route::get('/properties/{id}/price', [PropertyContentController::class, 'getPropertyPrice']);
Route::get('/availability-calendars', [AvailabilityCalendarController::class, 'allProperties']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::get('/newsletter/index', [NewsletterController::class, 'index']);
// PriceListing
Route::get('prices-listing/{propertyId}', [PricesApiController::class, 'index']);
Route::get('similar_properties/{id}', [PropertyContentController::class, 'getSimilar']);
Route::get('/test-logs', [TestBookingController::class, 'testLogging']);
Route::post('/test-webhook-simple', [TestBookingController::class, 'testWebhookSimple']);
Route::post('/stripe-webhook', [TestBookingController::class, 'handleStripeWebhook']);
Route::post('/handle-payment-success', [TestBookingController::class, 'handlePaymentSuccess']);
// Create Booking
Route::post('/book-property', [BookingController::class, 'bookProperty']);

// Test Booking
Route::post('/test-book-property', [TestBookingController::class, 'bookTestProperty']);


// Cancel Booking
Route::post('/sync-availability/{propertyId}', [BookingController::class, 'syncAvailability']);
Route::post('/cancel-booking', [BookingController::class, 'cancelBooking']);
Route::get('/collections', [CollectionController::class, 'getAllCollections']);


Route::get('/homepage-content', [HomepageContentController::class, 'index']);
Route::post('/homepage', [HomepageContentController::class, 'store']);

Route::get('/properties', [PropertyApiController::class, 'getProperties']);
// Search
Route::post('/retreats/search', [RetreatSearchController::class, 'index']);

Route::get('/homepage', [HomepageContentController::class, 'index']);
Route::post('/homepage', [HomepageContentController::class, 'store']);
Route::post('/admin/collections', [AdminCollectionController::class, 'createCollection']);
Route::get('/admin/collections/{id}', [AdminCollectionController::class, 'getCollection']);
Route::delete('/admin/collections/{id}', [AdminCollectionController::class, 'deleteCollection']);
Route::post('/upload-image', [ImageUploadController::class, 'upload']);
Route::post('/upload-video', [VideoUploadController::class, 'upload']);
Route::post('/upload-icon', [IconUploadController::class, 'upload']);

// Route::post('/admin/get-region-destinations', [AdminCollectionController::class, 'getRegionDestinations']);
// ✅ REGION MANAGEMENT (Admin)
Route::prefix('admin')->group(function () {
    // Create new region
    Route::post('/region', [AdminCollectionController::class, 'createRegion']);
    
    // Update existing region
    Route::put('/region/{id}', [AdminCollectionController::class, 'updateRegion']);
    
    // List all regions
    Route::get('/regions', [AdminCollectionController::class, 'getAllRegions']);

    //For Delete Region
    Route::delete('/region/{id}', [AdminCollectionController::class, 'deleteRegion']); // ✅ ADD THIS LINE

});

// ✅ FETCH PROPERTIES FOR A REGION
Route::post('/get-region-destinations', [AdminCollectionController::class, 'getRegionDestinations']);


Route::get('/search-properties', [PropertySearchController::class, 'GetPropertiessearch']);

Route::get('/collections', [CollectionController::class, 'index']);
Route::post('/collections', [CollectionController::class, 'store']);

Route::get('/collection-section/{key}', [CollectionSectionController::class, 'show']);
Route::post('/collection-section/{key}', [CollectionSectionController::class, 'storeOrUpdate']);


Route::get('/sections/{key}', [CollectionSectionController::class, 'show']);
Route::post('/sections/{key}', [CollectionSectionController::class, 'storeOrUpdate']);


Route::get('/feature-boxes', [FeatureBoxController::class, 'index']);
Route::post('/feature-boxes', [FeatureBoxController::class, 'storeOrUpdate']); // create
Route::put('/feature-boxes/{id}', [FeatureBoxController::class, 'storeOrUpdate']); // update
Route::delete('/feature-boxes/{id}', [FeatureBoxController::class, 'destroy']);   // delete


Route::get('/about-detail', [HospitalitySectionController::class, 'index']);
Route::post('/heading', [HospitalitySectionController::class, 'update']);

Route::get('/hero-section', [HeroSectionController::class, 'show']);
Route::post('/hero-section', [HeroSectionController::class, 'storeOrUpdate']);

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
