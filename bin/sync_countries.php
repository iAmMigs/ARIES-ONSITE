<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');
if (file_exists(__DIR__ . '/../.env.local')) {
    (new Dotenv())->loadEnv(__DIR__ . '/../.env.local');
}

$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$conn = $em->getConnection();

echo "1. Checking and creating columns in lookup_country...\n";
$schemaManager = $conn->createSchemaManager();
$columns = $schemaManager->listTableColumns('lookup_country');

$columnsToAdd = [
    'iso' => "ALTER TABLE lookup_country ADD iso VARCHAR(2) DEFAULT NULL",
    'nicename' => "ALTER TABLE lookup_country ADD nicename VARCHAR(80) DEFAULT NULL",
    'iso3' => "ALTER TABLE lookup_country ADD iso3 VARCHAR(3) DEFAULT NULL",
    'numcode' => "ALTER TABLE lookup_country ADD numcode SMALLINT DEFAULT NULL",
    'phonecode' => "ALTER TABLE lookup_country ADD phonecode INT DEFAULT NULL"
];

foreach ($columnsToAdd as $colName => $alterSql) {
    if (!isset($columns[strtolower($colName)])) {
        echo "Adding column $colName...\n";
        $conn->executeStatement($alterSql);
    } else {
        echo "Column $colName already exists.\n";
    }
}

echo "\n2. Reading and parsing Temps/countries.sql...\n";
$sql = file_get_contents(__DIR__ . '/../Temps/countries.sql');
preg_match_all("/\(\s*(\d+)\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*(NULL|'[^']*')\s*,\s*(NULL|\d+)\s*,\s*(\d+)\s*\)/i", $sql, $matches, PREG_SET_ORDER);
echo "Parsed " . count($matches) . " countries from SQL dump.\n";

function normalizeName($name) {
    $n = strtolower(trim($name));
    $n = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $n) ?: $n;
    $n = preg_replace('/[^a-z0-9]/', '', $n);
    return $n;
}

$aliases = [
    'unitedstatesofamerica' => 'unitedstates',
    'usa' => 'unitedstates',
    'us' => 'unitedstates',
    'southkorea' => 'korearepublicof',
    'koreasouth' => 'korearepublicof',
    'northkorea' => 'koreademocraticpeoplesrepublicof',
    'koreanorth' => 'koreademocraticpeoplesrepublicof',
    'russia' => 'russianfederation',
    'vietnam' => 'vietnam',
    'taiwan' => 'taiwanprovinceofchina',
    'syria' => 'syrianarabrepublic',
    'iran' => 'iranislamicrepublicof',
    'vaticancity' => 'holyseevaticancitystate',
    'holysee' => 'holyseevaticancitystate',
    'vatican' => 'holyseevaticancitystate',
    'tanzania' => 'tanzaniaunitedrepublicof',
    'bolivia' => 'bolivia',
    'venezuela' => 'venezuela',
    'brunei' => 'bruneidarussalam',
    'caboverde' => 'capeverde',
    'capeverde' => 'capeverde',
    'congocongobrazzaville' => 'congo',
    'cotedivoire' => 'cotedivoire',
    'czechrepublic' => 'czechrepublic',
    'czechia' => 'czechrepublic',
    'czechiaczechrepublic' => 'czechrepublic',
    'democraticrepublicofthecongo' => 'congothedemocraticrepublicofthe',
    'easttimor' => 'timorleste',
    'timorleste' => 'timorleste',
    'eswatinifmrswaziland' => 'swaziland',
    'eswatini' => 'swaziland',
    'laos' => 'laopeoplesdemocraticrepublic',
    'libya' => 'libyanarabjamahiriya',
    'macau' => 'macao',
    'macedonia' => 'macedoniatheformeryugoslavrepublicof',
    'northmacedonia' => 'macedoniatheformeryugoslavrepublicof',
    'micronesia' => 'micronesiafederatedstatesof',
    'moldova' => 'moldovarepublicof',
    'myanmarformerlyburma' => 'myanmar',
    'myanmarburma' => 'myanmar',
    'burma' => 'myanmar',
    'palestinestate' => 'palestinianterritoryoccupied',
    'palestine' => 'palestinianterritoryoccupied',
    'saintkittsandnevis' => 'saintkittsandnevis',
    'stkittsandnevis' => 'saintkittsandnevis',
    'saintlucia' => 'saintlucia',
    'stlucia' => 'saintlucia',
    'saintvincentandthegrenadines' => 'saintvincentandthegrenadines',
    'stvincentandthegrenadines' => 'saintvincentandthegrenadines',
    'serbia' => 'serbiaandmontenegro',
    'unitedkingdom' => 'unitedkingdom',
    'uk' => 'unitedkingdom',
    'greatbritain' => 'unitedkingdom',
];

