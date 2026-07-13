<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApplicantBed;
use App\Entity\SchoolYear;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AdmissionsEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $studentPortalUrl,
        private readonly string $activationGuideUrl,
        private readonly string $emailSender
    ) {}

    public function sendSuccessfulRegistrationEmail(ApplicantBed $applicant, SchoolYear $schoolYear): void
    {
        $fullName = trim(sprintf('%s %s %s', $applicant->getFirstName(), $applicant->getMiddleName() ?? '', $applicant->getLastName()));
        $fullName = preg_replace('/\s+/', ' ', $fullName);

        $htmlContent = $this->twig->render('emails/registration_success.html.twig', [
            'fullName' => $fullName,
            'studentNumber' => $applicant->getStudentNumber(),
            'gradeLevel' => $applicant->getGradeLevel(),
            'schoolYear' => 'SY ' . $schoolYear->getLabel(),
            'studentPortalUrl' => $this->studentPortalUrl,
            'activationGuideUrl' => $this->activationGuideUrl,
        ]);

        $email = (new Email())
            ->from($this->emailSender)
            ->to($applicant->getPersonalEmail())
            ->subject('Successful Admissions Registration - FEU Diliman')
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
