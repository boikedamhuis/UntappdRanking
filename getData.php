<?php
declare(strict_types=1);

require_once __DIR__ . '/APIData.php';

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
    return round(($photos + $badges) / 2, 1);
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
    $recentBeer = $user->recent_brews->items[0]->beer ?? null;

    return [
        'username' => $username,
        'displayName' => trim((string) ($user->user_name ?? $username)),
        'score' => untappdScore($photos, $badges),
        'photos' => $photos,
        'badges' => $badges,
        'latestBeer' => (string) ($recentBeer->beer_name ?? 'Nog geen recente check-in'),
        'label' => (string) ($recentBeer->beer_label ?? ''),
        'profileUrl' => 'https://untappd.com/user/' . rawurlencode($username),
    ];
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
        curl_close($curl);

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
        return [
            'username' => $username,
            'displayName' => $username,
            'score' => 0,
            'photos' => 0,
            'badges' => 0,
            'latestBeer' => 'Vul je Untappd API keys in',
            'label' => '',
            'profileUrl' => 'https://untappd.com/user/' . rawurlencode($username),
        ];
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

usort($players, static function (array $first, array $second): int {
    return ($second['score'] <=> $first['score']) ?: strcmp($first['username'], $second['username']);
});
