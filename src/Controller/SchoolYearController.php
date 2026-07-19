<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manages school year configuration for both the Alabang and Diliman campuses.
 *
 * Each campus maintains its own independent set of school years. Routes are
 * campus-scoped so that the Alabang admin can only manage Alabang school years
 * and vice versa.
 *
 * Route prefix: /admin/school-year/{campus}
 * Access is expected to be restricted by the firewall / security config per campus.
 */
#[Route('/admin/school-year')]
class SchoolYearController extends AbstractController
{
    /**
     * Lists all school years for the given campus and renders the management UI.
     * Handles creation of new school years via POST.
     */
    #[Route('/{campus}', name: 'app_admin_school_year_index', methods: ['GET', 'POST'])]
    public function index(
        string $campus,
        Request $request,
        SchoolYearRepository $syRepo,
        EntityManagerInterface $em
    ): Response {
        // Resolve the campus form slug to the entity campus code
        $campusCode = $this->resolveCampusCode($campus);
        if (!$campusCode) {
            throw $this->createNotFoundException('Invalid campus: ' . $campus);
        }

        if ($request->isMethod('POST')) {
            $yearStart = (int) $request->request->get('year_start');
            $yearEnd   = $yearStart + 1;
            $promissoryDeadlineStr = $request->request->get('promissory_deadline');

            // Validate: year must be a plausible value
            if ($yearStart < 2020 || $yearStart > 2100) {
                $this->addFlash('error', 'Please enter a valid starting year (e.g. 2025).');
                return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
            }

            if (!$promissoryDeadlineStr) {
                $this->addFlash('error', 'Promissory deadline is mandatory.');
                return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
            }

            // Prevent duplicate school years for the same campus
            $existing = $syRepo->findOneBy(['campus' => $campusCode, 'yearStart' => $yearStart]);
            if ($existing) {
                $this->addFlash('error', 'A school year starting in ' . $yearStart . ' already exists for this campus.');
                return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
            }

            // Build the short label: "SY" + last 2 digits of each year, e.g. "SY2526"
            $label = 'SY' . substr((string) $yearStart, -2) . substr((string) $yearEnd, -2);

            $sy = new SchoolYear();
            $sy->setLabel($label);
            $sy->setYearStart($yearStart);
            $sy->setYearEnd($yearEnd);
            $sy->setCampus($campusCode);
            $sy->setPromissoryDeadline(new \DateTime($promissoryDeadlineStr));
            $sy->setIsActive(false);
            $sy->setEnrollmentOpen(false);

            $em->persist($sy);
            $em->flush();

            $this->addFlash('success', 'School Year ' . $label . ' created successfully.');
            return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
        }

        $schoolYears  = $syRepo->findByCampusOrdered($campusCode);
        $activeSY     = $syRepo->findActiveByCampus($campusCode);
        $campusLabel  = ($campusCode === SchoolYear::CAMPUS_ALABANG) ? 'FEU Alabang' : 'FEU Diliman';

        return $this->render('admin-onsite/school_years/index.html.twig', [
            'schoolYears'  => $schoolYears,
            'activeSY'     => $activeSY,
            'campus'       => $campus,
            'campusCode'   => $campusCode,
            'campusLabel'  => $campusLabel,
            'active_menu'  => 'school_year',
        ]);
    }

    /**
     * Activates the specified school year for its campus.
     * All other school years for the same campus are automatically deactivated.
     */
    #[Route('/{campus}/{id}/activate', name: 'app_admin_school_year_activate', methods: ['POST'])]
    public function activate(
        string $campus,
        int $id,
        SchoolYearRepository $syRepo,
        EntityManagerInterface $em
    ): Response {
        $campusCode = $this->resolveCampusCode($campus);
        $sy = $syRepo->find($id);

        if (!$sy || $sy->getCampus() !== $campusCode) {
            throw $this->createNotFoundException();
        }

        // Deactivate all school years for this campus
        $syRepo->deactivateAllForCampus($campusCode);
        $em->flush();

        // Re-fetch and activate the target school year
        $em->refresh($sy);
        $sy->setIsActive(true);
        $em->flush();

        $this->addFlash('success', $sy->getLabel() . ' is now the active school year.');
        return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
    }

    /**
     * Toggles the enrollment window open or closed.
     * A maximum of 2 school years can have enrollment open concurrently.
     */
    #[Route('/{campus}/{id}/toggle-enrollment', name: 'app_admin_school_year_toggle', methods: ['POST'])]
    public function toggleEnrollment(
        string $campus,
        int $id,
        SchoolYearRepository $syRepo,
        EntityManagerInterface $em
    ): Response {
        $campusCode = $this->resolveCampusCode($campus);
        $sy = $syRepo->find($id);

        if (!$sy || $sy->getCampus() !== $campusCode) {
            throw $this->createNotFoundException();
        }

        if (!$sy->isEnrollmentOpen()) {
            $currentlyOpen = $syRepo->findOpenEnrollmentsByCampus($campusCode);
            if (count($currentlyOpen) >= 2) {
                $this->addFlash('error', 'A maximum of 2 school years can have enrollment open simultaneously for this campus.');
                return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
            }
        }

        $sy->setEnrollmentOpen(!$sy->isEnrollmentOpen());
        $em->flush();

        $state = $sy->isEnrollmentOpen() ? 'opened' : 'closed';
        $this->addFlash('success', 'Enrollment has been ' . $state . ' for ' . $sy->getLabel() . '.');

        return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
    }


