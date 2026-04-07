#!/bin/sh

# Test script to verify APP_KEY generation logic
# This simulates what happens in entrypoint.sh

echo "=== Testing APP_KEY Generation Logic ==="
echo ""

# Create a test .env file without APP_KEY
echo "Creating test .env file without APP_KEY line..."
cat > /tmp/test.env << EOF
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
EOF

echo "Test .env contents:"
cat /tmp/test.env
echo ""

# Test 1: Check if APP_KEY line exists
echo "Test 1: Checking if APP_KEY line exists..."
if ! grep -q "^APP_KEY=" /tmp/test.env; then
    echo "✓ APP_KEY line not found (as expected)"
    echo "  Adding APP_KEY= line..."
    echo "APP_KEY=" >> /tmp/test.env
else
    echo "✗ APP_KEY line found (unexpected)"
fi
echo ""

echo "Updated .env contents:"
cat /tmp/test.env
echo ""

# Test 2: Check if APP_KEY is empty or invalid
echo "Test 2: Checking if APP_KEY needs generation..."
if ! grep -q "^APP_KEY=base64:" /tmp/test.env || grep -q "^APP_KEY=$" /tmp/test.env; then
    echo "✓ APP_KEY is empty or invalid (as expected)"
    echo "  Would run: php artisan key:generate --force"
else
    echo "✗ APP_KEY appears valid (unexpected)"
fi
echo ""

# Test 3: Simulate with placeholder
echo "Test 3: Testing with placeholder value..."
cat > /tmp/test2.env << EOF
APP_NAME=Laravel
APP_KEY=base64:PLACEHOLDER_WILL_BE_GENERATED_BY_ARTISAN
EOF

if grep -q "^APP_KEY=base64:PLACEHOLDER" /tmp/test2.env; then
    echo "✓ Placeholder detected correctly"
    echo "  Would run: php artisan key:generate --force"
else
    echo "✗ Placeholder not detected"
fi
echo ""

# Test 4: Simulate with valid key
echo "Test 4: Testing with valid APP_KEY..."
cat > /tmp/test3.env << EOF
APP_NAME=Laravel
APP_KEY=base64:abcdefghijklmnopqrstuvwxyz1234567890ABCD==
EOF

if grep -q "^APP_KEY=base64:" /tmp/test3.env && ! grep -q "^APP_KEY=$" /tmp/test3.env && ! grep -q "^APP_KEY=base64:PLACEHOLDER" /tmp/test3.env; then
    echo "✓ Valid APP_KEY detected correctly"
    echo "  Would skip generation"
else
    echo "✗ Valid APP_KEY not recognized"
fi
echo ""

# Cleanup
rm -f /tmp/test.env /tmp/test2.env /tmp/test3.env

echo "=== All Tests Complete ==="
