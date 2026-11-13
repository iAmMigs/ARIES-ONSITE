<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\ApplicantBed;
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

        // Alphabetically sorted by name (A-Z)
        $tracks = $em->getRepository(TrackSetup::class)->findBy(
            ['campus' => $campusCode],
            ['name' => 'ASC']
        );

        $trackUsageCounts = [];
        foreach ($tracks as $track) {
            $trackUsageCounts[$track->getId()] = $this->getTrackUsageCount($em, $track);
        }

        return $this->render('admin-onsite/tracks/index.html.twig', [
            'campus'           => $campus,
            'campusCode'       => $campusCode,
            'campusName'       => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'tracks'           => $tracks,
            'trackUsageCounts' => $trackUsageCounts,
            'active_menu'      => 'tracks',
        ]);
    }

    #[Route('/{campus}/toggle/{id}', name: 'app_admin_tracks_toggle', methods: ['POST'])]
    public function toggle(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_tracks_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $track = $em->getRepository(TrackSetup::class)->find($id);
        if ($track && $track->getCampus() === $campusCode) {
            $track->setIsActive(!$track->isActive());
            $em->flush();
            $this->addFlash('success', sprintf('Track "%s" status updated.', $track->getName()));
        }

        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/add', name: 'app_admin_tracks_add', methods: ['POST'])]
    public function add(string $campus, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_tracks_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $code = strtoupper(trim((string) $request->request->get('code', '')));
        $name = trim((string) $request->request->get('name', ''));

        if ($code === '' || $name === '') {
            $this->addFlash('error', 'Both Track Code and Track Name are required.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        // Duplicate prevention per campus (case-insensitive for both code and name)
        $existing = $em->getRepository(TrackSetup::class)->createQueryBuilder('t')
            ->where('t.campus = :campus')
            ->andWhere('(LOWER(TRIM(t.code)) = :code OR LOWER(TRIM(t.name)) = :name)')
            ->setParameter('campus', $campusCode)
            ->setParameter('code', strtolower($code))
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            $this->addFlash('error', sprintf('A track with code "%s" or name "%s" already exists.', $code, $name));
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $track = new TrackSetup();
        $track->setCampus($campusCode);
        $track->setCode($code);
        $track->setName($name);
        $track->setIsActive(true);
        $track->setDisplayOrder(0);

        $em->persist($track);
        $em->flush();

        $this->addFlash('success', sprintf('Track "%s (%s)" added successfully.', $name, $code));
        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/edit/{id}', name: 'app_admin_tracks_edit', methods: ['POST'])]
    public function edit(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_tracks_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $track = $em->getRepository(TrackSetup::class)->find($id);
        if (!$track || $track->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Track not found.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $code = strtoupper(trim((string) $request->request->get('code', '')));
        $name = trim((string) $request->request->get('name', ''));

        if ($code === '' || $name === '') {
            $this->addFlash('error', 'Both Track Code and Track Name are required.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        // Duplicate check excluding current track
        $existing = $em->getRepository(TrackSetup::class)->createQueryBuilder('t')
            ->where('t.campus = :campus')
            ->andWhere('t.id != :id')
            ->andWhere('(LOWER(TRIM(t.code)) = :code OR LOWER(TRIM(t.name)) = :name)')
            ->setParameter('campus', $campusCode)
            ->setParameter('id', $id)
            ->setParameter('code', strtolower($code))
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            $this->addFlash('error', sprintf('A track with code "%s" or name "%s" already exists.', $code, $name));
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $track->setCode($code);
        $track->setName($name);
        $em->flush();

        $this->addFlash('success', sprintf('Track "%s (%s)" updated successfully.', $name, $code));
        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/delete/{id}', name: 'app_admin_tracks_delete', methods: ['POST'])]
    public function delete(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_tracks_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $track = $em->getRepository(TrackSetup::class)->find($id);
        if (!$track || $track->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Track not found.');
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $usageCount = $this->getTrackUsageCount($em, $track);
        if ($usageCount > 0) {
            $this->addFlash('error', sprintf('Cannot delete track "%s" because it has already been used by %d applicant(s). You can only edit or disable it.', $track->getName(), $usageCount));
            return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
        }

        $trackName = $track->getName();
        $em->remove($track);
        $em->flush();

        $this->addFlash('success', sprintf('Unused track "%s" deleted successfully.', $trackName));
        return $this->redirectToRoute('app_admin_tracks_index', ['campus' => $campus]);
    }

    private function getTrackUsageCount(EntityManagerInterface $em, TrackSetup $track): int
    {
        return (int) $em->getRepository(ApplicantBed::class)->createQueryBuilder('a')
            ->select('COUNT(a.studentNumber)')
            ->where('LOWER(TRIM(a.trackStrand)) = :name OR LOWER(TRIM(a.trackStrand)) = :code')
            ->setParameter('name', strtolower(trim((string) $track->getName())))
            ->setParameter('code', strtolower(trim((string) $track->getCode())))
            ->getQuery()
            ->getSingleScalarResult();
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
            throw new AccessDeniedException('Access denied: You do not have permission to access the Tracks module.');
        }
    }
}
