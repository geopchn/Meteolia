<?php

namespace App\Controller;

use App\Form\LocationType;
use App\Services\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'main_home')]
    public function home(Request $request, WeatherService $weatherService, CacheInterface $cache): Response
    {
        $form = $this->createForm(LocationType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $data = $form->getData();
            $locationName = $data['locationName'];

            $cacheKey = 'weather_data_'.$locationName;

            $weatherResult = $cache->get($cacheKey, function (ItemInterface $item) use ($weatherService, $locationName) {
                $item->expiresAfter(7200);
                try {
                    return $weatherService->getWeatherByCity($locationName);
                }catch (\Exception $e) {
                    return $e->getMessage();
                }
            });

            if (!is_array($weatherResult) ) {
                $this->addFlash('success', $weatherResult);
                return $this->redirectToRoute('main_home');
            }

        }

        return $this->render('main/home.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
