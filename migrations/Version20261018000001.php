<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync tblcitizenship with lookup_country (link 4 orphans, insert 42 sovereign countries) and fix phonecode/iso anomalies';
    }

    public function up(Schema $schema): void
    {
        // 1. Fix data anomalies in lookup_country & tblcitizenship
        $this->addSql("UPDATE lookup_country SET phonecode = 993 WHERE iso = 'TM' AND phonecode = 7370");
        $this->addSql("UPDATE lookup_country SET iso = 'RS', iso3 = 'SRB' WHERE country_name = 'Serbia' AND iso = 'CS'");
        $this->addSql("UPDATE tblcitizenship SET Country = 'MAURITIUS' WHERE CitizenshipCode = 'MAS'");

        // 2. Link 4 orphan countries by setting country_sf_id in lookup_country
        $this->addSql("UPDATE lookup_country SET country_sf_id = '44' WHERE iso = 'CK' AND (country_sf_id IS NULL OR country_sf_id = '')");
        $this->addSql("UPDATE lookup_country SET country_sf_id = '95' WHERE iso = 'HK' AND (country_sf_id IS NULL OR country_sf_id = '')");
        $this->addSql("UPDATE lookup_country SET country_sf_id = '182' WHERE iso = 'PR' AND (country_sf_id IS NULL OR country_sf_id = '')");
        $this->addSql("UPDATE lookup_country SET country_sf_id = '225' WHERE iso = 'TW' AND (country_sf_id IS NULL OR country_sf_id = '')");

        // 3. Insert 42 missing sovereign nationalities into tblcitizenship
        $nationalities = [
            ['ALB', 'ALBANIAN', 'ALBANIA', 6],
            ['AND', 'ANDORRAN', 'ANDORRA', 1],
            ['ATG', 'ANTIGUAN', 'ANTIGUA AND BARBUDA', 4],
            ['ARM', 'ARMENIAN', 'ARMENIA', 7],
            ['AZE', 'AZERBAIJANI', 'AZERBAIJAN', 16],
            ['BLR', 'BELARUSIAN', 'BELARUS', 35],
            ['BLZ', 'BELIZEAN', 'BELIZE', 36],
            ['BIH', 'BOSNIAN', 'BOSNIA AND HERZEGOVINA', 17],
            ['CAF', 'CENTRAL AFRICAN', 'CENTRAL AFRICAN REPUBLIC', 40],
            ['HRV', 'CROATIAN', 'CROATIA', 98],
            ['DMA', 'DOMINICAN (DOMINICA)', 'DOMINICA', 60],
            ['EST', 'ESTONIAN', 'ESTONIA', 64],
            ['GEO', 'GEORGIAN', 'GEORGIA', 79],
            ['VAT', 'VATICAN', 'HOLY SEE', 233],
            ['KAZ', 'KAZAKH', 'KAZAKHSTAN', 125],
            ['KGZ', 'KYRGYZ', 'KYRGYZSTAN', 116],
            ['LVA', 'LATVIAN', 'LATVIA', 135],
            ['LIE', 'LIECHTENSTEINER', 'LIECHTENSTEIN', 129],
            ['LTU', 'LITHUANIAN', 'LITHUANIA', 133],
            ['MDA', 'MOLDOVAN', 'MOLDOVA', 139],
            ['MCO', 'MONEGASQUE', 'MONACO', 138],
            ['MNE', 'MONTENEGRIN', 'MONTENEGRO', 140],
            ['NAM', 'NAMIBIAN', 'NAMIBIA', 160],
            ['NRU', 'NAURUAN', 'NAURU', 169],
            ['PRK', 'NORTH KOREAN', 'NORTH KOREA', 121],
            ['MKD', 'MACEDONIAN', 'NORTH MACEDONIA', 144],
            ['PLW', 'PALAUAN', 'PALAU', 185],
            ['PAN', 'PANAMANIAN', 'PANAMA', 173],
            ['KNA', 'KITTITIAN', 'SAINT KITTS AND NEVIS', 120],
            ['LCA', 'SAINT LUCIAN', 'SAINT LUCIA', 128],
            ['VCT', 'VINCENTIAN', 'SAINT VINCENT AND THE GRENADINES', 234],
            ['SMR', 'SAMMARINESE', 'SAN MARINO', 203],
            ['STP', 'SAO TOMEAN', 'SAO TOME AND PRINCIPE', 207],
            ['SRB', 'SERBIAN', 'SERBIA', 50],
            ['SVK', 'SLOVAK', 'SLOVAKIA', 201],
            ['SVN', 'SLOVENIAN', 'SLOVENIA', 199],
            ['SSD', 'SOUTH SUDANESE', 'SOUTH SUDAN', 253],
            ['TJK', 'TAJIK', 'TAJIKISTAN', 216],
            ['TKM', 'TURKMEN', 'TURKMENISTAN', 218],
            ['TUV', 'TUVALUAN', 'TUVALU', 224],
            ['UZB', 'UZBEK', 'UZBEKISTAN', 232],
            ['ZWE', 'ZIMBABWEAN', 'ZIMBABWE', 246],
        ];

        foreach ($nationalities as [$code, $natName, $country, $sfId]) {
            $escapedName = addslashes($natName);
            $escapedCountry = addslashes($country);
            $this->addSql("INSERT INTO tblcitizenship (CitizenshipCode, CitizenshipName, Country, country_sf_id)
                SELECT '{$code}', '{$escapedName}', '{$escapedCountry}', {$sfId}
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM tblcitizenship WHERE CitizenshipCode = '{$code}' OR CitizenshipName = '{$escapedName}'
                )");
        }
    }

    public function down(Schema $schema): void
    {
        $codes = [
            'ALB', 'AND', 'ATG', 'ARM', 'AZE', 'BLR', 'BLZ', 'BIH', 'CAF', 'HRV',
            'DMA', 'EST', 'GEO', 'VAT', 'KAZ', 'KGZ', 'LVA', 'LIE', 'LTU', 'MDA',
            'MCO', 'MNE', 'NAM', 'NRU', 'PRK', 'MKD', 'PLW', 'PAN', 'KNA', 'LCA',
            'VCT', 'SMR', 'STP', 'SRB', 'SVK', 'SVN', 'SSD', 'TJK', 'TKM', 'TUV',
            'UZB', 'ZWE'
        ];

        $codesStr = "'" . implode("','", $codes) . "'";
        $this->addSql("DELETE FROM tblcitizenship WHERE CitizenshipCode IN ({$codesStr})");

        $this->addSql("UPDATE lookup_country SET country_sf_id = NULL WHERE iso IN ('CK', 'HK', 'PR', 'TW')");
        $this->addSql("UPDATE lookup_country SET phonecode = 7370 WHERE iso = 'TM'");
        $this->addSql("UPDATE lookup_country SET iso = 'CS', iso3 = NULL WHERE country_name = 'Serbia'");
        $this->addSql("UPDATE tblcitizenship SET Country = 'MAURITUS' WHERE CitizenshipCode = 'MAS'");
    }
}
