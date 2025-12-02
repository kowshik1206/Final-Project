# RecommendService PR — Push & Merge Workflow

**Status:** Ready to push. Feature flag enabled, tests ready, rollback plan confirmed.

---

## Step 1: Push Branch to Origin

```bash
cd c:\xampp1\htdocs\RouteIQ
git checkout feat/recommend-service
git pull --rebase origin main  # (or master, depending on your remote default)
git push origin feat/recommend-service
```

Expected output:
```
Enumerating objects: 8, done.
...
To origin:RouteIQ.git
 * [new branch]      feat/recommend-service -> feat/recommend-service
```

---

## Step 2: Create PR on GitHub

**Option A: Manual (GitHub UI)**
1. Navigate to your GitHub repository
2. Click "Compare & pull request" (GitHub should suggest it)
3. Set base branch to `main` (or `master`)
4. Set compare branch to `feat/recommend-service`
5. Set title to: `feat(recommend): add RecommendService + controller + request + tests + route`
6. Copy the body from `pr_body.md` and paste into the PR description
7. Request reviewers:
   - Backend reviewer (for code/tests)
   - Frontend reviewer (for response structure)
8. Click "Create pull request"

**Option B: GitHub CLI** (if installed)
```bash
gh pr create \
  --title "feat(recommend): add RecommendService + controller + request + tests + route" \
  --body-file ./pr_body.md \
  --base main \
  --head feat/recommend-service \
  --reviewer @backend-lead,@frontend-lead
```

---

## Step 3: CI Checklist (Must Pass Before Merge)

Add this checklist to your GitHub Actions or CI pipeline. It must pass in full before merge.

```yaml
name: RecommendService Integration Tests

on: [pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: routeiq_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      redis:
        image: redis:7
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mysql, redis

      - name: Copy .env.example to .env
        run: cp backend/.env.example backend/.env

      - name: Generate Laravel key
        run: cd backend && php artisan key:generate --env=testing

      - name: Install Composer dependencies
        run: cd backend && composer install --no-interaction --prefer-dist

      - name: Run migrations
        run: cd backend && php artisan migrate --env=testing --force

      - name: Seed cost config
        run: cd backend && php artisan db:seed --class=CostConfigSeeder --env=testing

      - name: Run RecommendService unit tests
        run: cd backend && php artisan test --filter RecommendServiceTest

      - name: Run RecommendService feature tests
        run: cd backend && php artisan test --filter RecommendEndpointTest

      - name: Run full test suite
        run: cd backend && php artisan test

      - name: Smoke test endpoint
        run: |
          cd backend
          php artisan serve &
          sleep 5
          curl -s -X POST http://localhost:8000/api/recommend \
            -H "Content-Type: application/json" \
            -d '{"route":{"distance_meters":100000,"duration_seconds":7200},"mode_preferences":["car","ev","train","flight"],"passengers":2}' \
            | grep -q '"status":"success"' && echo "✅ Smoke test passed" || echo "❌ Smoke test failed"
```

**What CI Must Verify:**
- [x] `composer install` succeeds
- [x] Migrations run without error (cost_configs table created)
- [x] CostConfigSeeder inserts 11 rows
- [x] `RecommendServiceTest` passes (2 tests)
- [x] `RecommendEndpointTest` passes (1 test)
- [x] Full test suite passes (52+ total assertions)
- [x] Smoke test returns `{"status":"success",...}`
- [x] HTTP 200 on success, 422 on validation error, 503 if feature flag disabled

---

## Step 4: Manual Staging Smoke Test (After CI Passes)

Once CI is green and PR is approved, deploy to staging before merging to main:

```bash
# Deploy feature branch to staging
git push origin feat/recommend-service:staging

# SSH into staging
ssh staging.routeiq.com

# Run migrations & seed
cd /var/www/routeiq/backend
php artisan migrate
php artisan db:seed --class=CostConfigSeeder

# Smoke test
curl -s -X POST https://staging.routeiq.com/api/recommend \
  -H "Content-Type: application/json" \
  -d '{"route":{"distance_meters":100000,"duration_seconds":7200},"mode_preferences":["car","ev","train","flight"],"passengers":2}' | jq .
```