    /**
     * Updates the promissory deadline for a school year.
     */
    #[Route('/{campus}/{id}/update-promissory-deadline', name: 'app_admin_school_year_deadline', methods: ['POST'])]
    public function updatePromissoryDeadline(
        string $campus,
        int $id,
        Request $request,
        SchoolYearRepository $syRepo,
        EntityManagerInterface $em
    ): Response {
        $campusCode = $this->resolveCampusCode($campus);
        $sy = $syRepo->find($id);

        if (!$sy || $sy->getCampus() !== $campusCode) {
            throw $this->createNotFoundException();
        }

        $deadlineStr = $request->request->get('promissory_deadline');
        if ($deadlineStr) {
            try {
                $deadline = new \DateTime($deadlineStr);
                $sy->setPromissoryDeadline($deadline);
                
                $altLabel = $sy->getYearStart() . '-' . $sy->getYearEnd();

                $em->createQuery(
                    'UPDATE App\Entity\ApplicantBed a 
                     SET a.documentsAgreedDate = :deadline 
                     WHERE a.campus = :campus 
                     AND (a.schoolYearOfEntry = :label OR a.schoolYearOfEntry = :altLabel) 
                     AND a.documentsAgreedDate IS NOT NULL'
                )
                ->setParameter('deadline', $deadline)
                ->setParameter('campus', $campusCode)
                ->setParameter('label', $sy->getLabel())
                ->setParameter('altLabel', $altLabel)
                ->execute();

                $em->flush();
                $this->addFlash('success', 'Promissory deadline for ' . $sy->getLabel() . ' has been updated.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date format for deadline.');
            }
        } else {
            $sy->setPromissoryDeadline(null);
            
            $altLabel = $sy->getYearStart() . '-' . $sy->getYearEnd();

            $em->createQuery(
                'UPDATE App\Entity\ApplicantBed a 
                 SET a.documentsAgreedDate = NULL 
                 WHERE a.campus = :campus 
                 AND (a.schoolYearOfEntry = :label OR a.schoolYearOfEntry = :altLabel) 
                 AND a.documentsAgreedDate IS NOT NULL'
            )
            ->setParameter('campus', $campusCode)
            ->setParameter('label', $sy->getLabel())
            ->setParameter('altLabel', $altLabel)
            ->execute();

            $em->flush();
            $this->addFlash('success', 'Promissory deadline has been removed.');
        }

        return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
    }

    /**
     * Permanently deletes a school year record.
     * Deletion is blocked if the school year has applicants assigned to it
     * to prevent orphaned student records.
     */
    #[Route('/{campus}/{id}/delete', name: 'app_admin_school_year_delete', methods: ['POST'])]
    public function delete(
        string $campus,
        int $id,
        SchoolYearRepository $syRepo,
        EntityManagerInterface $em
    ): Response {
        $campusCode = $this->resolveCampusCode($campus);
        $sy = $syRepo->find($id);

        if (!$sy || $sy->getCampus() !== $campusCode) {
            throw $this->createNotFoundException();
        }

        // Block deletion of the active school year
        if ($sy->isActive()) {
            $this->addFlash('error', 'Cannot delete the active school year. Activate another one first.');
            return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
        }

        // Block deletion if applicants are tied to this school year's label
        $applicantCount = $em->getRepository(ApplicantBed::class)->count([
            'campus'            => $campusCode,
            'schoolYearOfEntry' => $sy->getLabel(),
        ]);

        if ($applicantCount > 0) {
            $this->addFlash('error', 'Cannot delete ' . $sy->getLabel() . ' — it has ' . $applicantCount . ' registered applicant(s).');
            return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
        }

        $em->remove($sy);
        $em->flush();

        $this->addFlash('success', $sy->getLabel() . ' has been deleted.');
        return $this->redirectToRoute('app_admin_school_year_index', ['campus' => $campus]);
    }

    /**
     * Converts a URL-friendly campus slug to the internal campus entity code.
     *
     * @param string $campus 'alabang' or 'diliman'
     * @return string|null The campus code, or null if the slug is unrecognized
     */
    private function resolveCampusCode(string $campus): ?string
    {
        return match ($campus) {
            'alabang' => SchoolYear::CAMPUS_ALABANG,
            'diliman' => SchoolYear::CAMPUS_DILIMAN,
            default   => null,
        };
    }
}
