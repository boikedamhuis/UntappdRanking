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
$cacheTtl = 60 * 30;
$shouldRefresh = ($_GET['refresh'] ?? '') === '1';

$players = [];
$dataStatus = 'live';
$dataMessage = '';
$updatedAt = date('c');

ini_set('default_socket_timeout', '3');

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

function untappdAdventureScore(array $player): array
{
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
        ]];
    }

    $styles = [];
    $categories = [];
    $countries = [];
    $abvPoints = 0;

    foreach ($beers as $beer) {
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

    $weirdness = untappdWeirdnessPoints($beers);
    $stylePoints = min(30, count($styles) * 5);
    $categoryPoints = min(30, count($categories) * 6);
    $countryPoints = min(12, count($countries) * 3);
    $abvPoints = min(15, $abvPoints);
    $badgePoints = min(15, sqrt((int) ($player['badges'] ?? 0)) * 0.6);
    $photoPoints = min(5, sqrt((int) ($player['photos'] ?? 0)) * 0.2);
    $total = round($stylePoints + $categoryPoints + $weirdness['points'] + $abvPoints + $countryPoints + $badgePoints + $photoPoints, 1);

    return [
        'score' => $total,
        'breakdown' => [
            'style' => $stylePoints,
            'category' => $categoryPoints,
            'weird' => $weirdness['points'],
            'abv' => $abvPoints,
            'country' => $countryPoints,
            'badge' => round($badgePoints, 1),
            'photo' => round($photoPoints, 1),
        ],
        'categories' => array_values($categories),
        'weirdTags' => $weirdness['tags'],
        'uniqueStyles' => count($styles),
        'uniqueCountries' => count($countries),
    ];
}

function untappdApplyScoring(array $player): array
{
    $adventure = untappdAdventureScore($player);

    $player['score'] = $adventure['score'];
    $player['scoreBreakdown'] = $adventure['breakdown'];
    $player['categories'] = $adventure['categories'];
    $player['weirdTags'] = $adventure['weirdTags'];
    $player['uniqueStyles'] = $adventure['uniqueStyles'];
    $player['uniqueCountries'] = $adventure['uniqueCountries'];

    return $player;
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

usort($players, static function (array $first, array $second): int {
    return ($second['score'] <=> $first['score']) ?: strcmp($first['username'], $second['username']);
});
