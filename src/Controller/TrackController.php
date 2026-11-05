<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\TrackSetup;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tracks')]
#[IsGranted('ROLE_ADMIN')]
class TrackController extends AbstractController
{
    #[Route('/{campus}', name: 'app_admin_tracks_index', methods: ['GET'])]
    public function index(string $campus, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        $tracks = $em->getRepository(TrackSetup::class)->findBy(
            ['campus' => $campusCode],
            ['displayOrder' => 'ASC', 'id' => 'ASC']
        );

        return $this->render('admin-onsite/tracks/index.html.twig', [
            'campus'      => $campus,
            'campusCode'  => $campusCode,
            'campusName'  => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'tracks'      => $tracks,
            'active_menu' => 'tracks',
        ]);
    }

    #[Route('/{campus}/toggle/{id}', name: 'app_admin_tracks_toggle', methods: ['POST'])]
    public function toggle(string $campus, int $id, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        $track = $em->getRepository(TrackSetup::class)->find($id);
        if ($track && $track->getCampus() === $campusCode) {
            $track->setIsActive(!$track->isActive());
            $em->flush();
            $this->addFlash('success', sprintf('Track/Strand "%s" status updated.', $track->getName()));
        }

        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/add', name: 'app_admin_tracks_add', methods: ['POST'])]
    public function add(string $campus, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        $code = trim((string) $request->request->get('code', ''));
        $name = trim((string) $request->request->get('name', ''));

        if ($code !== '' && $name !== '') {
            $track = new TrackSetup();
            $track->setCampus($campusCode);
            $track->setCode($code);
            $track->setName($name);
            $track->setIsActive(true);
            $track->setDisplayOrder((int) $request->request->get('display_order', 0));

            $em->persist($track);
            $em->flush();
            $this->addFlash('success', sprintf('Track "%s (%s)" added successfully.', $name, $code));
        }

        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    private function authorizeAdmin(string $campus): void
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof AdminUser) {
            throw new AccessDeniedException('User is not an AdminUser instance.');
        }

        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        if ($currentUser->getCampus() !== null && $currentUser->getCampus() !== $campusCode) {
            throw new AccessDeniedException('You do not have access to this campus.');
        }

        if (!$currentUser->hasModule(AdminUser::MODULE_TRACKS)) {
            throw new AccessDeniedException('Access denied: You do not have permission to access the Tracks & Strands module.');
        }
    }
}

