<?php

namespace App\Tests\Functional\API;

use App\Entity\Game;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    private $client;
    private string $adminToken;
    private string $userToken;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Initialise le client et l'EntityManager
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        // Génère un token JWT pour chaque utilisateur
        $this->adminToken = $this->getAdminToken($container);
        $this->userToken  = $this->getUserToken($container);

    }

    /**
     * Génère un token JWT pour l'admin.
     * @param object $container Le conteneur de services.
     * @return string Le token JWT.
     */
    private function getAdminToken($container): string
    {
        $userRepo = $container->get(UserRepository::class);
        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        $admin = $userRepo->findOneBy(['email' => 'splint@test.fr']);
        return $jwtManager->create($admin);
    }

    /**
     * Génère un token JWT pour l'utilisateur standard.
     * @param object $container Le conteneur de services.
     * @return string Le token JWT.
     */
    private function getUserToken($container): string
    {
        $userRepo = $container->get(UserRepository::class);
        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        $user = $userRepo->findOneBy(['email' => 'user1@test.fr']);
        return $jwtManager->create($user);
    }

    // --------- TESTS GET LIST ---------
    #[DataProvider('gamesProvider')]
    public function testGetGames(string $role, int $expectedStatus): void
    {
        // Prépare les headers en fonction du rôle
        $headers = $this->getHeadersForRole($role);
        $this->client->request('GET', '/api/games', [], [], $headers);
        
        // Vérifie le code de statut
        $this->assertResponseStatusCodeSame($expectedStatus);

        // Vérifie le contenu si OK
        if ($expectedStatus === 200) {
            $responseData = $this->getJsonResponse();

            foreach ($responseData as $game) {
                $this->assertArrayHasKey('title', $game);
                $this->assertNotEmpty($game['title']);
            }
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    public static function gamesProvider(): array
    {
        return [
            'admin' => ["admin", 200],
            'user' => ["user", 200],
            'no auth' => ["", 401],
        ];
    }

    // --------- TESTS GET DETAIL ---------
    #[DataProvider('gameDetailProvider')]
    public function testGetGameDetail(?string $role, int $expectedStatus): void
    {
        
        $game = null;
        $gameId = 999999;

        // Crée un jeu en base si on ne teste pas le 404
        if ($expectedStatus !== 404) {
            $game = $this->createGame('Test Game', 'Test Studio');
            $gameId = $game->getId();
        }

        // Requête GET
        $headers = $this->getHeadersForRole($role);
        $this->client->request('GET', '/api/games/' . $gameId, [], [], $headers);

        // Vérifie le code de statut
        $this->assertResponseStatusCodeSame($expectedStatus);

        // Vérifie le contenu si OK
        if ($expectedStatus === 200) {
            $responseData = $this->getJsonResponse();
            $this->assertEquals($gameId, $responseData['id']);
            $this->assertEquals('Test Game', $responseData['title']);
            
            // Nettoyage
            $this->removeGame($game);
        }
    }

    /**
     * @return array<string, array{0: ?string, 1: int}>
     */
    public static function gameDetailProvider(): array
    {
        return [
            'admin' => ['admin', 200],
            'user' => ['user', 200],
            'no auth' => [null, 401],
            'not found' => ['admin', 404],
        ];
    }

    // --------- TESTS CREATE ---------
    #[DataProvider('createGameProvider')]
    public function testCreateGame(?string $role, array $payload, int $expectedStatus): void
    {
        // Requête POST
        $headers = $this->getHeadersForRole($role);
        $this->client->request('POST', '/api/games', [], [], $headers, json_encode($payload));
        
        // Vérifie le code de statut
        $this->assertResponseStatusCodeSame($expectedStatus);

        // Vérifie le contenu si création réussie
        if ($expectedStatus === 201) {
            $responseData = $this->getJsonResponse();
            $this->assertEquals($payload['title'], $responseData['title']);
            $this->assertEquals($payload['studio'], $responseData['studio']);

            // Nettoyage
            $this->removeGameById($responseData['id']);
        }
    }

    /**
     * @return array<string, array{0: ?string, 1: array, 2: int}>
     */
    public static function createGameProvider(): array
    {
        return [
            'admin valid' => ['admin', ['title' => 'New Game', 'studio' => 'New Studio'], 201],
            'user forbidden' => ['user', ['title' => 'New Game', 'studio' => 'New Studio'], 403],
            'no auth' => [null, ['title' => 'New Game', 'studio' => 'New Studio'], 401],
            'admin invalid' => ['admin', ['studio' => 'New Studio'], 422],
        ];
    }

    // --------- TESTS UPDATE ---------
    #[DataProvider('updateGameProvider')]
    public function testUpdateGame(?string $role, array $payload, int $expectedStatus): void
    {
        // Crée un jeu en base pour le mettre à jour
        $game = $this->createGame('Original Game', 'Original Studio');
        $headers = $this->getHeadersForRole($role);

        // Requête PUT
        $this->client->request('PUT', '/api/games/' . $game->getId(), [], [], $headers, json_encode($payload));
        
        // Vérifie le code de statut
        $this->assertResponseStatusCodeSame($expectedStatus);

        // Vérifie le contenu si mise à jour réussie
        if ($expectedStatus === 200) {
            $responseData = $this->getJsonResponse();
            $this->assertEquals($payload['title'], $responseData['title']);
            $this->assertEquals($payload['studio'], $responseData['studio']);
        }

        // Nettoyage
        $this->removeGame($game);
    }

    /**
     * @return array<string, array{0: ?string, 1: array, 2: int}>
     */
    public static function updateGameProvider(): array
    {
        return [
            'admin valid' => ['admin', ['title' => 'Updated Game', 'studio' => 'Updated Studio'], 200],
            'user forbidden' => ['user', ['title' => 'Updated Game', 'studio' => 'Updated Studio'], 403],
            'no auth' => [null, ['title' => 'Updated Game', 'studio' => 'Updated Studio'], 401],
            'admin invalid' => ['admin', ['title' => ''], 422],
        ];
    }

    // --------- TESTS DELETE ---------
    #[DataProvider('deleteGameProvider')]
    public function testDeleteGame(?string $role, int $expectedStatus): void
    {
        // On crée un jeu à supprimer
        $game = $this->createGame('Delete Game', 'Delete Studio');
        $headers = $this->getHeadersForRole($role);
        $gameId = $game->getId();

        // Requête DELETE
        $this->client->request('DELETE', '/api/games/' . $gameId, [], [], $headers);

        // Vérifie le code de statut
        $this->assertResponseStatusCodeSame($expectedStatus);

        // Vérifie que le jeu est bien supprimé si succès
        if ($expectedStatus === 204) {
            $deletedGame = $this->entityManager->getRepository(Game::class)->find($gameId);
            $this->assertNull($deletedGame);
        } else {
            // Nettoyage si non supprimé
            $this->removeGame($game);
        }
    }

    /**
     * @return array<string, array{0: ?string, 1: int}>
     */
    public static function deleteGameProvider(): array
    {
        return [
            'admin' => ['admin', 204],
            'user' => ['user', 403],
            'no auth' => [null, 401],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null; // Evite les fuites de mémoire
    }

    // --------- HELPERS ---------

    /**
     * Prépare les headers pour une requête en fonction du rôle.
     * @param string|null $role Rôle de l'utilisateur (admin, user, null pour pas d'auth).
     * @return array Tableau des headers.
     */
    private function getHeadersForRole(?string $role): array
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $token = match ($role) {
            'admin' => $this->adminToken,
            'user' => $this->userToken,
            default => null,
        };

        if ($token) {
            $headers['HTTP_Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Récupère et décode la réponse JSON.
     * @return array|null Retourne le tableau décodé ou null si échec.
     */
    private function getJsonResponse(): ?array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Crée un jeu et l'enregistre en base de données.
     * @param string $title titre du jeu.
     * @param string $studio studio du jeu.
     * @return Game Retourne l'entité Game créée.
     */
    private function createGame(string $title, string $studio): Game
    {
        $game = new Game();
        $game->setTitle($title);
        $game->setStudio($studio);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game;
    }

    /**
     * Supprime un jeu de la base de données.
     * @param Game $game Le jeu à supprimer.
     */
    private function removeGame(Game $game): void
    {
        $this->entityManager->remove($game);
        $this->entityManager->flush();
    }

    /**
     * Supprime un jeu de la base de données par son ID.
     * @param int $gameId L'ID du jeu à supprimer.
     */
    private function removeGameById(int $gameId): void
    {
        $game = $this->entityManager->getRepository(Game::class)->find($gameId);
        if ($game) {
            $this->entityManager->remove($game);
            $this->entityManager->flush();
        }
    }
}
