<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\PropertyExtra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPropertyExtrasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        foreach (Property::all() as $property) {
            Log::info("Fetching extras for property_id: {$property->id}");


            $xmlBody = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<scAPI>
  <client>
    <ID>22826</ID>
    <key>cddc35d0-1491-4b04-969c-9f8419ef0e99</key>
    <siteID>46172</siteID>
    <propertyID>{$property->id}</propertyID>
  </client>
</scAPI>
XML;

            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
                'Accept'       => 'application/xml',
            ])->send('POST', "https://api.supercontrol.co.uk/api/endpoint/v1/GetProperty", [
                'body' => $xmlBody,
            ]);

            if ($response->failed()) {
                Log::error(" Failed to fetch extras for property_id: {$property->id}");
                continue;
            }


            $xml = simplexml_load_string($response->body(), "SimpleXMLElement", LIBXML_NOCDATA);


            $extras = $xml->xpath('//option_db');

            if (empty($extras)) {
                Log::warning("No extras found for property_id: {$property->id}");
                continue;
            }

            foreach ($extras as $extra) {
                $extraArray = (array) $extra;

                PropertyExtra::updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'extra_id'    => (string)($extra->id ?? null),
                    ],
                    [
                        'name'             => (string)($extra->name ?? null),
                        'description'      => (string)($extra->description ?? null),
                        'price_rate'       => (float)($extra->price ?? 0),
                        'price_total'      => (float)($extra->price ?? 0),
                        'price_unit'       => (string)($extra->pricetype ?? null),
                        'price_unit_label' => (string)($extra->pricetype ?? null),
                        'currency_symbol'  => isset($extra->currency)
                            ? (string)$extra->currency
                            : '£',
                        'qty'              => (int)($extra->maxQty ?? 0),
                        'is_default'       => (int)($extra->defaultQty ?? 0),
                        'img'              => (string)($extra->img ?? null),
                        'option_payment'   => (string)($extra->optionPayment ?? null),
                        'available'        => isset($extra->available)
                            ? (string)$extra->available
                            : null,
                    ]
                );
            }

            Log::info("Extras updated for property_id: {$property->id}");
        }
    }
}
