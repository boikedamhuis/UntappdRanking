<?php
declare(strict_types=1);

$localConfigFile = __DIR__ . '/APIData.local.php';
$defaultConfigFile = __DIR__ . '/APIData.php';

if (is_readable($localConfigFile)) {
    require_once $localConfigFile;
} else {
    require_once $defaultConfigFile;
}

$untappdUsers = array_values(array_filter($DATAUSERNAMES ?? []));
$cacheFile = __DIR__ . '/savedUntappdData.json';
$honorsFile = __DIR__ . '/eventHonors.json';
$cacheTtl = 60 * 30;
$shouldRefresh = ($_GET['refresh'] ?? '') === '1';

$players = [];
$dataStatus = 'live';
$dataMessage = '';
$updatedAt = date('c');
$activeSeason = untappdActiveSeason();
$eventHonors = untappdReadEventHonors($honorsFile);

ini_set('default_socket_timeout', '3');

function untappdActiveSeason(?DateTimeImmutable $now = null): array
{
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));
    $year = (int) $now->format('Y');
    $month = (int) $now->format('n');
    $day = (int) $now->format('j');
    $ymd = $now->format('Y-m-d');

    $events = [
        [
            'name' => 'Nieuwjaarsduik',
            'start' => "$year-01-01",
            'end' => "$year-01-15",
            'description' => 'Fris beginnen: laag-ABV, alcoholvrij, crispy lagers en verrassend licht spul.',
            'focusCategories' => ['Lager & crispy', 'Tarwe & zacht'],
            'focusKeywords' => ['table', 'session', 'pilsner', 'lager', 'witbier', 'low alcohol', 'non-alcoholic'],
            'type' => 'event',
            'honorPoints' => 20,
            'trophy' => 'Nieuwjaarsduik winnaar',
        ],
        [
            'name' => 'Paasproeverij',
            'start' => untappdEasterDate($year, -3),
            'end' => untappdEasterDate($year, 2),
            'description' => 'Pasen beloont lente, saison, blond, witbier, fruitig en fris zuur.',
            'focusCategories' => ['Belgisch & klassiek', 'Tarwe & zacht', 'Fruit & kruidig', 'Zuur & wild'],
            'focusKeywords' => ['easter', 'paas', 'saison', 'blonde', 'witbier', 'white ale', 'spring'],
            'type' => 'event',
            'honorPoints' => 25,
            'trophy' => 'Paasproeverij winnaar',
        ],
        [
            'name' => 'Koningsdag Kraker',
            'start' => "$year-04-20",
            'end' => "$year-04-30",
            'description' => 'Oranje weken: fruitig, kruidig, Nederlands, feestelijk en net een tikje onverstandig.',
            'focusCategories' => ['Fruit & kruidig', 'Lager & crispy', 'Belgisch & klassiek'],
            'focusKeywords' => ['orange', 'oranje', 'citrus', 'dutch', 'netherlands', 'koning'],
            'type' => 'event',
            'honorPoints' => 30,
            'trophy' => 'Koningsdag kampioen',
        ],
        [
            'name' => 'Oktoberfest Sprint',
            'start' => "$year-09-15",
            'end' => "$year-10-06",
            'description' => 'Märzen, festbier, bock en crispy halve liters krijgen seizoensglans.',
            'focusCategories' => ['Lager & crispy'],
            'focusKeywords' => ['festbier', 'marzen', 'märzen', 'oktoberfest', 'bock', 'helles', 'lager'],
            'type' => 'event',
            'honorPoints' => 25,
            'trophy' => 'Oktoberfest winnaar',
        ],
        [
            'name' => 'Halloween Horror Pour',
            'start' => "$year-10-24",
            'end' => "$year-11-03",
            'description' => 'Donker, rokerig, zuur, pompoen en andere rare vondsten scoren extra.',
            'focusCategories' => ['Donker & geroosterd', 'Zuur & wild', 'Fruit & kruidig'],
            'focusKeywords' => ['pumpkin', 'smoked', 'rauch', 'black', 'chili', 'wild', 'sour'],
            'type' => 'event',
            'honorPoints' => 25,
            'trophy' => 'Halloween horrorwinnaar',
        ],
        [
            'name' => 'Kerstkelder',
            'start' => "$year-12-10",
            'end' => "$year-12-31",
            'description' => 'Kerstbier, barrel aged, zwaar donker en kruidige winterwarmers doen mee.',
            'focusCategories' => ['Sterk & sipper', 'Donker & geroosterd', 'Vat & hout', 'Belgisch & klassiek'],
            'focusKeywords' => ['christmas', 'kerst', 'winter', 'barrel', 'quad', 'quadrupel', 'spiced'],
            'type' => 'event',
            'honorPoints' => 30,
            'trophy' => 'Kerstkelder kampioen',
        ],
    ];

    foreach ($events as $event) {
        if ($ymd >= $event['start'] && $ymd <= $event['end']) {
            return untappdSeasonPayload($event, $now);
        }
    }

    if ($month >= 3 && $month <= 5) {
        return untappdSeasonPayload([
            'name' => 'Lentebokaal',
            'start' => "$year-03-01",
            'end' => "$year-05-31",
            'description' => 'Lente beloont fris, zuur, fruitig, saison, witbier en lichte ontdekkingen.',
            'focusCategories' => ['Zuur & wild', 'Fruit & kruidig', 'Tarwe & zacht', 'Belgisch & klassiek', 'Lager & crispy'],
            'focusKeywords' => ['saison', 'witbier', 'white ale', 'fruit', 'sour', 'gose', 'session', 'spring'],
        ], $now);
    }

    if ($month >= 6 && $month <= 8) {
        return untappdSeasonPayload([
            'name' => 'Zomercompetitie',
            'start' => "$year-06-01",
            'end' => "$year-08-31",
            'description' => 'Zomer geeft bonus aan crispy, fruitig, zuur, laag-ABV en dorstlessende stijlen.',
            'focusCategories' => ['Lager & crispy', 'Fruit & kruidig', 'Zuur & wild', 'Tarwe & zacht'],
            'focusKeywords' => ['radler', 'session', 'gose', 'berliner', 'fruit', 'citrus', 'pilsner', 'lager'],
        ], $now);
    }

    if ($month >= 9 && $month <= 11) {
        return untappdSeasonPayload([
            'name' => 'Herfstproeverij',
            'start' => "$year-09-01",
            'end' => "$year-11-30",
            'description' => 'Herfst houdt van bock, amber, donker, rook, barrel aged en kruidige glazen.',
            'focusCategories' => ['Donker & geroosterd', 'Vat & hout', 'Sterk & sipper', 'Lager & crispy'],
            'focusKeywords' => ['bock', 'barrel', 'smoked', 'rauch', 'amber', 'brown', 'pumpkin', 'spiced'],
        ], $now);
    }

    $seasonYear = $month === 12 ? $year : $year - 1;

    return untappdSeasonPayload([
        'name' => 'Winterklassement',
        'start' => "$seasonYear-12-01",
        'end' => ($seasonYear + 1) . '-02-28',
        'description' => 'Winter beloont stevige sippers, donker bier, vatrijping en kruidige klassiekers.',
        'focusCategories' => ['Sterk & sipper', 'Donker & geroosterd', 'Vat & hout', 'Belgisch & klassiek'],
        'focusKeywords' => ['winter', 'christmas', 'barrel', 'stout', 'porter', 'quadrupel', 'barleywine', 'spiced'],
    ], $now);
}

