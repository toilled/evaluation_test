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
    #[Route('/user', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $usersArray = [];
        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $usersArray[] = ['username' => $user->getUsername(), 'displayName' => $user->getDisplayName()];
        }
        return $this->json([
            'users' => $usersArray,
        ]);
    }

    #[Route('/user', methods: ['PUT'])]
    public function create(Request $request, UserRepository $userRepository): JsonResponse
    {
        $user = new User();
        $user->setDisplayName($request->get('displayName'));
        $user->setUsername($request->get('username'));
        $user->setPassword($request->get('password'));
        
        $userRepository->save($user, true);

        return $this->json(['success' => true]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/user/{username}', methods: ['GET', 'HEAD'])]
    public function singleUser(string $username, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneByUsername($username);
        return $this->json([
            'username' => $user->getUsername(),
            'displayName' => $user->getDisplayName(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/user/{username}', methods: ['POST'])]
    public function loginUser(string $username, Request $request, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneByUsername($username);
        return $this->json([
            'success' => $user->checkPassword($request->get('password')),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/user/{username}', methods: ['PUT'])]
    public function updateUser(string $username, Request $request, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneByUsername($username);

        if ($request->get('oldPassword') == null || !$user->checkPassword($request->get('oldPassword'))) {
            return $this->json([
                'success' => false,
                'reason' => 'Incorrect password'
            ]);
        }

        $user->setDisplayName($request->get('displayName'));
        $user->setUsername($request->get('username'));
        $user->setPassword($request->get('password'));

        $userRepository->save($user, true);

        return $this->json([
            'success' => true,
        ]);
    }
}
