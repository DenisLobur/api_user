<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends AbstractController
{
    #[Route('/v1/api/users', name: 'api_users', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function users(Request $request): JsonResponse
    {
        $method = $request->getMethod();
        switch ($method) {
            case 'GET':
                return $this->json([
                    ['id' => 1, 'name' => 'Some name'],
                    ['id' => 2, 'name' => 'Some2 name2'],
                ]);
            case 'POST':
                return $this->json(['message' => 'User created'], 201);
            case 'PUT':
                return $this->json(['message' => 'User updated']);
            case 'DELETE':
                return $this->json(['message' => 'User deleted']);
            default:
                return $this->json(['error' => 'Method not allowed'], 405);
        }
    }
}
