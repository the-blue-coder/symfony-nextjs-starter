#!/bin/bash
# Enforces the spec-driven pipeline:
# - No writes to frontend/ or backend/ if a spec is in-progress with unchecked criteria.

input=$(cat)

# Extract fields from JSON using grep/sed (portable, no jq/python3 required)
file_path=$(echo "$input" | grep -o '"file_path":"[^"]*"' | head -1 | sed 's/"file_path":"//;s/"//')
cwd=$(echo "$input" | grep -o '"cwd":"[^"]*"' | head -1 | sed 's/"cwd":"//;s/"//')

# Only enforce on frontend/ or backend/ files
if [[ "$file_path" != *"/frontend/"* && "$file_path" != *"/backend/"* ]]; then
    exit 0
fi

specs_dir="$cwd/.context/feature-specs"

if [[ ! -d "$specs_dir" ]]; then
    exit 0
fi

# Find specs that are in-progress AND have unchecked criteria
blocking=()

for spec in "$specs_dir"/*.md; do
    [[ -f "$spec" ]] || continue
    if grep -q "^status: in-progress" "$spec" && grep -q "^- \[ \]" "$spec"; then
        blocking+=("$(basename "$spec")")
    fi
done

if [[ ${#blocking[@]} -gt 0 ]]; then
    echo "" >&2
    echo "Pipeline gate: run /verify before writing feature code." >&2
    echo "" >&2
    echo "Specs in-progress with unchecked criteria:" >&2
    for s in "${blocking[@]}"; do
        echo "  · $s" >&2
    done
    echo "" >&2
    echo "Run /verify on the spec above, then come back." >&2
    exit 2
fi

exit 0
