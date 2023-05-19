<?php

namespace App\Services;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherApiService
{
    private HttpClientInterface $httpClient;
    private array $config;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag)
    {
        $this->httpClient = $httpClient;
        $this->config = $parameterBag->get('weather');
    }


    public function getWeatherByCity(string $city): array
    {
        try {
            $coordinates = $this->getCoordinatesByCity($city);
        } catch (Exception) {
            throw new Exception('La localisation n\'a pas été trouvée');
        }

        try {
            $weather = $this->getWeatherByCoordinates($coordinates['lat'], $coordinates['lon']);
        } catch (Exception) {
            throw new Exception('Erreur lors de la récupération des données météo');
        }

        $weather['name'] = $coordinates['name'];

        return $weather;
    }

    private function getCoordinatesByCity(string $city): array
    {
        $query = [
            'q' => $city,
            'limit' => 1,
        ];

        $coordinatesData = $this->requestToApi($this->config['endpoints']['geocoding'], $query);

        return [
            'name' => $coordinatesData[0]['name'],
            'lat' => $coordinatesData[0]['lat'],
            'lon' => $coordinatesData[0]['lon'],
        ];
    }

    private function getWeatherByCoordinates(float $lat, float $lon): array
    {
        $query = [
            'lat' => $lat,
            'lon' => $lon,
            'units' => 'metric'
        ];

        return $this->requestToApi($this->config['endpoints']['weather'], $query);
    }

    private function requestToApi(string $endpoint, array $query): array
    {
        $query['appid'] = $this->config['key'];

        $response = $this->httpClient->request(
            'GET',
            $this->config['url'] .  $endpoint,
            [
                'query' => $query,
            ]
        );

        return $response->toArray();
    }
}