<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends AbstractController
{
    #[Route('/v1/api/users', name: 'api_users_get', methods: ['GET'])]
    public function getUserById(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        if (empty($id)) {
            return $this->json([
                'error' => 'Missing required attribute',
                'missing' => ['id']
            ], 400);
        }
        // TODO: fetch the user by id from the database
        return $this->json([
            'id' => $id,
            'login' => 'sample', // TODO: Replace with actual user data
            'pass' => 'sample',  // TODO: Replace with actual user data
            'phone' => '12345678' // TODO: Replace with actual user data
        ]);
    }

    #[Route('/v1/api/users', name: 'api_users_post', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['login', 'pass', 'phone'];
        $missing = array_filter($required, fn($field) => empty($data[$field]));
        if ($missing) {
            return $this->json([
                'error' => 'Missing required attributes',
                'missing' => array_values($missing)
            ], 400);
        }
        // TODO: create the user entity in the database
        return $this->json([
            'message' => 'User created',
            'id' => 1, // Replace with actual created user ID
            'login' => $data['login'],
            'pass' => $data['pass'],
            'phone' => $data['phone']
        ], 201);
    }

    #[Route('/v1/api/users', name: 'api_users_put', methods: ['PUT'])]
    public function updateUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['id', 'login', 'pass', 'phone'];
        $missing = array_filter($required, fn($field) => empty($data[$field]));
        if ($missing) {
            return $this->json([
                'error' => 'Missing required attributes',
                'missing' => array_values($missing)
            ], 400);
        }
        // TODO: update the user entity in the database
        return $this->json([
            'message' => 'User updated',
            'id' => $data['id']
        ]);
    }

    #[Route('/v1/api/users', name: 'api_users_delete', methods: ['DELETE'])]
    public function deleteUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;
        if (empty($id)) {
            return $this->json([
                'error' => 'Missing required attribute',
                'missing' => ['id']
            ], 400);
        }
        // TODO: delete the user by id from the database
        return $this->json(['message' => 'User deleted']);
    }
}
