#!/bin/bash
# run_tests.sh - Run all smoke tests and show results

echo "🧪 RouteIQ Test Suite"
echo "===================="
echo ""

# Quick mode tests (essentials only)
echo "Running QUICK smoke tests..."
php backend/public/smoke_test.php --mode=quick
QUICK_EXIT=$?

if [ $QUICK_EXIT -eq 0 ]; then
    echo ""
    echo "✅ Quick tests PASSED! Proceeding to full tests..."
    echo ""
    
    # Full mode tests (all endpoints)
    echo "Running FULL smoke tests..."
    php backend/public/smoke_test.php --mode=full
    FULL_EXIT=$?
    
    if [ $FULL_EXIT -eq 0 ]; then
        echo ""
        echo "🎉 ALL TESTS PASSED!"
        echo ""
        echo "📁 Artifacts:"
        echo "   - Uploaded files: uploads/test/"
        echo "   - Database: database.sqlite"
        echo "   - Logs: Check browser console for frontend errors"
        exit 0
    else
        echo ""
        echo "❌ Full tests failed. See above for details."
        exit 1
    fi
else
    echo ""
    echo "❌ Quick tests failed. Fix issues before running full suite."
    exit 1
fi
