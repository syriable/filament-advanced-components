// Digit and formatting helpers for the PhoneInput client.
//
// The server (libphonenumber) is the authority for the stored and validated
// value; everything here is presentational — turning the digits a user types
// into a pleasant, country-appropriate national display without a round-trip.

// Strip everything that is not a digit.
export function digitsOnly(value) {
    return (value ?? '').replace(/\D/g, '')
}

// Build a formatting template from a national example number: each digit
// becomes a fillable slot, every other character is a literal separator.
// e.g. "(415) 555-2671" -> ["(", 0, 0, 0, ") ", 0, 0, 0, "-", 0, 0, 0, 0]
// (represented as an array where numbers are slots and strings are literals).
export function templateFromExample(example) {
    if (! example) {
        return null
    }

    const template = []

    for (const character of example) {
        template.push(/\d/.test(character) ? 0 : character)
    }

    return template
}

// Apply an example-derived template to a run of digits. Digits fill the slots
// in order; separators are emitted only once a following digit exists, so a
// half-typed number never shows a dangling bracket. Any digits beyond the
// template's slot count are appended verbatim, so longer-than-example numbers
// are never truncated.
export function applyTemplate(digits, template) {
    if (! template || digits.length === 0) {
        return digits
    }

    let output = ''
    let cursor = 0
    let pendingLiterals = ''

    for (const token of template) {
        if (cursor >= digits.length) {
            break
        }

        if (typeof token === 'number') {
            output += pendingLiterals + digits[cursor]
            pendingLiterals = ''
            cursor += 1
        } else {
            pendingLiterals += token
        }
    }

    // Overflow: more digits than the example describes — keep them raw.
    if (cursor < digits.length) {
        output += ' ' + digits.slice(cursor)
    }

    return output
}

// The full national display for a run of digits given an example number.
export function formatNational(digits, example) {
    return applyTemplate(digitsOnly(digits), templateFromExample(example))
}
