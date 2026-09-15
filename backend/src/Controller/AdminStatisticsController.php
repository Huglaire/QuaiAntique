<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/statistics')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatisticsController extends AbstractController
{
    /**
     * Génère et retourne les statistiques du restaurant.
     *
     * @param StatisticsService $statisticsService
     * @return JsonResponse
     */
    #[Route('', name: 'admin_statistics', methods: ['GET'])]
    public function index(
        StatisticsService $statisticsService
    ): JsonResponse {
        try {
            $statistics =
                $statisticsService->generateStatistics();

            return $this->json([
                'generatedAt' =>
                    $statistics->getGeneratedAt()?->format(
                        \DateTimeInterface::ATOM
                    ),

                'averageGuestsPerBooking' =>
                    $statistics->getAverageGuestsPerBooking(),

                'averageOccupancyByDay' =>
                    $statistics->getAverageOccupancyByDay(),

                'busiestTimeSlot' =>
                    $statistics->getBusiestTimeSlot(),
            ]);

        } catch (\RuntimeException $exception) {
            return $this->json(
                [
                    'message' =>
                        $exception->getMessage(),
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}