function untappdEasterDate(int $year, int $offsetDays = 0): string
{
    return (new DateTimeImmutable('@' . easter_date($year)))
        ->setTimezone(new DateTimeZone('Europe/Amsterdam'))
        ->modify(($offsetDays >= 0 ? '+' : '') . $offsetDays . ' days')
        ->format('Y-m-d');
}

function untappdSeasonPayload(array $season, DateTimeImmutable $now): array
{
    $start = new DateTimeImmutable($season['start'] . ' 00:00:00', new DateTimeZone('Europe/Amsterdam'));
    $end = new DateTimeImmutable($season['end'] . ' 23:59:59', new DateTimeZone('Europe/Amsterdam'));
    $daysTotal = max(1, (int) $start->diff($end)->format('%a') + 1);
    $daysLeft = max(0, (int) $now->diff($end)->format('%r%a'));

    return [
        'name' => $season['name'],
        'label' => $season['name'] . ' ' . $now->format('Y'),
        'description' => $season['description'],
        'start' => $start->format('c'),
        'end' => $end->format('c'),
        'daysLeft' => $daysLeft,
        'progress' => min(100, max(0, round((($daysTotal - $daysLeft) / $daysTotal) * 100))),
        'focusCategories' => $season['focusCategories'],
        'focusKeywords' => $season['focusKeywords'],
        'type' => $season['type'] ?? 'season',
        'honorPoints' => (int) ($season['honorPoints'] ?? 0),
        'trophy' => $season['trophy'] ?? ($season['name'] . ' winnaar'),
        'id' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $season['name'] . '-' . $now->format('Y'))),
    ];
}

