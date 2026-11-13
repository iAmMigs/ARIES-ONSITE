<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\ApplicantBed;
use App\Entity\CampusSetting;
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

        // Alphabetically sorted by label (A-Z)
        $surveyOptions = $em->getRepository(SurveyOption::class)->findBy(
            ['campus' => $campusCode],
            ['label' => 'ASC']
        );

        $optionUsageCounts = $this->getOptionUsageCounts($em, $surveyOptions);

        $maxChoicesSetting = $em->getRepository(CampusSetting::class)->findOneBy([
            'campus' => $campusCode,
            'settingKey' => CampusSetting::SETTING_MAX_SURVEY_CHOICES,
        ]);
        $maxChoices = $maxChoicesSetting ? (int) $maxChoicesSetting->getSettingValue() : 3;

        return $this->render('admin-onsite/survey/index.html.twig', [
            'campus'            => $campus,
            'campusCode'        => $campusCode,
            'campusName'        => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'surveyOptions'     => $surveyOptions,
            'optionUsageCounts' => $optionUsageCounts,
            'maxChoices'        => $maxChoices,
            'active_menu'       => 'survey',
        ]);
    }

    #[Route('/{campus}/settings', name: 'app_admin_survey_settings', methods: ['POST'])]
    public function updateSettings(string $campus, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_survey_settings', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $maxChoices = (int) $request->request->get('max_choices', 3);
        if ($maxChoices < 1) {
            $maxChoices = 1;
        }

        $setting = $em->getRepository(CampusSetting::class)->findOneBy([
            'campus' => $campusCode,
            'settingKey' => CampusSetting::SETTING_MAX_SURVEY_CHOICES,
        ]);

        if (!$setting) {
            $setting = new CampusSetting();
            $setting->setCampus($campusCode);
            $setting->setSettingKey(CampusSetting::SETTING_MAX_SURVEY_CHOICES);
            $em->persist($setting);
        }

        $setting->setSettingValue((string) $maxChoices);
        $em->flush();

        $this->addFlash('success', sprintf('Maximum applicant choices updated to %d.', $maxChoices));
        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
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

        if ($label === '') {
            $this->addFlash('error', 'Option label cannot be empty.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        // Duplicate validation (case-insensitive & trimmed per campus)
        $existing = $em->createQueryBuilder()
            ->select('s')
            ->from(SurveyOption::class, 's')
            ->where('s.campus = :campus')
            ->andWhere('LOWER(TRIM(s.label)) = :label')
            ->setParameter('campus', $campusCode)
            ->setParameter('label', strtolower($label))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing) {
            $this->addFlash('error', sprintf('A survey option with the label "%s" already exists.', $label));
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $option = new SurveyOption();
        $option->setCampus($campusCode);
        $option->setLabel($label);
        $option->setIcon($icon ?: 'ki-verify');
        $option->setIsActive(true);
        $option->setDisplayOrder(0);

        $em->persist($option);
        $em->flush();
        $this->addFlash('success', sprintf('Survey option "%s" added successfully.', $label));

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

            if ($label === '') {
                $this->addFlash('error', 'Option label cannot be empty.');
                return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
            }

            // Duplicate validation excluding current ID
            $existing = $em->createQueryBuilder()
                ->select('s')
                ->from(SurveyOption::class, 's')
                ->where('s.campus = :campus')
                ->andWhere('s.id != :id')
                ->andWhere('LOWER(TRIM(s.label)) = :label')
                ->setParameter('campus', $campusCode)
                ->setParameter('id', $id)
                ->setParameter('label', strtolower($label))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing) {
                $this->addFlash('error', sprintf('Another survey option with the label "%s" already exists.', $label));
                return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
            }

            $option->setLabel($label);
            $option->setIcon($icon ?: 'ki-verify');
            $em->flush();
            $this->addFlash('success', sprintf('Survey option "%s" updated successfully.', $label));
        }

        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
    }

    #[Route('/{campus}/delete/{id}', name: 'app_admin_survey_delete', methods: ['POST'])]
    public function delete(string $campus, int $id, Request $request, EntityManagerInterface $em, CampusDatabaseSwitcher $databaseSwitcher): Response
    {
        $this->authorizeAdmin($campus);
        $campusCode = strtolower(trim($campus)) === 'alabang' ? 'feu_alabang' : 'feu_diliman';
        $databaseSwitcher->switchToCampus($campusCode);

        if (!$this->isCsrfTokenValid('admin_survey_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $option = $em->getRepository(SurveyOption::class)->find($id);
        if (!$option || $option->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Survey option not found.');
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $usageCounts = $this->getOptionUsageCounts($em, [$option]);
        $usageCount = $usageCounts[$option->getId()] ?? 0;
        if ($usageCount > 0) {
            $this->addFlash('error', sprintf('Cannot delete survey option "%s" because it has already been selected by %d applicant(s). You can only edit or disable it.', $option->getLabel(), $usageCount));
            return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
        }

        $optionLabel = $option->getLabel();
        $em->remove($option);
        $em->flush();

        $this->addFlash('success', sprintf('Unused survey option "%s" deleted successfully.', $optionLabel));
        return $this->redirectToRoute('app_admin_survey_index', ['campus' => $campus]);
    }

    /**
     * @param SurveyOption[] $options
     * @return array<int, int>
     */
    private function getOptionUsageCounts(EntityManagerInterface $em, array $options): array
    {
        $counts = [];
        $labelToIds = [];
        foreach ($options as $opt) {
            $counts[$opt->getId()] = 0;
            $key = strtolower(trim((string) $opt->getLabel()));
            if ($key !== '') {
                $labelToIds[$key][] = $opt->getId();
            }
        }

        if ($labelToIds === []) {
            return $counts;
        }

        $rows = $em->getRepository(ApplicantBed::class)->createQueryBuilder('a')
            ->select('a.marketingSource')
            ->where('a.marketingSource IS NOT NULL')
            ->andWhere("a.marketingSource != ''")
            ->getQuery()
            ->getScalarResult();

        foreach ($rows as $row) {
            $raw = (string) ($row['marketingSource'] ?? '');
            if ($raw === '') {
                continue;
            }
            $tokens = array_unique(array_filter(array_map(
                static fn(string $part): string => strtolower(trim($part)),
                explode(',', $raw)
            )));
            foreach ($tokens as $token) {
                if (isset($labelToIds[$token])) {
                    foreach ($labelToIds[$token] as $optId) {
                        $counts[$optId]++;
                    }
                }
            }
        }

        return $counts;
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

