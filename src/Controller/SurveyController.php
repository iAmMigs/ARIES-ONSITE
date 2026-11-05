<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\SurveyOption;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/survey')]
#[IsGranted('ROLE_ADMIN')]
class SurveyController extends AbstractController
{
    #[Route('/{campus}', name: 'app_admin_survey_index', methods: ['GET'])]
    public function index(string $campus, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        $surveyOptions = $em->getRepository(SurveyOption::class)->findBy(
            ['campus' => $campusCode],
            ['displayOrder' => 'ASC', 'id' => 'ASC']
        );

        return $this->render('admin-onsite/survey/index.html.twig', [
            'campus'        => $campus,
            'campusCode'    => $campusCode,
            'campusName'    => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'surveyOptions' => $surveyOptions,
            'active_menu'   => 'survey',
        ]);
    }

    #[Route('/{campus}/toggle/{id}', name: 'app_admin_survey_toggle', methods: ['POST'])]
    public function toggle(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_survey_toggle_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $option = $em->getRepository(SurveyOption::class)->find($id);
        if ($option && $option->getCampus() === $campusCode) {
            $option->setIsActive(!$option->isActive());
            $em->flush();
            $this->addFlash('success', sprintf('Survey option "%s" status updated.', $option->getLabel()));
        }

        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/add', name: 'app_admin_survey_add', methods: ['POST'])]
    public function add(string $campus, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_survey_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $label = trim((string) $request->request->get('label', ''));
        $icon = trim((string) $request->request->get('icon', 'ki-verify'));

        if ($label !== '') {
            $option = new SurveyOption();
            $option->setCampus($campusCode);
            $option->setLabel($label);
            $option->setIcon($icon ?: 'ki-verify');
            $option->setIsActive(true);
            $option->setDisplayOrder((int) $request->request->get('display_order', 0));

            $em->persist($option);
            $em->flush();
            $this->addFlash('success', sprintf('Survey option "%s" added successfully.', $label));
        }

        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/edit/{id}', name: 'app_admin_survey_edit', methods: ['POST'])]
    public function edit(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_survey_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $option = $em->getRepository(SurveyOption::class)->find($id);
        if ($option && $option->getCampus() === $campusCode) {
            $label = trim((string) $request->request->get('label', ''));
            $icon = trim((string) $request->request->get('icon', 'ki-verify'));
            $order = (int) $request->request->get('display_order', 0);

            if ($label !== '') {
                $option->setLabel($label);
                $option->setIcon($icon ?: 'ki-verify');
                $option->setDisplayOrder($order);
                $em->flush();
                $this->addFlash('success', sprintf('Survey option "%s" updated successfully.', $label));
            }
        }

        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
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

        if (!$currentUser->hasModule(AdminUser::MODULE_SURVEY)) {
            throw new AccessDeniedException('Access denied: You do not have permission to access the Survey module.');
        }
    }
}

