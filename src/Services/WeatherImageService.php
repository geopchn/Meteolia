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

    private string $projectDir;
    private array $config;

    public function __construct(KernelInterface $appKernel, ParameterBagInterface $parameterBag)
    {
        $this->projectDir = $appKernel->getProjectDir();
        $this->config = $parameterBag->get('image');
    }

    public function generate(string $weatherCondition, float $temperature, string $locationName ): string
    {
        $backgroundImage = imagecreatefrompng($this->projectDir.'/public/assets/images/backgrounds/' . $this->getWeatherBackground($weatherCondition));

        $this->addDateText($backgroundImage);
        $this->addWeatherIcon($backgroundImage, $weatherCondition);
        $this->addTemperatureText($backgroundImage, $temperature);
        $this->addLocationText($backgroundImage, $locationName);

        $imagePath = $this->projectDir. $this->config['uploadDir'] . 'weather.png';

        imagepng($backgroundImage, $imagePath);
        imagedestroy($backgroundImage);

        return $imagePath;
    }

    private function addDateText(GdImage $backgroundImage): void
    {
        $font = $this->getFontConfig(self::KEY_DATE)['font'];
        $fontSize = $this->getFontConfig(self::KEY_DATE)['size'];
        $textY = self::DATE_MARGIN_TOP;

        $dateFormatted = $this->getFormattedDate();

        $textX = $this->getXTextCenter($fontSize, $font, $dateFormatted, $backgroundImage);
        $textColor = imagecolorallocate($backgroundImage, 255, 255, 255);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $dateFormatted);
    }

    private function addWeatherIcon(GdImage $backgroundImage, string $weatherCondition): void
    {
        $weatherIconName = $this->getWeatherIconName($weatherCondition);
        $weatherImage = imagecreatefrompng($this->projectDir.'/public/assets/images/weather-icons/'.$weatherIconName);

        $imageX = (imagesx($backgroundImage) - imagesx($weatherImage)) / 2;
        $imageY = ((imagesy($backgroundImage) - imagesy($weatherImage)) / 2) - 50;

        imagecopy($backgroundImage, $weatherImage, $imageX, $imageY, 0, 0, imagesx($weatherImage), imagesy($weatherImage));
        imagedestroy($weatherImage);
    }

    private function addTemperatureText(GdImage $backgroundImage, float $temperature): void
    {
        $font = $this->getFontConfig(self::KEY_TEMPERATURE)['font'];
        $fontSize = $this->getFontConfig(self::KEY_TEMPERATURE)['size'];
        $temperature = round($temperature) . '°C';

        $textX = $this->getXTextCenter($fontSize, $font, $temperature, $backgroundImage);
        $textY = self::TEMP_POSITION_Y;

        $textColor = imagecolorallocate($backgroundImage, 255, 255, 255);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $temperature);
    }

    private function addLocationText(GdImage $backgroundImage, string $city): void
    {
        $font = $this->getFontConfig(self::KEY_CITY)['font'];
        $fontSize = $this->getFontConfig(self::KEY_CITY)['size'];
        $textY = imagesx($backgroundImage) - 50;

        $textX = $this->getXTextCenter($fontSize, $font, $city, $backgroundImage);
        $textColor = imagecolorallocate($backgroundImage, 255, 255, 255);

        imagettftext($backgroundImage, $fontSize, 0, $textX, $textY, $textColor, $font, $city);
    }

    private function getWeatherIconName(string $weatherCondition): string
    {
        return match ($weatherCondition) {
            'Clear' => 'sun.png',
            'Rain' => 'rain.png',
            'Snow' => 'snow.png',
            'Thunderstorm' => 'thunder.png',
            default => 'cloud.png',
        };
    }

    private function getWeatherBackground(string $weatherCondition): string
    {
        if ($weatherCondition === 'Clear'){
            $weatherIconName = 'blue.png';
        } else {
            $weatherIconName = 'grey.png';
        }

        return $weatherIconName;
    }

    private function getXTextCenter(int $fontSize, string $font, string $text, \GdImage|bool $backgroundImage): int|float
    {
        $textBoundingBox = imagettfbbox($fontSize, 0, $font, $text);
        $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
        $backgroundWidth = imagesx($backgroundImage);

        return ($backgroundWidth - $textWidth) / 2;
    }

    private function getFontConfig(string $type): array
    {
        return [
            'font' => $this->projectDir . $this->config['elements'][$type]['font'],
            'size' => $this->config['elements'][$type]['size']
        ];
    }

    private function getFormattedDate(): string
    {
        $date = new DateTime();
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        $formatter->setPattern('EEEE d MMMM');
        return ucfirst($formatter->format($date));
    }

    public function formatFileName(string $fileName): string
    {
        $fileName = strtolower($fileName);
        $fileName = str_replace(' ', '_', $fileName);
        $fileName = trim($fileName, '_');

        return $fileName;
    }

}