<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(private readonly UserRepository $repository) {}

    /**
     * Get all users
     */
    #[Route('/users', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $usersArray = [];
        $users = $this->repository->findAll();
        foreach ($users as $user) {
            $usersArray[] = [
                'username' => $user->getUsername(),
                'displayName' => $user->getDisplayName()
            ];
        }
        return $this->json([
            'count' => count($usersArray),
            'users' => $usersArray,
        ]);
    }

    /**
     * Create a user
     *
     * @throws NonUniqueResultException
     */
    #[Route('/users', methods: ['PUT'])]
    public function create(Request $request): JsonResponse
    {
        $missingValues = [];
        $requiredValues = [
            'displayName',
            'username',
            'password'
        ];
        foreach ($requiredValues as $requiredValue) {
            if (!$request->get($requiredValue)) {
                $missingValues[] = $requiredValue;
            }
        }
        if (!empty($missingValues)) {
            return $this->json([
                'success' => false,
                'error' => 'Missing values',
                'missing' => $missingValues
            ]);
        }

        if ($this->repository->findUserByUsername($request->get('username'))) {
            return $this->json([
                'success' => false,
                'error' => 'Duplicate username'
            ]);
        }

        $user = new User();
        $user->setDisplayName($request->get('displayName'));
        $user->setUsername($request->get('username'));
        $user->setPassword($request->get('password'));
        
        $this->repository->save($user, true);

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * Retrieve a single user by username
     *
     * @throws NonUniqueResultException
     */
    #[Route('/users/{username}', methods: ['GET', 'HEAD'])]
    public function singleUser(string $username): JsonResponse
    {
        $user = $this->repository->findUserByUsername($username);
        return $this->json([
            'username' => $user->getUsername(),
            'displayName' => $user->getDisplayName(),
        ]);
    }

    /**
     * Validate user credentials
     *
     * @throws NonUniqueResultException
     */
    #[Route('/users/{username}', methods: ['POST'])]
    public function loginUser(string $username, Request $request): JsonResponse
    {
        $user = $this->repository->findUserByUsername($username);
        return $this->json([
            'success' => $user->checkPassword($request->get('password')),
        ]);
    }

    /**
     * Update user credentials
     *
     * @throws NonUniqueResultException
     */
    #[Route('/users/{username}', methods: ['PUT'])]
    public function updateUser(string $username, Request $request): JsonResponse
    {
        $user = $this->repository->findUserByUsername($username);

        if ($request->get('oldPassword') == null || !$user->checkPassword($request->get('oldPassword'))) {
            return $this->json([
                'success' => false,
                'error' => 'Incorrect password'
            ]);
        }

        if ($request->get('displayName') != null) $user->setDisplayName($request->get('displayName'));
        if ($request->get('username') != null) $user->setUsername($request->get('username'));
        if ($request->get('password') != null) $user->setPassword($request->get('password'));

        $this->repository->save($user, true);

        return $this->json([
            'success' => true,
        ]);
    }
}
