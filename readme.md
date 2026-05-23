# Untappd Ranking

A small PHP leaderboard for an Untappd friends contest. It combines each user's
photo and badge totals into a score, shows the latest checked-in beer, and sorts
everyone into a clean responsive ranking.

## Setup

1. Request a client ID and client secret at <https://untappd.com/api/>.
2. Copy `APIData.example.php` to `APIData.local.php`.
3. Add your credentials and usernames to `APIData.local.php`.
4. Serve the folder with PHP and open `index.php`.

## How It Works

`getData.php` fetches user data from the Untappd API and calculates a Beer
Explorer Score. The score favors variety and unusual choices more than raw
volume:

```text
season score = style diversity + category spread + weird beer tags + ABV range + country spread + season bonus + tiny badge/photo bonus
```

Examples of higher-value signals are sour/wild beers, lambic/gueuze, barrel
aging, smoked beers, barleywine, unusual ingredients, strong sippers, low-ABV
table beers, and drinking across multiple styles and countries.

The active season is selected automatically. Normal seasons are `Lentebokaal`,
`Zomercompetitie`, `Herfstproeverij`, and `Winterklassement`; short event
seasons include `Nieuwjaarsduik`, `Paasproeverij`, `Koningsdag Kraker`,
`Oktoberfest Sprint`, `Halloween Horror Pour`, and `Kerstkelder`. Each season
has its own bonus focus, so the leaderboard can reset its flavor throughout the
year.

Event seasons also award lifetime honor points. The current event leader is
stored in `eventHonors.json` and receives a persistent trophy bonus, so winning
Koningsdag, Pasen, Kerst, and similar events keeps counting after the event ends.

Each player also receives small achievement badges, such as `Style Nomad`,
`World Tour`, `Sour Survivor`, `Barrel Baron`, `Big Sipper`, `Crispy Diplomat`,
`Tiny Beer Hero`, and `Weird Flex`. Open a player's score in the table to see
the point breakdown.

Fetched results are cached in `savedUntappdData.json` for 30 minutes. If the API
is temporarily unavailable, the page falls back to the latest cache instead of
rendering an empty table.

## Files

- `APIData.example.php` shows the local config shape.
- `APIData.local.php` stores your private API credentials and is ignored by Git.
- `getData.php` fetches, normalizes, sorts, and caches Untappd data.
- `index.php` renders the leaderboard.
- `sortTable.js` adds client-side sorting.
- `style.css` contains the responsive design.