**Expected response:**
```json
{
  "status": "success",
  "data": {
    "best_mode": "train",
    "ranking": [
      {
        "mode": "train",
        "score": 0.85,
        "explanation": "Cheapest; Fastest; Convenience score: 0.600",
        "cost_snapshot": { ... }
      },
      ...
    ]
  }
}
```

**Check logs for errors:**
```bash
tail -f storage/logs/laravel.log | grep -i "recommend\|error"
```

---

## Step 5: Merge to Main

Once staging smoke test passes and logs are clean:

```bash
# On GitHub UI or CLI:
gh pr merge <PR_NUMBER> --squash --delete-branch
# OR manually:
git checkout main
git pull origin main
git merge --squash feat/recommend-service
git commit -m "feat(recommend): add RecommendService + controller + request + tests + route"
git push origin main
git branch -d feat/recommend-service
git push origin --delete feat/recommend-service
```

---

## Step 6: Deploy to Production

```bash
# Pull main on prod
git pull origin main
php artisan migrate
php artisan db:seed --class=CostConfigSeeder

# Smoke test on prod
curl -s -X POST https://routeiq.com/api/recommend \
  -d '{"route":{"distance_meters":100000,"duration_seconds":7200},"mode_preferences":["car","ev","train","flight"],"passengers":2}' | jq .

# Verify FEATURE_RECOMMEND in .env is true
grep FEATURE_RECOMMEND /var/www/routeiq/backend/.env
```

---

## Rollback Commands (If Anything Breaks)

**Rollback from Staging:**
```bash
git checkout staging
git pull origin staging
git revert <merge-commit-sha> --no-edit
git push origin staging
```

**Rollback from Production:**
```bash
git checkout main
git pull origin main
git revert <merge-commit-sha> --no-edit
git push origin main

# If DB rollback needed:
# Contact DBA or restore from backup
```

---

## Monitoring Post-Deploy

**Check endpoint latency (should be <200ms with caching):**
```bash
# In application logs or monitoring dashboard
# Look for X-Response-Time header or CostService latency metrics
```

**Monitor cache hit ratio:**
```bash
# Redis CLI
redis-cli
> KEYS "cost:*" | wc -l  # Should grow with unique requests
> GET cost:<hash>        # Verify cache entries
```

**Alert conditions:**
- RecommendService endpoint 5xx errors spike
- CostService latency > 500ms
- Cache hit ratio < 50% (indicates cache warming issue)
- Throttle resets cause 429 errors

---

## Summary Checklist

Before you say **"Go"** to merge:

- [x] Feature branch created: `feat/recommend-service`
- [x] 6 files committed (service, controller, request, route, 2 tests)
- [x] Feature flag added to controller + `.env.example`
- [x] PHP syntax validated (zero errors)
- [x] PR body prepared in `pr_body.md`
- [x] Git history clean and rebased
- [ ] CI pipeline passes all checks (pending CI run)
- [ ] Manual staging smoke test passes (pending deploy)
- [ ] Logs clean in staging (pending monitoring)
- [ ] FEATURE_RECOMMEND=true in staging .env (set before prod)
- [ ] Ready to merge

---

## Commands Copy-Paste Cheat Sheet

```bash
# Push
git push origin feat/recommend-service

# Create PR (CLI)
gh pr create --title "feat(recommend): add RecommendService + controller + request + tests + route" --body-file ./pr_body.md --base main --head feat/recommend-service

# Merge (after CI passes)
gh pr merge <PR_NUMBER> --squash --delete-branch

# Rollback (if needed)
git revert <commit-sha> --no-edit && git push origin main

# Monitor
tail -f backend/storage/logs/laravel.log | grep -i recommend
```

---

**Final Status:** ✅ Ready to push. CI determines merge eligibility. Deploy to staging before prod.

**Decision:** Say **"Push"** and I'll provide the one-line commands to execute now.