function untappdReadEventHonors(string $honorsFile): array
{
    if (!is_readable($honorsFile)) {
        return ['honors' => []];
    }

    $payload = json_decode((string) file_get_contents($honorsFile), true);

    if (!is_array($payload) || !isset($payload['honors']) || !is_array($payload['honors'])) {
        return ['honors' => []];
    }

    return $payload;
}

function untappdWriteEventHonors(string $honorsFile, array $eventHonors): void
{
    file_put_contents($honorsFile, json_encode($eventHonors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function untappdPlayerHonorPoints(string $username, array $eventHonors): int
{
    $points = 0;

    foreach ($eventHonors['honors'] ?? [] as $honor) {
        if (($honor['username'] ?? '') === $username) {
            $points += (int) ($honor['points'] ?? 0);
        }
    }

    return $points;
}

function untappdPlayerHonors(string $username, array $eventHonors): array
{
    return array_values(array_filter($eventHonors['honors'] ?? [], static function (array $honor) use ($username): bool {
        return ($honor['username'] ?? '') === $username;
    }));
}

function untappdReadCache(string $cacheFile): array
{
    if (!is_readable($cacheFile)) {
        return [];
    }

    $cached = json_decode((string) file_get_contents($cacheFile), true);

    if (!is_array($cached) || !isset($cached['players']) || !is_array($cached['players'])) {
        return [];
    }

    return $cached;
}

function untappdWriteCache(string $cacheFile, array $players): void
{
    $payload = [
        'updatedAt' => date('c'),
        'players' => $players,
    ];

    file_put_contents($cacheFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function untappdScore(int $photos, int $badges): float
{
    return round((sqrt($photos) * 0.8) + (sqrt($badges) * 1.2), 1);
}

function untappdNormalizeBeer(object $item): array
{
    $beer = $item->beer ?? null;
    $brewery = $item->brewery ?? null;

    return [
        'name' => (string) ($beer->beer_name ?? ''),
        'style' => (string) ($beer->beer_style ?? ''),
        'abv' => (float) ($beer->beer_abv ?? 0),
        'ibu' => (int) ($beer->beer_ibu ?? 0),
        'label' => (string) ($beer->beer_label ?? ''),
        'brewery' => (string) ($brewery->brewery_name ?? ''),
        'country' => (string) ($brewery->country_name ?? ''),
        'rating' => (float) ($item->rating_score ?? 0),
        'createdAt' => (string) ($item->created_at ?? ''),
    ];
}

function untappdBeerCategory(array $beer): string
{
    $text = strtolower(($beer['style'] ?? '') . ' ' . ($beer['name'] ?? ''));

    $categories = [
        'Zuur & wild' => ['sour', 'wild', 'lambic', 'gueuze', 'gose', 'berliner', 'brett', 'farmhouse'],
        'Hop & bitter' => ['ipa', 'pale ale', 'double ipa', 'triple ipa', 'neipa', 'hazy', 'bitter'],
        'Donker & geroosterd' => ['stout', 'porter', 'black ale', 'schwarzbier', 'roasted'],
        'Vat & hout' => ['barrel', 'bourbon', 'oak', 'aged', 'ba', 'foeder'],
        'Fruit & kruidig' => ['fruit', 'spiced', 'herb', 'chili', 'pepper', 'pumpkin', 'tepache'],
        'Sterk & sipper' => ['barleywine', 'quadrupel', 'tripel', 'strong ale', 'old ale', 'imperial'],
        'Belgisch & klassiek' => ['belgian', 'saison', 'dubbel', 'tripel', 'witbier', 'abbey'],
        'Tarwe & zacht' => ['wheat', 'weizen', 'hefeweizen', 'white ale', 'blonde'],
        'Lager & crispy' => ['lager', 'pilsner', 'helles', 'kolsch', 'bock', 'marzen'],
    ];

    foreach ($categories as $category => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return $category;
            }
        }
    }

    return 'Anders';
}

function untappdWeirdnessPoints(array $beers): array
{
    $keywords = [
        'wild' => 4,
        'lambic' => 5,
        'gueuze' => 6,
        'brett' => 5,
        'smoked' => 5,
        'rauch' => 5,
        'barrel' => 4,
        'bourbon' => 4,
        'foeder' => 5,
        'imperial' => 3,
        'barleywine' => 5,
        'pastry' => 4,
        'chili' => 4,
        'tepache' => 5,
        'pickle' => 6,
        'oyster' => 6,
        'milkshake' => 3,
        'farmhouse' => 3,
        'sour' => 3,
        'gose' => 3,
    ];

    $hits = [];
    $points = 0;

    foreach ($beers as $beer) {
        $text = strtolower(($beer['style'] ?? '') . ' ' . ($beer['name'] ?? ''));

        foreach ($keywords as $keyword => $value) {
            if (!isset($hits[$keyword]) && str_contains($text, $keyword)) {
                $hits[$keyword] = $keyword;
                $points += $value;
            }
        }
    }

    return [
        'points' => min(28, $points),
        'tags' => array_values($hits),
    ];
}

function untappdSeasonBeers(array $beers, array $season): array
{
    $start = strtotime((string) ($season['start'] ?? ''));
    $end = strtotime((string) ($season['end'] ?? ''));

    if ($start === false || $end === false) {
        return $beers;
    }

    $seasonBeers = array_values(array_filter($beers, static function (array $beer) use ($start, $end): bool {
        if (empty($beer['createdAt'])) {
            return false;
        }

        $createdAt = strtotime((string) $beer['createdAt']);

        return $createdAt !== false && $createdAt >= $start && $createdAt <= $end;
    }));

    return $seasonBeers !== [] ? $seasonBeers : $beers;
}

function untappdSeasonPoints(array $beers, array $season): array
{
    $hits = [];
    $points = 0;
    $focusCategories = $season['focusCategories'] ?? [];
    $focusKeywords = $season['focusKeywords'] ?? [];

    foreach ($beers as $beer) {
        $category = untappdBeerCategory($beer);
        $text = strtolower(($beer['style'] ?? '') . ' ' . ($beer['name'] ?? '') . ' ' . ($beer['country'] ?? ''));

        if (in_array($category, $focusCategories, true) && !isset($hits[$category])) {
            $hits[$category] = $category;
            $points += 3;
        }

        foreach ($focusKeywords as $keyword) {
            if (!isset($hits[$keyword]) && str_contains($text, strtolower((string) $keyword))) {
                $hits[$keyword] = (string) $keyword;
                $points += 2;
            }
        }
    }

    return [
        'points' => min(12, $points),
        'hits' => array_values($hits),
    ];
}

function untappdAdventureScore(array $player): array
{
    $season = $GLOBALS['activeSeason'] ?? untappdActiveSeason();
    $beers = $player['recentBeers'] ?? [];

    if ($beers === [] && !empty($player['latestBeer'])) {
        $beers = [[
            'name' => (string) $player['latestBeer'],
            'style' => '',
            'abv' => 0,
            'ibu' => 0,
            'label' => (string) ($player['label'] ?? ''),
            'brewery' => '',
            'country' => '',
            'rating' => 0,
            'createdAt' => '',
        ]];
    }

    $seasonBeers = untappdSeasonBeers($beers, $season);
    $styles = [];
    $categories = [];
    $countries = [];
    $abvPoints = 0;

    foreach ($seasonBeers as $beer) {
        $style = trim((string) ($beer['style'] ?? ''));
        $country = trim((string) ($beer['country'] ?? ''));
        $abv = (float) ($beer['abv'] ?? 0);

        if ($style !== '') {
            $styles[strtolower($style)] = $style;
        }

        if ($country !== '') {
            $countries[strtolower($country)] = $country;
        }

        $category = untappdBeerCategory($beer);
        if ($category !== 'Anders') {
            $categories[$category] = $category;
        }

        if ($abv >= 12) {
            $abvPoints += 5;
        } elseif ($abv >= 9) {
            $abvPoints += 4;
        } elseif ($abv >= 7) {
            $abvPoints += 2;
        } elseif ($abv > 0 && $abv <= 3.5) {
            $abvPoints += 2;
        }
    }

    $weirdness = untappdWeirdnessPoints($seasonBeers);
    $seasonPoints = untappdSeasonPoints($seasonBeers, $season);
    $stylePoints = min(30, count($styles) * 5);
    $categoryPoints = min(30, count($categories) * 6);
    $countryPoints = min(12, count($countries) * 3);
    $abvPoints = min(15, $abvPoints);
    $badgePoints = min(6, sqrt((int) ($player['badges'] ?? 0)) * 0.25);
    $photoPoints = min(3, sqrt((int) ($player['photos'] ?? 0)) * 0.12);
    $total = round($stylePoints + $categoryPoints + $weirdness['points'] + $abvPoints + $countryPoints + $seasonPoints['points'] + $badgePoints + $photoPoints, 1);

    return [
        'seasonScore' => $total,
        'breakdown' => [
            'style' => $stylePoints,
            'category' => $categoryPoints,
            'weird' => $weirdness['points'],
            'abv' => $abvPoints,
            'country' => $countryPoints,
            'season' => $seasonPoints['points'],
            'badge' => round($badgePoints, 1),
            'photo' => round($photoPoints, 1),
        ],
        'categories' => array_values($categories),
        'weirdTags' => $weirdness['tags'],
        'seasonHits' => $seasonPoints['hits'],
        'seasonCheckins' => count($seasonBeers),
        'uniqueStyles' => count($styles),
        'uniqueCountries' => count($countries),
    ];
}

function untappdAchievements(array $player): array
{
    $categories = $player['categories'] ?? [];
    $tags = $player['weirdTags'] ?? [];
    $beers = $player['recentBeers'] ?? [];
    $achievements = [];

    $hasCategory = static fn (string $category): bool => in_array($category, $categories, true);
    $hasTag = static fn (string $tag): bool => in_array($tag, $tags, true);

    if (($player['uniqueStyles'] ?? 0) >= 4) {
        $achievements[] = [
            'name' => 'Style Nomad',
            'reason' => 'Minstens 4 verschillende stijlen in recente check-ins.',
        ];
    }

    if (($player['uniqueCountries'] ?? 0) >= 3) {
        $achievements[] = [
            'name' => 'World Tour',
            'reason' => 'Bieren uit minstens 3 landen.',
        ];
    }

    if ($hasCategory('Zuur & wild') || $hasTag('lambic') || $hasTag('gueuze') || $hasTag('brett')) {
        $achievements[] = [
            'name' => 'Sour Survivor',
            'reason' => 'Zuur, wild, lambic, gueuze of brett gespot.',
        ];
    }

    if ($hasCategory('Vat & hout') || $hasTag('barrel') || $hasTag('bourbon') || $hasTag('foeder')) {
        $achievements[] = [
            'name' => 'Barrel Baron',
            'reason' => 'Vatrijping of houtinvloed in de lijst.',
        ];
    }

    if ($hasCategory('Sterk & sipper') || $hasTag('barleywine') || $hasTag('imperial')) {
        $achievements[] = [
            'name' => 'Big Sipper',
            'reason' => 'Een sterk of langzaam drinkbaar bier telt mee.',
        ];
    }

    if ($hasCategory('Lager & crispy')) {
        $achievements[] = [
            'name' => 'Crispy Diplomat',
            'reason' => 'Ook lager, pils of andere crispy stijlen krijgen liefde.',
        ];
    }

    foreach ($beers as $beer) {
        $abv = (float) ($beer['abv'] ?? 0);

        if ($abv > 0 && $abv <= 3.5) {
            $achievements[] = [
                'name' => 'Tiny Beer Hero',
                'reason' => 'Low-ABV bier gevonden zonder saai te doen.',
            ];
            break;
        }
    }

    if (count($tags) >= 2) {
        $achievements[] = [
            'name' => 'Weird Flex',
            'reason' => 'Meerdere rare tags in recente bieren.',
        ];
    }

    if (count($player['seasonHits'] ?? []) >= 2) {
        $achievements[] = [
            'name' => 'Season Hunter',
            'reason' => 'Meerdere bieren passen perfect bij het actieve seizoen.',
        ];
    }

    return array_slice($achievements, 0, 4);
}

function untappdApplyScoring(array $player): array
{
    $adventure = untappdAdventureScore($player);
    $eventHonors = $GLOBALS['eventHonors'] ?? ['honors' => []];
    $username = (string) ($player['username'] ?? '');
    $honorPoints = untappdPlayerHonorPoints($username, $eventHonors);

    $player['seasonScore'] = $adventure['seasonScore'];
    $player['honorPoints'] = $honorPoints;
    $player['score'] = round($adventure['seasonScore'] + $honorPoints, 1);
    $player['scoreBreakdown'] = $adventure['breakdown'];
    $player['scoreBreakdown']['honors'] = $honorPoints;
    $player['categories'] = $adventure['categories'];
    $player['weirdTags'] = $adventure['weirdTags'];
    $player['seasonHits'] = $adventure['seasonHits'];
    $player['seasonCheckins'] = $adventure['seasonCheckins'];
    $player['uniqueStyles'] = $adventure['uniqueStyles'];
    $player['uniqueCountries'] = $adventure['uniqueCountries'];
    $player['eventHonors'] = untappdPlayerHonors($username, $eventHonors);
    $player['achievements'] = untappdAchievements($player);

    return $player;
}

function untappdRecordActiveEventHonor(array $players, array $activeSeason, array $eventHonors, string $honorsFile): array
{
    if (($activeSeason['type'] ?? 'season') !== 'event' || (int) ($activeSeason['honorPoints'] ?? 0) <= 0 || $players === []) {
        return $eventHonors;
    }

    $candidates = array_values(array_filter($players, static function (array $player): bool {
        return (int) ($player['seasonCheckins'] ?? 0) > 0;
    }));

    if ($candidates === []) {
        return $eventHonors;
    }

    usort($candidates, static function (array $first, array $second): int {
        return ($second['seasonScore'] <=> $first['seasonScore']) ?: strcmp((string) $first['username'], (string) $second['username']);
    });

    $winner = $candidates[0];
    $seasonId = (string) $activeSeason['id'];
    $eventHonors['honors'][$seasonId] = [
        'seasonId' => $seasonId,
        'season' => (string) $activeSeason['name'],
        'label' => (string) $activeSeason['label'],
        'trophy' => (string) $activeSeason['trophy'],
        'username' => (string) $winner['username'],
        'displayName' => (string) $winner['displayName'],
        'points' => (int) $activeSeason['honorPoints'],
        'winningScore' => (float) $winner['seasonScore'],
        'awardedAt' => date('c'),
    ];

    untappdWriteEventHonors($honorsFile, $eventHonors);

    return $eventHonors;
}

function untappdFetchUser(string $username, string $clientId, string $clientSecret): ?array
{
    $url = sprintf(
        'https://api.untappd.com/v4/user/info/%s?client_id=%s&client_secret=%s',
        rawurlencode($username),
        rawurlencode($clientId),
        rawurlencode($clientSecret)
    );

    $json = untappdHttpGet($url);
    if ($json === false) {
        return null;
    }

    $payload = json_decode($json);
    $user = $payload->response->user ?? null;

    if ($user === null) {
        return null;
    }

    $photos = (int) ($user->stats->total_photos ?? 0);
    $badges = (int) ($user->stats->total_badges ?? 0);
    $recentItems = is_array($user->recent_brews->items ?? null) ? $user->recent_brews->items : [];
    $recentBeers = array_values(array_filter(array_map('untappdNormalizeBeer', $recentItems), static function (array $beer): bool {
        return $beer['name'] !== '';
    }));
    $recentBeer = $recentBeers[0] ?? [];
    $label = (string) ($recentBeer['label'] ?? '');

    return untappdApplyScoring([
        'username' => $username,
        'displayName' => trim((string) ($user->user_name ?? $username)),
        'photos' => $photos,
        'badges' => $badges,
        'latestBeer' => (string) ($recentBeer['name'] ?? 'Nog geen recente check-in'),
        'latestStyle' => (string) ($recentBeer['style'] ?? ''),
        'label' => $label,
        'recentBeers' => $recentBeers,
        'profileUrl' => 'https://untappd.com/user/' . rawurlencode($username),
    ]);
}

function untappdHttpGet(string $url): string|false
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);

        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_USERAGENT => 'UntappdRanking/2.0',
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if (!is_string($response) || $status >= 400) {
            return false;
        }

        return $response;
    }

    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 3,
            'user_agent' => 'UntappdRanking/2.0',
        ],
    ]);

    return @file_get_contents($url, false, $context);
}

