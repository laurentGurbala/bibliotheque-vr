<?php

namespace App\Controller\API;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/games/{id}', name: 'api_game_detail', methods: ['GET'])]
    public function getDetailGame(Game $game): JsonResponse
    {
        return $this->json($game, JsonResponse::HTTP_OK);
    }

    #[Route('/games/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    public function delete(Game $game, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($game);
        $em->flush();
        
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
