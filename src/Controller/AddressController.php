<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Entity\LookupBarangay;

#[Route('/api/address')]
class AddressController extends AbstractController
{
    #[Route('/regions', name: 'api_address_regions')]
    public function getRegions(EntityManagerInterface $em): JsonResponse
    {
        $regions = $em->getRepository(LookupRegion::class)->findAll();
        $data = [];
        foreach ($regions as $r) {
            $data[] = ['code' => $r->getRegionCode(), 'name' => $r->getRegionDesc()];
        }
        return $this->json($data);
    }

    #[Route('/provinces/{regionCode}', name: 'api_address_provinces')]
    public function getProvinces(string $regionCode, EntityManagerInterface $em): JsonResponse
    {
        $provinces = $em->getRepository(LookupProvince::class)->findBy(['regionCode' => $regionCode]);
        $data = [];
        foreach ($provinces as $p) {
            $data[] = ['code' => $p->getProvinceCode(), 'name' => $p->getProvinceDesc()];
        }
        return $this->json($data);
    }

    #[Route('/cities/{provinceCode}', name: 'api_address_cities')]
    public function getCities(string $provinceCode, EntityManagerInterface $em): JsonResponse
    {
        $cities = $em->getRepository(LookupCity::class)->findBy(['provinceCode' => $provinceCode]);
        $data = [];
        foreach ($cities as $c) {
            $data[] = ['code' => $c->getCityCode(), 'name' => $c->getCityDesc()];
        }
        return $this->json($data);
    }

    #[Route('/barangays/{cityCode}', name: 'api_address_barangays')]
    public function getBarangays(string $cityCode, EntityManagerInterface $em): JsonResponse
    {
        $barangays = $em->getRepository(LookupBarangay::class)->findBy(['cityCode' => $cityCode]);
        $data = [];
        foreach ($barangays as $b) {
            $data[] = ['code' => $b->getBarangayCode(), 'name' => $b->getBarangayDesc(), 'zip' => $b->getZipcode()];
        }
        return $this->json($data);
    }

    #[Route('/provinces-all', name: 'api_address_provinces_all')]
    public function getAllProvinces(EntityManagerInterface $em): JsonResponse
    {
        $provinces = $em->createQuery(
            'SELECT p.provinceCode, p.provinceDesc, r.regionCode, r.regionDesc 
             FROM App\Entity\LookupProvince p 
             LEFT JOIN App\Entity\LookupRegion r WITH p.regionCode = r.regionCode 
             ORDER BY p.provinceDesc ASC'
        )->getResult();

        $data = [];
        foreach ($provinces as $p) {
            $data[] = [
                'provinceCode' => $p['provinceCode'],
                'provinceName' => $p['provinceDesc'],
                'regionCode' => $p['regionCode'],
                'regionName' => $p['regionDesc']
            ];
        }

        return $this->json($data);
    }
}