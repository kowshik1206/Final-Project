## Summary
This PR introduces the RecommendService which ranks travel modes (car, ev, train, flight) for a given route using deterministic scoring based on cost, time, and convenience. It also adds the API endpoint `POST /api/recommend`, request validation, unit and feature tests, and OpenAPI snippets.

## What was added
- `app/Services/RecommendService.php` — Scoring logic (cost/time/convenience), passenger adjustments, deterministic outputs.
- `app/Http/Requests/RecommendRequest.php` — Request validation (route, distance_meters, mode_preferences).
- `app/Http/Controllers/Api/RecommendController.php` — Endpoint handler and error mapping.
- `routes/api.php` — `POST /api/recommend` (throttle: 30/min).
- Tests:
  - `tests/Unit/RecommendServiceTest.php`
  - `tests/Feature/RecommendEndpointTest.php`
- OpenAPI snippet for `/api/recommend` (to be merged into `backend/openapi.yaml`).

## Why
- Unlocks frontend "cheapest/fastest/balanced" UX.
- Reuses existing CostService (cached) so performance is acceptable.
- Deterministic and tested — ready for staging validation.

## Required CI checks (must pass before merge)
1. `composer install` completes
2. `php artisan migrate` and `php artisan db:seed --class=CostConfigSeeder` succeed
3. Unit tests — `RecommendServiceTest` pass
4. Feature tests — `RecommendEndpointTest` pass
5. Smoke test: `POST /api/recommend` returns `status: success` and non-empty `data.ranking`
6. OpenAPI linter passes (if present in pipeline)
7. Confirm throttle behavior in CI (redis cache enabled for rate limiting)

## Manual staging / smoke steps (for reviewer)
- Migrate & seed:
```bash
php artisan migrate
php artisan db:seed --class=CostConfigSeeder
```

- Smoke:
```bash
curl -s -X POST https://staging.example.com/api/recommend \
-H "Content-Type: application/json" \
-d '{"route":{"distance_meters":100000,"duration_seconds":7200},"mode_preferences":["car","ev","train","flight"],"passengers":2}' | jq .
```
Expect: `{"status":"success","data":{"best_mode":...,"ranking":[...]}}`

## Rollback
If anything goes wrong in staging or prod, revert the merge commit:
```bash
git revert <merge-commit> --no-edit
git push origin main
```

## Notes
- This PR returns inline `cost_snapshot` for each mode. We will add persistent `cost_snapshots` table in a follow-up to avoid duplicating snapshots across trips.
- Feature flag support is included (recommended) — see snippet below.
