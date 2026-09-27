# WHO Child Growth Standards — LMS tables

Reference data used by `App\Services\WhoGrowthStandards` to compute z-scores for
children aged 0–60 months.

| File | Indicator | Indexed by |
|------|-----------|------------|
| `weianthro.txt` | Weight-for-age | age in days (0–1826) |
| `lenanthro.txt` | Length/height-for-age | age in days (0–1826) |
| `wflanthro.txt` | Weight-for-length (under 24 months) | length in cm (45–110) |
| `wfhanthro.txt` | Weight-for-height (24 months and over) | height in cm (65–120) |

Columns: `sex` (1 = male, 2 = female), index, `l`, `m`, `s` (Box-Cox power,
median, coefficient of variation).

**Source:** WHO Multicentre Growth Reference Study Group. *WHO Child Growth
Standards* (2006). Files copied unchanged from WHO's official `anthro` package,
`data-raw/growthstandards/` —
https://github.com/WorldHealthOrganization/anthro (GPL-3.0).
