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

`getData.php` fetches user data from the Untappd API, calculates a score with:

```text
(total photos + total badges) / 2
```

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
