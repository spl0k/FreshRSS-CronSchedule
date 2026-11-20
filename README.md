# FreshRSS Cron Schedule

Schedules each FreshRSS feed's refresh with a **cron expression**, directly from the interface.

## Installation

1. Clone this repository into `FreshRSS/extensions/` (with Docker: into the volume mounted on `/var/www/FreshRSS/extensions`).
2. In FreshRSS: Configuration → Extensions → enable “Cron Schedule”.
3. Click the extension's gear icon: enter an expression per feed, then submit.

## How it works

The FreshRSS cron (`actualize_script.php`, `CRON_MIN`…) keeps running at a regular interval (e.g. every 10 min). For each scheduled feed, the extension lets the refresh through only if an occurrence of its expression has passed since its last refresh; otherwise the feed is skipped (no network request).

- A missed occurrence (server down) is caught up only once, on the next run.
- Retry window: `*/10 7 * * 1-5` allows one refresh per occurrence between 7:00 and 7:50.
- Feeds without an expression keep the normal behaviour (default TTL, AutoTTL…).
- Manual refreshes (a feed's button, `force=1`) are never blocked.
- Time zone: that of PHP / FreshRSS (`TZ` variable with Docker).

## Required feed setting

FreshRSS only hands the extension feeds whose minimum interval between two refreshes has elapsed. For scheduled feeds, set this interval to the **shortest value** (Manage the feed subscription → “Do not automatically refresh more often than”), otherwise the occurrence will be delayed by as much. This also excludes these feeds from AutoTTL.

## Supported syntax

`minute hour day-of-month month day-of-week` with `*`, lists (`1,5`), ranges (`1-5`), steps (`*/10`, `1-30/5`), names (`jan`, `mon-fri`) and macros (`@hourly`, `@daily`, `@weekly`, `@monthly`, `@yearly`). If both day-of-month and day-of-week are restricted, they are combined with OR, as in classic cron.

## Honnest disclaimer

I have no experience with the FreshRSS extension API, and actually haven't done any PHP for more than 15 years. This extension have been fully written by Claude (Anthropic). The only things coming from a human are the design decisions and this paragraph.
