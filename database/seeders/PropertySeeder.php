<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Property;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $accounts = Account::all();

         foreach ($accounts as $account) {
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/ContentIndex/'.$account->id);

            $data = $response->json();

            foreach ($data['properties'] as $property) {
                Property::updateOrCreate(
                    ['id' => $property['propertyId']],
                    [
                        'account_id' => $account->id,
                        'active' => $property['active'] ?? null,
                        'listingUrl' => $property['listingUrl'] ?? null,
                    ]
                );
            }
        }
    }
}
