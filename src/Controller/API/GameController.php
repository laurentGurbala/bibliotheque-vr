<?php

namespace App\Controller\API;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class GameController extends AbstractController
{
    #[Route('/games', name: 'api_games', methods: ['GET'])]
    public function list(GameRepository $repo): JsonResponse
    {
        $games = $repo->findAll();
        return $this->json($games, JsonResponse::HTTP_OK);
    }
    
}