function untappdFallbackPlayers(array $usernames): array
{
    return array_map(static function (string $username): array {
        return untappdApplyScoring([
            'username' => $username,
            'displayName' => $username,
            'photos' => 0,
            'badges' => 0,
            'latestBeer' => 'Vul je Untappd API keys in',
            'latestStyle' => '',
            'label' => '',
            'recentBeers' => [],
            'profileUrl' => 'https://untappd.com/user/' . rawurlencode($username),
        ]);
    }, $usernames);
}

$cache = untappdReadCache($cacheFile);
$cacheUpdatedAt = isset($cache['updatedAt']) ? strtotime((string) $cache['updatedAt']) : false;
$cacheIsFresh = $cacheUpdatedAt !== false && (time() - $cacheUpdatedAt < $cacheTtl);
$hasCredentials = !empty($CLIENTID) && !empty($CLIENTSECRET);

if ($cacheIsFresh && !$shouldRefresh) {
    $players = $cache['players'];
    $updatedAt = (string) $cache['updatedAt'];
    $dataStatus = 'cached';
    $dataMessage = 'Cache actief';
} elseif ($hasCredentials && $untappdUsers !== [] && $shouldRefresh) {
    foreach ($untappdUsers as $username) {
        $player = untappdFetchUser((string) $username, (string) $CLIENTID, (string) $CLIENTSECRET);

        if ($player !== null) {
            $players[] = $player;
        }
    }

    if ($players !== []) {
        untappdWriteCache($cacheFile, $players);
    } elseif (isset($cache['players'])) {
        $players = $cache['players'];
        $updatedAt = (string) ($cache['updatedAt'] ?? date('c'));
        $dataStatus = 'cached';
        $dataMessage = 'Live data niet bereikbaar, cache gebruikt';
    }
} elseif (isset($cache['players'])) {
    $players = $cache['players'];
    $updatedAt = (string) ($cache['updatedAt'] ?? date('c'));
    $dataStatus = 'cached';
    $dataMessage = $hasCredentials ? 'Cache gebruikt' : 'API keys ontbreken, cache gebruikt';
}

if ($players === []) {
    $players = untappdFallbackPlayers($untappdUsers);
    $dataStatus = 'setup';
    $dataMessage = $hasCredentials ? 'Klik verversen om Untappd data op te halen' : 'API keys ontbreken';
}

$players = array_map('untappdApplyScoring', $players);
$eventHonors = untappdRecordActiveEventHonor($players, $activeSeason, $eventHonors, $honorsFile);
$GLOBALS['eventHonors'] = $eventHonors;
$players = array_map('untappdApplyScoring', $players);

usort($players, static function (array $first, array $second): int {
    return ($second['score'] <=> $first['score']) ?: strcmp($first['username'], $second['username']);
});
