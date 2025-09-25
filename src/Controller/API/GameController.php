<?php

namespace App\Controller\API;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/games', name: 'api_')]
final class GameController extends AbstractController
{
    #[Route('', name: 'api_games', methods: ['GET'])]
    public function getAll(
        GameRepository $gameRepository,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        // Paramètres de pagination
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        // Cache basé sur la page et la limite
        $cacheKey = 'games_page_' . $page . '_limit_' . $limit;
        $games = $cache->get($cacheKey, function(ItemInterface $item) use ($gameRepository, $page, $limit) {
            $item->tag('gamesCache');
            return $gameRepository->findAllWithPagination($page, $limit);
        });

        return $this->json($games, JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_game_detail', methods: ['GET'])]
    public function getDetail(Game $game, TagAwareCacheInterface $cache): JsonResponse
    {
        // Cache basé sur l'ID du jeu
        $cacheKey = 'game_' . $game->getId();
        $gameData = $cache->get($cacheKey, function(ItemInterface $item) use ($game) {
            $item->tag('gamesCache');
            return $game;
        });

        return $this->json($gameData, JsonResponse::HTTP_OK);
    }

    #[Route('', name: 'api_games_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour créer un jeu.")]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
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

        // Invalidation du cache
        $cache->invalidateTags(['gamesCache']);

        return $this->json($game, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_game_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour modifier un jeu.")]
    public function update(
        Game $game, 
        Request $request, 
        SerializerInterface $serializer, 
        ValidatorInterface $validator, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
        ): JsonResponse
    {
        try {
            $updatedGame = $serializer->deserialize($request->getContent(), Game::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $game]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Invalid JSON format'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Validation
        $errors = $validator->validate($updatedGame);
        if (count($errors) > 0) {
            return $this->json(
                $errors,
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $em->persist($updatedGame);
        $em->flush();

        // Invalidation du cache
        $cache->invalidateTags(['gamesCache']);

        return $this->json($updatedGame, JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_game_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer un jeu.")]
    public function delete(
        Game $game, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
        ): JsonResponse
    {
        $em->remove($game);
        $em->flush();

        // Invalidation du cache
        $cache->invalidateTags(['gamesCache']);
        
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
