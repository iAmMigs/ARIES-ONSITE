<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Entity\LookupBarangay;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LocationExtension extends AbstractExtension
{
    private EntityManagerInterface $em;
    private array $cache = [];

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('location_region', [$this, 'getRegionName']),
            new TwigFilter('location_province', [$this, 'getProvinceName']),
            new TwigFilter('location_city', [$this, 'getCityName']),
            new TwigFilter('location_barangay', [$this, 'getBarangayName']),
        ];
    }

    public function getRegionName(?string $code): ?string
    {
        if (empty($code) || !is_numeric($code)) {
            return $code;
        }

        $codeInt = (int)$code;
        $cacheKey = 'region_' . $codeInt;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $region = $this->em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => $codeInt]);
        $name = $region ? $region->getRegionDesc() : $code;
        $this->cache[$cacheKey] = $name;

        return $name;
    }

    public function getProvinceName(?string $code): ?string
    {
        if (empty($code) || !is_numeric($code)) {
            return $code;
        }

        $codeInt = (int)$code;
        $cacheKey = 'province_' . $codeInt;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $province = $this->em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => $codeInt]);
        $name = $province ? $province->getProvinceDesc() : $code;
        $this->cache[$cacheKey] = $name;

        return $name;
    }

    public function getCityName(?string $code): ?string
    {
        if (empty($code) || !is_numeric($code)) {
            return $code;
        }

        $codeInt = (int)$code;
        $cacheKey = 'city_' . $codeInt;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $city = $this->em->getRepository(LookupCity::class)->findOneBy(['cityCode' => $codeInt]);
        $name = $city ? $city->getCityDesc() : $code;
        $this->cache[$cacheKey] = $name;

        return $name;
    }

    public function getBarangayName(?string $code): ?string
    {
        if (empty($code) || !is_numeric($code)) {
            return $code;
        }

        $codeInt = (int)$code;
        $cacheKey = 'barangay_' . $codeInt;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $barangay = $this->em->getRepository(LookupBarangay::class)->findOneBy(['barangayCode' => $codeInt]);
        $name = $barangay ? $barangay->getBarangayDesc() : $code;
        $this->cache[$cacheKey] = $name;

        return $name;
    }
}
