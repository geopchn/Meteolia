<?php

namespace App\Services;

use DateTime;
use GdImage;
use IntlDateFormatter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class WeatherImageService
{
    const KEY_CITY = 'city';
    const KEY_DATE = 'date';
    const KEY_TEMPERATURE = 'temp';
    const TEMP_POSITION_Y = 400;
    const DATE_MARGIN_TOP = 50;
    const TEXT_OPACITY = 0;

    private string $projectDir;
    private array $config;

    public function __construct(KernelInterface $appKernel, ParameterBagInterface $parameterBag)
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        $this->projectDir = $appKernel->getProjectDir();
        $this->config = $parameterBag->get('image');
    }

    public function generate(array $weather): void
    {
        $backgroundImage = imagecreatefrompng($this->projectDir.'/public/assets/images/backgrounds/blue.png');

        $this->addDateText($backgroundImage);
        $this->addWeatherIcon($backgroundImage, $weather);
        $this->addTemperatureText($backgroundImage, $weather);
        $this->addLocationText($backgroundImage, $weather);

        imagepng($backgroundImage, $this->projectDir.'/public/images-weather/weather.png');

        imagedestroy($backgroundImage);
    }

    public function addDateText(GdImage|bool $backgroundImage): void
    {
        $font = $this->getFontConfig(WeatherImageService::KEY_DATE)['font'];
        $fontSize = $this->getFontConfig(WeatherImageService::KEY_DATE)['size'];
        $textY = WeatherImageService::DATE_MARGIN_TOP;

        $date = new DateTime();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        $formatter->setPattern('EEEE d MMMM');
        $dateFormatted =  ucfirst($formatter->format($date));

        $textX = $this->getXTextCenter($fontSize, $font, $dateFormatted, $backgroundImage);
        $textColor = imagecolorallocatealpha($backgroundImage, 255, 255, 255, WeatherImageService::TEXT_OPACITY);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $dateFormatted);
    }

    private function addWeatherIcon(GdImage|bool $backgroundImage, array $weather): void
    {
        $weatherIconName = $this->getWeatherIconName($weather);
        $weatherImage = imagecreatefrompng($this->projectDir.'/public/assets/images/weather-icons/'.$weatherIconName);

        $imageX = (imagesx($backgroundImage) - imagesx($weatherImage)) / 2;
        $imageY = ((imagesy($backgroundImage) - imagesy($weatherImage)) / 2) - 50;

        imagecopy($backgroundImage, $weatherImage, $imageX, $imageY, 0, 0, imagesx($weatherImage), imagesy($weatherImage));
        imagedestroy($weatherImage);
    }

    private function addTemperatureText(GdImage|bool $backgroundImage, array $weather): void
    {
        $font = $this->getFontConfig(WeatherImageService::KEY_TEMPERATURE)['font'];
        $fontSize = $this->getFontConfig(WeatherImageService::KEY_TEMPERATURE)['size'];
        $temp = $weather['main']['temp'];
        $temp = round($temp) . '°C';

        $textX = $this->getXTextCenter($fontSize, $font, $temp, $backgroundImage);
        $textY = WeatherImageService::TEMP_POSITION_Y;

        $textColor = imagecolorallocatealpha($backgroundImage, 255, 255, 255, WeatherImageService::TEXT_OPACITY);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $temp);
    }

    private function addLocationText(\GdImage|bool $backgroundImage, array $weather): void
    {
        $font = $this->getFontConfig(WeatherImageService::KEY_CITY)['font'];
        $fontSize = $this->getFontConfig(WeatherImageService::KEY_CITY)['size'];
        $textY = imagesx($backgroundImage) - 50;
        $city = $weather['name'];

        $textX = $this->getXTextCenter($fontSize, $font, $city, $backgroundImage);
        $textColor = imagecolorallocatealpha($backgroundImage, 255, 255, 255, WeatherImageService::TEXT_OPACITY);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $city);
    }

    private function getWeatherIconName(array $weather): string
    {
        if ($weather['weather'][0]['main'] === 'Clear'){
            $weatherIconName = 'sun.png';
        } else {
            $weatherIconName = 'cloud.png';
        }
        return $weatherIconName;
    }

    public function getXTextCenter(int $fontSize, string $font, string $text, \GdImage|bool $backgroundImage): int|float
    {
        $textBoundingBox = imagettfbbox($fontSize, 0, $font, $text);
        $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
        $backgroundWidth = imagesx($backgroundImage);

        return ($backgroundWidth - $textWidth) / 2;
    }

    private function getFontConfig(string $type): array
    {
        return [
            'font' => $this->projectDir . $this->config[$type]['font'],
            'size' => $this->config[$type]['size']
        ];
    }

}