$existing = $conn->fetchAllAssociative('SELECT country_id, country_name, country_sf_id FROM lookup_country');
$existingNormMap = [];
foreach ($existing as $e) {
    $norm = normalizeName($e['country_name']);
    $existingNormMap[$norm] = $e;
}

$updatedCount = 0;
$insertedCount = 0;
$matchedIds = [];

foreach ($matches as $m) {
    $iso = strtoupper(trim($m[2]));
    $name = trim($m[3]);
    $nicename = trim($m[4]);
    $iso3 = $m[5] === 'NULL' ? null : trim($m[5], "'");
    $numcode = $m[6] === 'NULL' ? null : (int)$m[6];
    $phonecode = (int)$m[7];

    $normName = normalizeName($name);
    $normNice = normalizeName($nicename);

    $matchedEntry = $existingNormMap[$normName] 
        ?? $existingNormMap[$normNice] 
        ?? (isset($aliases[$normName]) ? ($existingNormMap[$aliases[$normName]] ?? null) : null)
        ?? (isset($aliases[$normNice]) ? ($existingNormMap[$aliases[$normNice]] ?? null) : null);

    if (!$matchedEntry) {
        foreach ($aliases as $aliasKey => $targetKey) {
            if ($targetKey === $normName || $targetKey === $normNice) {
                if (isset($existingNormMap[$aliasKey])) {
                    $matchedEntry = $existingNormMap[$aliasKey];
                    break;
                }
            }
        }
    }

    if ($matchedEntry) {
        $countryId = $matchedEntry['country_id'];
        $matchedIds[] = $countryId;
        $conn->update('lookup_country', [
            'iso' => $iso,
            'nicename' => $nicename,
            'iso3' => $iso3,
            'numcode' => $numcode,
            'phonecode' => $phonecode
        ], ['country_id' => $countryId]);
        $updatedCount++;
    } else {
        $conn->insert('lookup_country', [
            'country_name' => $nicename,
            'country_sf_id' => null,
            'iso' => $iso,
            'nicename' => $nicename,
            'iso3' => $iso3,
            'numcode' => $numcode,
            'phonecode' => $phonecode
        ]);
        $insertedCount++;
    }
}

// Check if any existing countries still missing iso/phonecode
$customFixes = [
    'South Sudan' => ['iso' => 'SS', 'nicename' => 'South Sudan', 'iso3' => 'SSD', 'numcode' => 728, 'phonecode' => 211],
    'Montenegro' => ['iso' => 'ME', 'nicename' => 'Montenegro', 'iso3' => 'MNE', 'numcode' => 499, 'phonecode' => 382],
    'Kosovo' => ['iso' => 'XK', 'nicename' => 'Kosovo', 'iso3' => 'XKX', 'numcode' => 0, 'phonecode' => 383],
    'Côte d\'Ivoire' => ['iso' => 'CI', 'nicename' => 'Cote D\'Ivoire', 'iso3' => 'CIV', 'numcode' => 384, 'phonecode' => 225],
    'Laos' => ['iso' => 'LA', 'nicename' => 'Lao People\'s Democratic Republic', 'iso3' => 'LAO', 'numcode' => 418, 'phonecode' => 856],
    'North Korea' => ['iso' => 'KP', 'nicename' => 'Korea, Democratic People\'s Republic of', 'iso3' => 'PRK', 'numcode' => 408, 'phonecode' => 850],
];

foreach ($customFixes as $cName => $cData) {
    $norm = normalizeName($cName);
    if (isset($existingNormMap[$norm])) {
        $cid = $existingNormMap[$norm]['country_id'];
        $conn->update('lookup_country', $cData, ['country_id' => $cid]);
        echo "Applied custom fix for $cName (ID $cid)\n";
    }
}

// Fallback for any country with missing nicename: use country_name
$conn->executeStatement("UPDATE lookup_country SET nicename = country_name WHERE nicename IS NULL OR nicename = ''");

echo "\n--- Summary ---\n";
echo "Updated existing countries: $updatedCount\n";
echo "Inserted new countries: $insertedCount\n";

$totalInDb = $conn->fetchOne('SELECT COUNT(*) FROM lookup_country');
echo "Total countries in database now: $totalInDb\n";

// Show sample rows
$sample = $conn->fetchAllAssociative('SELECT country_id, country_name, nicename, iso, phonecode FROM lookup_country ORDER BY country_name ASC LIMIT 10');
foreach ($sample as $s) {
    echo sprintf("%3d | %-25s | %-2s | +%-5s\n", $s['country_id'], $s['country_name'], $s['iso'] ?? '??', $s['phonecode'] ?? '0');
}
