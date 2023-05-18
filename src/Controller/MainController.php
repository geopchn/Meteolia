<?php

namespace App\Controller;

use App\Form\LocationType;
use App\Services\WeatherImageService;
use App\Services\WeatherApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        if ($form->isSubmitted()) {

            $data = $form->getData();
            $locationName = $data['locationName'];
            $config = $parameterBag->get('weather');

            $cacheKey = $config['cache']['prefix'].$locationName;

            $weatherResult = $cache->get($cacheKey, function (ItemInterface $item) use ($weatherService, $locationName, $config) {
                $item->expiresAfter($config['cache']['ttl']);
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

            $imageService->generate($weatherResult);

        }

        return $this->render('main/home.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
