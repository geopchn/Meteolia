<?php

namespace App\Controller;

use App\Form\LocationType;
use App\Services\WeatherImageService;
use App\Services\WeatherApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'main_home')]
    public function home(Request $request, WeatherApiService $weatherService, WeatherImageService $imageService, CacheInterface $cache, ParameterBagInterface $parameterBag): Response
    {
        $form = $this->createForm(LocationType::class);

        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('main/home.html.twig', [
            'form' => $form->createView(), ]);
        }

        $data = $form->getData();
        $locationName = $data['locationName'];

        if ($locationName === null) {
            $this->addFlash('error', 'Veuillez entrer une localisation');
            return $this->redirectToRoute('main_home');
        }

        $configCache = $parameterBag->get('cache');
        $cacheKey = $configCache['prefix'].$locationName;

        $weatherResult = $cache->get($cacheKey, function (ItemInterface $item) use ($weatherService, $locationName, $configCache) {
            $item->expiresAfter($configCache['ttl']);
            try {
                return $weatherService->getWeatherByCity($locationName);
            }catch (\Exception $e) {
                return $e->getMessage();
            }
        });

        if (!is_array($weatherResult)) {
            $this->addFlash('error', $weatherResult);
            return $this->redirectToRoute('main_home');
        }

        $imagePath = $imageService->generate($weatherResult);
        $fileName = $weatherResult['name'] . $parameterBag->get('image')['fileSuffix'];

        $response = new BinaryFileResponse($imagePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $imageService->formatFileName($fileName));

        return $response;
    }
}
