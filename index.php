<?php
declare(strict_types=1);

require_once __DIR__ . '/getData.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatUpdatedAt(string $updatedAt): string
{
    $timestamp = strtotime($updatedAt);

    if ($timestamp === false) {
        return 'Bijwerkt onbekend';
    }

    return 'Bijgewerkt ' . date('H:i', $timestamp);
}

$leader = $players[0] ?? null;
$totalPhotos = array_sum(array_map(static fn (array $player): int => (int) $player['photos'], $players));
$totalBadges = array_sum(array_map(static fn (array $player): int => (int) $player['badges'], $players));
$statusLabels = [
    'live' => 'Live data',
    'cached' => 'Cache data',
    'setup' => 'Setup nodig',
];
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Untappd Ranking</title>
    <link rel="stylesheet" href="style.css">
    <script src="sortTable.js" defer></script>
</head>
<body>
    <main class="app-shell">
        <section class="hero" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Untappd friends contest</p>
                <h1 id="page-title">Beer bragging rights, netjes op score.</h1>
                <p class="intro">
                    Foto's en badges worden gecombineerd tot een simpele ranking. Hoogste score bovenaan,
                    laatste check-in ernaast.
                </p>
            </div>

            <div class="hero-panel" aria-label="Huidige leider">
                <span class="panel-label">Bovenaan</span>
                <strong><?= e((string) ($leader['displayName'] ?? 'Nog niemand')) ?></strong>
                <span><?= e((string) ($leader['latestBeer'] ?? 'Voeg spelers toe in APIData.php')) ?></span>
            </div>
        </section>

        <section class="stats" aria-label="Samenvatting">
            <div>
                <span><?= count($players) ?></span>
                <p>spelers</p>
            </div>
            <div>
                <span><?= number_format($totalPhotos, 0, ',', '.') ?></span>
                <p>foto's</p>
            </div>
            <div>
                <span><?= number_format($totalBadges, 0, ',', '.') ?></span>
                <p>badges</p>
            </div>
            <div>
                <span><?= e($statusLabels[$dataStatus] ?? 'Status') ?></span>
                <p><?= e($dataMessage !== '' ? $dataMessage : formatUpdatedAt($updatedAt)) ?></p>
            </div>
        </section>

        <section class="leaderboard" aria-labelledby="ranking-title">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Leaderboard</p>
                    <h2 id="ranking-title">Ranking</h2>
                </div>
                <div class="actions">
                    <a class="button-link" href="?refresh=1">Ververs data</a>
                    <a class="text-link" href="https://untappd.com/" rel="noreferrer" target="_blank">Open Untappd</a>
                </div>
            </div>

            <div class="table-wrap">
                <table id="rankingTable">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" data-sort="name">Speler</th>
                            <th scope="col" data-sort="number">Score</th>
                            <th scope="col" data-sort="number">Foto's</th>
                            <th scope="col" data-sort="number">Badges</th>
                            <th scope="col" data-sort="beer">Laatste bier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $index => $player): ?>
                            <?php
                            $rank = $index + 1;
                            $label = (string) ($player['label'] ?? '');
                            ?>
                            <tr>
                                <td data-label="#">
                                    <span class="rank"><?= $rank ?></span>
                                </td>
                                <td data-label="Speler">
                                    <a class="player" href="<?= e((string) $player['profileUrl']) ?>" target="_blank" rel="noreferrer">
                                        <?php if ($label !== ''): ?>
                                            <img src="<?= e($label) ?>" alt="" loading="lazy">
                                        <?php else: ?>
                                            <span class="label-fallback" aria-hidden="true"><?= e(strtoupper(substr((string) $player['username'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                        <span>
                                            <strong><?= e((string) $player['displayName']) ?></strong>
                                            <small>@<?= e((string) $player['username']) ?></small>
                                        </span>
                                    </a>
                                </td>
                                <td data-label="Score" data-value="<?= e((string) $player['score']) ?>">
                                    <strong><?= e(number_format((float) $player['score'], 1, ',', '.')) ?></strong>
                                </td>
                                <td data-label="Foto's" data-value="<?= e((string) $player['photos']) ?>">
                                    <?= e(number_format((int) $player['photos'], 0, ',', '.')) ?>
                                </td>
                                <td data-label="Badges" data-value="<?= e((string) $player['badges']) ?>">
                                    <?= e(number_format((int) $player['badges'], 0, ',', '.')) ?>
                                </td>
                                <td data-label="Laatste bier"><?= e((string) $player['latestBeer']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
