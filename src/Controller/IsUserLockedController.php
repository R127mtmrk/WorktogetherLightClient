<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/islocked', name: 'app_is_user_locked')]
final class IsUserLockedController extends AbstractController
{
    #[Route('/', name: 'app_is_user_locked_index')]
    public function index(): Response
    {
        //get user form URL
        $user = $this->getParameter('user');
        $token = $this->getParameter('token');

        //get pwderr from user by id in URL
        $status = $this->getUser()->getPwdErr();
        if ($status>=3) {
            $statusMsg = 'Compte bloqué';
        } else {
            $statusMsg = 'Compte actif';
        }

        //verify if token is valid
        $tokenStatus = 'none';

        if ($token == $this->getUser()->getToken()) {
            $tokenStatus = 'ok';
        }

        return $this->render('is_user_locked/index.html.twig', [
            'controller_name' => 'IsUserLockedController',
            'user' => $user,
            'token' => $token,
            'status' => $statusMsg,
            'tokenStatus' => $tokenStatus,
        ]);
    }

    #[Route('/reset', name: 'app_is_user_locked_reset')]
    public function reset(): Response
    {
        $token = $this->getParameter('token');
        $user = $this->getParameter('user');



        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_dashboard');
        }

    }
}
