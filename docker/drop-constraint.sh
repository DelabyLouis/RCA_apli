#!/bin/bash

# Script to drop numero_ordem constraints with detailed logging
set -e

echo "════════════════════════════════════════" >&2
echo "🔧 [drop-constraint.sh] CRITICAL: Dropping numero_ordre constraints" >&2
echo "════════════════════════════════════════" >&2

# BEFORE: List all constraints
echo "[drop-constraint.sh] BEFORE: All constraints on transaction table:" >&2
php bin/console dbal:run-sql "SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = 'transaction' ORDER BY constraint_name" 2>&1 | head -50 >&2 || echo "[drop-constraint.sh] ⚠️  Query failed" >&2

# BEFORE: Check for numero constraints specifically
echo "[drop-constraint.sh] Constraints matching 'numero%':" >&2
BEFORE=$(php bin/console dbal:run-sql "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'transaction' AND constraint_name LIKE '%numero%'" 2>&1 | grep -E "^[a-zA-Z_]" || echo "NONE")
echo "$BEFORE" >&2

# Try the Symfony command
echo "[drop-constraint.sh] Executing app:force-drop-constraint..." >&2
php bin/console app:force-drop-constraint 2>&1 | head -50 >&2 || echo "[drop-constraint.sh] ⚠️  Command failed with exit code $?" >&2

# Fallback: Try direct SQL drops with known names
echo "[drop-constraint.sh] Attempting direct SQL drops..." >&2
php bin/console dbal:run-sql "ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice" 2>&1 || echo "[drop-constraint.sh] ⚠️  First drop failed" >&2
php bin/console dbal:run-sql "ALTER TABLE transaction DROP CONSTRAINT IF EXISTS unique_numero_ordem_exercice" 2>&1 || echo "[drop-constraint.sh] ⚠️  Second drop failed" >&2

# AFTER: List remaining constraints
echo "[drop-constraint.sh] AFTER: All constraints on transaction table:" >&2
php bin/console dbal:run-sql "SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = 'transaction' ORDER BY constraint_name" 2>&1 | head -50 >&2 || echo "[drop-constraint.sh] ⚠️  Query failed" >&2

# AFTER: Check for numero constraints specifically
echo "[drop-constraint.sh] Remaining constraints matching 'numero%':" >&2
AFTER=$(php bin/console dbal:run-sql "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'transaction' AND constraint_name LIKE '%numero%'" 2>&1 | grep -E "^[a-zA-Z_]" || echo "NONE")
echo "$AFTER" >&2

if [[ "$AFTER" == "NONE" ]]; then
    echo "✅ SUCCESS: All numero_ordem constraints removed!" >&2
    echo "════════════════════════════════════════" >&2
    exit 0
else
    echo "❌ FAILED: Constraints still exist: $AFTER" >&2
    echo "════════════════════════════════════════" >&2
    exit 1
fi
