<?php

namespace App\Tests\Functional\API;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    /**
     * Test la liste de jeux
     */
    public function testList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/games');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());
    }

    /**
     * Test le détail d'un jeu
     */
    public function testGetDetail(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/games/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals('Jeu VR 1', $responseData['title']);
    }

    /**
     * Test la création d'un jeu
     */
    public function testCreate(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Nouveau Jeu',
                'description' => 'Description du nouveau jeu',
                'releaseAt' => '2023-01-01',
                'studio' => 'Studio XYZ'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals('Nouveau Jeu', $responseData['title']);
    }

    /**
     * Test la création d'un jeu avec un JSON invalide
     */
    public function testCreateInvalidJson(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalidJson:}'
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid JSON format', $responseData['error']);
    }

    /**
     * Test la création d'un jeu avec des données invalides
     */
    public function testCreateInvalidData(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
    }

    /**
     * Test la mise à jour d'un jeu
     */
    public function testUpdate(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/games/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'description' => 'Description modifiée',
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEquals('Description modifiée', $responseData['description']);
    }

    /**
     * Test la mise à jour d'un jeu avec un JSON invalide
     */
    public function testUpdateInvalidJson(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/games/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalidJson:}'
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid JSON format', $responseData['error']);
    }

    /**
     * Test la mise à jour d'un jeu inexistant
     */
    public function testUpdateNonExistent(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/games/99999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'description' => 'Description modifiée',
            ])
        );

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Test la mise à jour d'un jeu avec des données invalides
     */
    public function testUpdateInvalidData(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/games/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => '',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
    }

    /**
     * Test de la suppression d'un jeu
     */
    public function testDelete(): void
    {
        $client = static::createClient();
        // Créer un jeu à supprimer
        $client->request(
            'POST',
            '/api/games',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Nouveau Jeu',
                'description' => 'Description du nouveau jeu',
                'releaseAt' => '2023-01-01',
                'studio' => 'Studio XYZ'
            ])
        );

        $game = json_decode($client->getResponse()->getContent(), true);
        $id = $game['id'];

        // Supprimer
        $client->request('DELETE', '/api/games/' . $id);
        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * Test de la suppression d'un jeu inexistant
     */
    public function testDeleteNonExistent(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/games/99999');
        $this->assertResponseStatusCodeSame(404);
    }
}
