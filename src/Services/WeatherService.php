<?php

namespace App\Services;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
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
        } catch (Exception $e) {
            throw new Exception('La localisation n\'a pas été trouvée');
        }

        try {
            $weather = $this->getWeatherByCoordinates($coordinates['lat'], $coordinates['lon']);
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la récupération des données météo');
        }

        return $weather;
    }

    public function getCoordinatesByCity(string $city): array
    {
        $query = [
            'q' => $city,
            'limit' => 1,
        ];

        $coordinatesData = $this->requestToApi($this->config['endpoints']['geocoding'], $query);

        return [
            'lat' => $coordinatesData[0]['lat'],
            'lon' => $coordinatesData[0]['lon'],
        ];
    }

    public function getWeatherByCoordinates(float $lat, float $lon): array
    {
        $query = [
            'lat' => $lat,
            'lon' => $lon,
            'units' => 'metric'
        ];

        return $this->requestToApi($this->config['endpoints']['weather'], $query);
    }

    public function requestToApi(string $endpoint, array $query): array
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