#!/bin/bash
# Injects a planning reminder for substantial requests.
# Heuristic only - searches the raw JSON payload for strong signals.

input=$(cat)

# Strong signals of non-trivial / feature-level work
if echo "$input" | grep -iE '"message"[^}]*\b(implement|implémenter|new (page|feature|component|service|endpoint|module|hook)|nouvelle (page|fonctionnalité|composant|service)|add a new|ajouter une? nouvelle?|refactor|refactoriser|migration|migrate|architecture|redesign|integrate|intégrer|from scratch|de zéro)\b' > /dev/null 2>&1; then

    # Exclude: ends with a question mark (it's just a question)
    if echo "$input" | grep -iE '"message".*\?"' > /dev/null 2>&1; then
        exit 0
    fi

    # Exclude: explicit small fixes / renames
    if echo "$input" | grep -iE '"message"\s*:\s*"(fix|bug|typo|rename|corriger|renommer|changer (le|la|les) (texte|couleur|style|label|nom))' > /dev/null 2>&1; then
        exit 0
    fi

    printf '{"hookSpecificOutput":{"hookEventName":"UserPromptSubmit","additionalContext":"PLANNING REMINDER: This request looks substantial. Before implementing, ask the user: '\''This looks like a non-trivial change - do you want to go through /plan first to spec it out, or should I just go ahead?'\''"}}'
fi

exit 0
