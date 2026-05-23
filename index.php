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

function uniquePlayerValues(array $players, string $key): array
{
    $values = [];

    foreach ($players as $player) {
        foreach (($player[$key] ?? []) as $value) {
            $values[(string) $value] = (string) $value;
        }
    }

    return array_values($values);
}

$leader = $players[0] ?? null;
$averageScore = count($players) > 0 ? array_sum(array_map(static fn (array $player): float => (float) $player['score'], $players)) / count($players) : 0;
$allCategories = uniquePlayerValues($players, 'categories');
$allWeirdTags = uniquePlayerValues($players, 'weirdTags');
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
                <h1 id="page-title">Beer bragging rights voor avonturiers.</h1>
                <p class="intro">
                    Niet de grootste drinklijst wint, maar de speler met de meeste stijlspreiding,
                    gekke vondsten, sterke sippers, zure uitstapjes en internationale omwegen.
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
                <span><?= e(number_format($averageScore, 1, ',', '.')) ?></span>
                <p>gem. explorer score</p>
            </div>
            <div>
                <span><?= count($allCategories) ?></span>
                <p>categorieën</p>
            </div>
            <div>
                <span><?= count($allWeirdTags) ?></span>
                <p>gekke tags</p>
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
                            <th scope="col" data-sort="number">Spreiding</th>
                            <th scope="col" data-sort="tags">Bijzonder</th>
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
                                    <small class="score-note">
                                        stijl <?= e((string) ($player['scoreBreakdown']['style'] ?? 0)) ?>,
                                        gek <?= e((string) ($player['scoreBreakdown']['weird'] ?? 0)) ?>,
                                        abv <?= e((string) ($player['scoreBreakdown']['abv'] ?? 0)) ?>
                                    </small>
                                </td>
                                <td data-label="Spreiding" data-value="<?= e((string) (($player['uniqueStyles'] ?? 0) + ($player['uniqueCountries'] ?? 0))) ?>">
                                    <div class="spread">
                                        <strong><?= e((string) ($player['uniqueStyles'] ?? 0)) ?></strong> stijlen
                                        <span><?= e((string) ($player['uniqueCountries'] ?? 0)) ?> landen</span>
                                    </div>
                                </td>
                                <td data-label="Bijzonder">
                                    <?php $tags = array_slice(array_merge($player['categories'] ?? [], $player['weirdTags'] ?? []), 0, 4); ?>
                                    <?php if ($tags !== []): ?>
                                        <div class="tag-list">
                                            <?php foreach ($tags as $tag): ?>
                                                <span><?= e((string) $tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">Nog geen recente stijl-data</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Laatste bier">
                                    <?= e((string) $player['latestBeer']) ?>
                                    <?php if (!empty($player['latestStyle'])): ?>
                                        <small class="score-note"><?= e((string) $player['latestStyle']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
