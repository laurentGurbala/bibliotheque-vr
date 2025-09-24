<?php

namespace App\Controller\API;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\TextUI\XmlConfiguration\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/games', name: 'api_')]
final class GameController extends AbstractController
{
    #[Route('', name: 'api_games', methods: ['GET'])]
    public function list(GameRepository $repo): JsonResponse
    {
        $games = $repo->findAll();
        return $this->json($games, JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_game_detail', methods: ['GET'])]
    public function getDetail(Game $game): JsonResponse
    {
        return $this->json($game, JsonResponse::HTTP_OK);
    }

    #[Route('', name: 'api_games_create', methods: ['POST'])]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $game = $serializer->deserialize($request->getContent(), Game::class, 'json');
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Invalid JSON format'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Validation
        $errors = $validator->validate($game);
        if (count($errors) > 0) {
            return $this->json(
                $errors,
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $em->persist($game);
        $em->flush();

        return $this->json($game, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    public function delete(Game $game, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($game);
        $em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
