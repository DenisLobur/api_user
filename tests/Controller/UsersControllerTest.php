<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\ApiToken;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    private ?User $rootUser = null;
    private ?User $regularUser = null;
    private ?User $regularUser2 = null;
    private string $rootToken = 'root-test-token-1234567890abcdef1234567890abcdef12345678';
    private string $userToken = 'user-test-token-1234567890abcdef1234567890abcdef12345678';
    private string $user2Token = 'user2-test-token-234567890abcdef1234567890abcdef123456789';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        // Clean up database
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE api_token');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        parent::tearDown();
    }

    private function loadFixtures(): void
    {
        // Clean up first
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE api_token');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Create root user
        $this->rootUser = new User();
        $this->rootUser->setEmail('rt@t.com');
        $this->rootUser->setPassword($this->passwordHasher->hashPassword($this->rootUser, '12345678'));
        $this->rootUser->setPhone('+0000000');
        $this->rootUser->setRoles(['ROLE_ROOT']);
        $this->entityManager->persist($this->rootUser);

        $rootApiToken = new ApiToken();
        $rootApiToken->setUser($this->rootUser);
        $rootApiToken->setToken($this->rootToken);
        $this->entityManager->persist($rootApiToken);

        // Create regular user 1
        $this->regularUser = new User();
        $this->regularUser->setEmail('u1@t.com');
        $this->regularUser->setPassword($this->passwordHasher->hashPassword($this->regularUser, '12345678'));
        $this->regularUser->setPhone('+1111111');
        $this->entityManager->persist($this->regularUser);

        $userApiToken = new ApiToken();
        $userApiToken->setUser($this->regularUser);
        $userApiToken->setToken($this->userToken);
        $this->entityManager->persist($userApiToken);

        // Create regular user 2
        $this->regularUser2 = new User();
        $this->regularUser2->setEmail('u2@t.com');
        $this->regularUser2->setPassword($this->passwordHasher->hashPassword($this->regularUser2, '87654321'));
        $this->regularUser2->setPhone('+2222222');
        $this->entityManager->persist($this->regularUser2);

        $user2ApiToken = new ApiToken();
        $user2ApiToken->setUser($this->regularUser2);
        $user2ApiToken->setToken($this->user2Token);
        $this->entityManager->persist($user2ApiToken);

        $this->entityManager->flush();
    }

    // ==================== Authentication Tests ====================

    public function testRequestWithoutTokenReturns401(): void
    {
        $this->client->request('GET', '/v1/api/users/1');

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Authentication required', $response['error']);
    }

    public function testRequestWithInvalidTokenReturns401(): void
    {
        $this->client->request('GET', '/v1/api/users/1', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token-12345'
        ]);

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    // ==================== GET Tests ====================

    public function testGetUserAsRootSuccess(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('GET', '/v1/api/users/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('login', $response);
        $this->assertEquals('u1@t.com', $response['login']);
    }

    public function testGetOwnUserAsRegularUserSuccess(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('GET', '/v1/api/users/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('login', $response);
        $this->assertEquals('u1@t.com', $response['login']);
    }

    public function testGetOtherUserAsRegularUserReturns403(): void
    {
        $otherUserId = $this->regularUser2->getId();

        $this->client->request('GET', '/v1/api/users/' . $otherUserId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken
        ]);

        $this->assertResponseStatusCodeSame(403);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Access denied', $response['error']);
    }

    public function testGetNonExistentUserReturns404(): void
    {
        $this->client->request('GET', '/v1/api/users/99999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken
        ]);

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found', $response['error']);
    }

    public function testGetUserWithInvalidIdReturns400(): void
    {
        $this->client->request('GET', '/v1/api/users/invalid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken
        ]);

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required attribute', $response['error']);
    }

    // ==================== POST Tests ====================

    public function testCreateUserAsRootSuccess(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'n@ew.com',
            'password' => '12345678',
            'phone' => '+3333333'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User created', $response['message']);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('n@ew.com', $response['email']);
    }

    public function testCreateUserAsRegularUserSuccess(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'ne@w.com',
            'password' => '12345678',
            'phone' => '+4444444'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User created', $response['message']);
    }

    public function testCreateUserWithMissingFieldsReturns400(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'te@st.co'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required attributes', $response['error']);
        $this->assertContains('password', $response['missing']);
        $this->assertContains('phone', $response['missing']);
    }

    public function testCreateUserWithPasswordTooLongReturns400(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'te@st.co',
            'password' => '123456789', // 9 characters - too long
            'phone' => '+5555555'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Validation failed', $response['error']);
        $this->assertStringContainsString('Password cannot be longer than 8 characters', $response['message']);
    }

    public function testCreateUserWithRolesAsRootSuccess(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'ad@mn.co',
            'password' => '12345678',
            'phone' => '+6666666',
            'roles' => ['ROLE_ROOT']
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Verify the user has the role
        $user = $this->userRepository->findOneBy(['email' => 'ad@mn.co']);
        $this->assertContains('ROLE_ROOT', $user->getRoles());
    }

    public function testCreateUserWithRolesAsRegularUserIgnoresRoles(): void
    {
        $this->client->request('POST', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'no@rt.co',
            'password' => '12345678',
            'phone' => '+7777777',
            'roles' => ['ROLE_ROOT']
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Verify the user does NOT have ROLE_ROOT (only ROLE_USER)
        $user = $this->userRepository->findOneBy(['email' => 'no@rt.co']);
        $this->assertNotContains('ROLE_ROOT', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    // ==================== PUT Tests ====================

    public function testUpdateUserAsRootSuccess(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId,
            'email' => 'up@dt.co',
            'password' => 'newpass1',
            'phone' => '+8888888'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User updated', $response['message']);
    }

    public function testUpdateOwnUserAsRegularUserSuccess(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId,
            'email' => 'my@up.co',
            'password' => 'mypass12',
            'phone' => '+9999999'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User updated', $response['message']);
    }

    public function testUpdateOtherUserAsRegularUserReturns403(): void
    {
        $otherUserId = $this->regularUser2->getId();

        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $otherUserId,
            'email' => 'ha@ck.co',
            'password' => 'hacked12',
            'phone' => '+0000001'
        ]));

        $this->assertResponseStatusCodeSame(403);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Access denied', $response['error']);
    }

    public function testUpdateNonExistentUserReturns404(): void
    {
        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => 99999,
            'email' => 'no@ne.co',
            'password' => '12345678',
            'phone' => '+0000002'
        ]));

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found', $response['error']);
    }

    public function testUpdateUserWithMissingFieldsReturns400(): void
    {
        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => 1,
            'email' => 'te@st.co'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required attributes', $response['error']);
    }

    public function testUpdateUserWithPasswordTooLongReturns400(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId,
            'email' => 'te@st.co',
            'password' => '123456789', // 9 characters - too long
            'phone' => '+0000003'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Validation failed', $response['error']);
    }

    public function testUpdateUserRolesAsRootSuccess(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('PUT', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId,
            'email' => 'pr@mo.co',
            'password' => 'promote1',
            'phone' => '+0000004',
            'roles' => ['ROLE_ROOT']
        ]));

        $this->assertResponseIsSuccessful();

        // Refresh entity
        $this->entityManager->clear();
        $user = $this->userRepository->find($userId);
        $this->assertContains('ROLE_ROOT', $user->getRoles());
    }

    // ==================== DELETE Tests ====================

    public function testDeleteUserAsRootSuccess(): void
    {
        $userId = $this->regularUser2->getId();

        $this->client->request('DELETE', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User deleted', $response['message']);

        // Verify user is deleted
        $this->entityManager->clear();
        $deletedUser = $this->userRepository->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testDeleteUserAsRegularUserReturns403(): void
    {
        $userId = $this->regularUser2->getId();

        $this->client->request('DELETE', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId
        ]));

        $this->assertResponseStatusCodeSame(403);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Access denied', $response['error']);
        $this->assertStringContainsString('Only root users can delete', $response['message']);
    }

    public function testDeleteOwnUserAsRegularUserReturns403(): void
    {
        $userId = $this->regularUser->getId();

        $this->client->request('DELETE', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => $userId
        ]));

        $this->assertResponseStatusCodeSame(403);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Access denied', $response['error']);
    }

    public function testDeleteNonExistentUserReturns404(): void
    {
        $this->client->request('DELETE', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'id' => 99999
        ]));

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found', $response['error']);
    }

    public function testDeleteUserWithMissingIdReturns400(): void
    {
        $this->client->request('DELETE', '/v1/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->rootToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required attribute', $response['error']);
        $this->assertContains('id', $response['missing']);
    }
}

