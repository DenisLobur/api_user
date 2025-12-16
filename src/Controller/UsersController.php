<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersController extends AbstractController
{
    #[Route('/v1/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function getUserById($id, UserRepository $userRepository): JsonResponse
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->json([
                'error' => 'Missing required attribute',
                'missing' => ['id']
            ], 400);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'error' => 'User not found',
                'id' => $id
            ], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'login' => method_exists($user, 'getLogin') ? $user->getLogin() : ($user->getEmail() ?? ''),
            'pass' => method_exists($user, 'getPass') ? $user->getPass() : ($user->getPassword() ?? ''),
            'phone' => method_exists($user, 'getPhone') ? $user->getPhone() : ''
        ]);
    }

    #[Route('/v1/api/users', name: 'api_users_post', methods: ['POST'])]
    public function createUser(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['email', 'password', 'phone'];
        $missing = array_filter($required, fn($field) => empty($data[$field]));
        if ($missing) {
            return $this->json([
                'error' => 'Missing required attributes',
                'missing' => array_values($missing)
            ], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);

        $hashedPassword = $userPasswordHasher->hashPassword($user, $data['password']);

        $user->setPassword($hashedPassword);

        if (method_exists($user, 'setPhone')) {
            $user->setPhone($data['phone']);
        }

        $userRepository->add($user, true);

        return $this->json([
            'message' => 'User created',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'phone' => method_exists($user, 'getPhone') ? $user->getPhone() : null
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
