<?php

declare(strict_types=1);

namespace Modules\Shared\OpenRouter\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\DTO\CompanyProfile\GeoCodingDTO;

class OpenRouterGeoService
{
    /**
     * Connect to OpenRouter API and get location information
     *
     * @param GeoCodingDTO $geoCodingDTO
     * @return array
     */
    public function getLocationFromOpenRouter(GeoCodingDTO $geoCodingDTO,$cities,$expectedCity,$expectedState): array
    {
       return Cache::rememberForever('openrouter_'.$geoCodingDTO->getLatitude().'_'.$geoCodingDTO->getLongitude(),function () use ($geoCodingDTO,$cities,$expectedCity,$expectedState) {
            try {
                // Get API credentials from config
                $apiKey = env('OPENROUTER_API_KEY');
                $apiUrl = env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

                // Prepare request data for OpenRouter chat completions API
                $requestData = [
                    "model" => "google/gemini-2.5-flash", // or any other model you prefer
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => "check this latlng and match with this list of cities - Latitude: ".$geoCodingDTO->getLatitude() . ", Longitude: " . $geoCodingDTO->getLongitude().' while expected city is '.$expectedCity.' and expected state is '.$expectedState.' Please match with one of this cities at the end only return as city_id and state_id and city_name and state_name and ( recommended city to be added to the list provided with all info into object ( name,state_id,state_code,country_id,country_code,latitude,longitude) with boolean add or rename):'."$cities"
                        ]
                    ],
                    "stream" => false, // Disable streaming for easier parsing
                    "max_tokens" => 500,
                    "temperature" => 0.1,
                    "response_format" => ["type" => "json_object"],
                ];

                // Use curl for better control over the request
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);

                // Execute the request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                // Check for curl errors
                if ($response === false) {
                    Log::error('OpenRouter API connection error: ' . $error);
                    return [
                        'success' => false,
                        'message' => 'Failed to connect to OpenRouter API: ' . $error,
                        'data' => null
                    ];
                }

                // Check for HTTP errors
                if ($httpCode < 200 || $httpCode >= 300) {
                    Log::error('OpenRouter API HTTP error: ' . $httpCode . ' - ' . $response);
                    return [
                        'success' => false,
                        'message' => 'OpenRouter API returned HTTP error: ' . $httpCode,
                        'data' => $response ? json_decode($response, true) : null
                    ];
                }

                // Process the response
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('OpenRouter API invalid JSON response: ' . json_last_error_msg());
                    return [
                        'success' => false,
                        'message' => 'Invalid JSON response from OpenRouter API',
                        'data' => $response
                    ];
                }

                // Extract the content from the OpenRouter response
                $locationContent = '';
                if (isset($data['choices'][0]['message']['content'])) {
                    $locationContent = $data['choices'][0]['message']['content'];
                }

                return [
                    'success' => true,
                    'message' => 'Location data retrieved successfully',
                    'data' => [
                        'raw_response' => $data,
                        'location_content' => $locationContent
                    ]
                ];
            } catch (Exception $e) {
                Log::error('OpenRouter API exception: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Error processing OpenRouter API request: ' . $e->getMessage(),
                    'data' => null
                ];
            }
        });

    }

    /**
     * Compare location data from Google Maps with data from the database
     *
     * @param array $googleMapsData Array containing country, state, city data from Google Maps
     * @return array Comparison results
     */
    public function compareLocationData(array $googleMapsData): array
    {
        // Extract location data from Google Maps response
        $googleCountry = $googleMapsData[0] ?? null;
        $googleState = $googleMapsData[1] ?? null;
        $googleCity = $googleMapsData[2] ?? null;

        // Get country, state, city names for comparison
        $googleCountryName = $googleCountry ? ($googleCountry->name ?? '') : '';
        $googleStateName = $googleState ? ($googleState->name ?? '') : '';
        $googleCityName = $googleCity ? ($googleCity->name ?? '') : '';

        return [
            'google_data' => [
                'country' => $googleCountry,
                'state' => $googleState,
                'city' => $googleCity,
            ],
            'db_data' => [
                'country' => $googleCountry,
                'state' => $googleState,
                'city' => $googleCity,
            ],
            'matches' => [
                'country' => $googleCountry ? true : false, // Already matched from DB in geoCoding method
                'state' => $googleState ? true : false,    // Already matched from DB in geoCoding method
                'city' => $googleCity ? true : false,      // Already matched from DB in geoCoding method
            ]
        ];
    }
}
