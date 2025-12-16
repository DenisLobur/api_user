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
use Symfony\Bundle\SecurityBundle\Security;

class UsersController extends AbstractController
{
    public function __construct(
        private Security $security
    ) {
    }

    private function isRoot(): bool
    {
        return $this->isGranted('ROLE_ROOT');
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    private function canAccessUser(int $targetUserId): bool
    {
        if ($this->isRoot()) {
            return true;
        }

        $currentUser = $this->getCurrentUser();
        return $currentUser && $currentUser->getId() === $targetUserId;
    }

    #[Route('/v1/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function getUserById($id, UserRepository $userRepository): JsonResponse
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->json([
                'error' => 'Missing required attribute',
                'missing' => ['id']
            ], 400);
        }

        $id = (int) $id;

        // Check if user can access this resource
        if (!$this->canAccessUser($id)) {
            return $this->json([
                'error' => 'Access denied',
                'message' => 'You can only access your own user data'
            ], 403);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'error' => 'User not found',
                'id' => $id
            ], 404);
        }

        return $this->json([
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

        // Regular users can only create their own account (self-registration is allowed)
        // Root users can create any account
        // Note: This endpoint allows creating new users; for regular users,
        // they can only modify their own data via PUT

        $user = new User();
        $user->setEmail($data['email']);

        $hashedPassword = $userPasswordHasher->hashPassword($user, $data['password']);

        $user->setPassword($hashedPassword);

        if (method_exists($user, 'setPhone')) {
            $user->setPhone($data['phone']);
        }

        // Set default role as ROLE_USER, only root can assign ROLE_ROOT
        if ($this->isRoot() && isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $userRepository->add($user, true);

        return $this->json([
            'message' => 'User created',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(), // here we're returning hashed password
            'phone' => method_exists($user, 'getPhone') ? $user->getPhone() : null
        ], 201);
    }

    #[Route('/v1/api/users', name: 'api_users_put', methods: ['PUT'])]
    public function updateUser(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['id', 'email', 'password', 'phone'];
        $missing = array_filter($required, fn($field) => empty($data[$field]));
        if ($missing) {
            return $this->json([
                'error' => 'Missing required attributes',
                'missing' => array_values($missing)
            ], 400);
        }

        $targetUserId = (int) $data['id'];

        // Check if user can access this resource
        if (!$this->canAccessUser($targetUserId)) {
            return $this->json([
                'error' => 'Access denied',
                'message' => 'You can only update your own user data'
            ], 403);
        }

        $user = $userRepository->find($targetUserId);
        if (!$user) {
            return $this->json([
                'error' => 'User not found',
                'id' => $data['id']
            ], 404);
        }

        $user->setEmail($data['email']);
        $hashedPassword = $userPasswordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        if (method_exists($user, 'setPhone')) {
            $user->setPhone($data['phone']);
        }

        // Only root can change roles
        if ($this->isRoot() && isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $userRepository->add($user, true);

        return $this->json([
            'message' => 'User updated',
            'id' => $user->getId()
        ]);
    }

    #[Route('/v1/api/users', name: 'api_users_delete', methods: ['DELETE'])]
    public function deleteUser(Request $request, UserRepository $userRepository): JsonResponse
    {
        // Only root users can delete
        if (!$this->isRoot()) {
            return $this->json([
                'error' => 'Access denied',
                'message' => 'Only root users can delete users'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;
        if (empty($id)) {
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

        $userRepository->remove($user, true);

        return $this->json(['message' => 'User deleted']);
    }
}
