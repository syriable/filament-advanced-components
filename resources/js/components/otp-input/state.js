// OtpStateManager — the single source of truth for "what is a valid
// character" and how a code string maps to and from the per-cell array.
//
// It is built once from the mode's regex character-class body (e.g. `0-9`,
// `A-Za-z0-9`) so the client can never disagree with the server about which
// characters are allowed. Every method is pure, so it is trivially testable
// and holds no reference to the Alpine component.
export function createStateManager(characterClass) {
    // A global matcher to strip everything invalid (separators, whitespace,
    // out-of-mode characters) from a pasted or autofilled blob, and a single
    // matcher to test one character. `u` keeps multi-byte input well-behaved.
    const invalidMatcher = new RegExp(`[^${characterClass}]`, 'gu')
    const singleMatcher = new RegExp(`^[${characterClass}]$`, 'u')

    return {
        // Strip every character that isn't valid for this mode. Whitespace
        // and separators fall away here, satisfying "trim automatically" and
        // "ignore invalid characters" in one place.
        sanitize(input) {
            return String(input ?? '').replace(invalidMatcher, '')
        },

        accepts(character) {
            return singleMatcher.test(character)
        },

        // Turn a code string into a fixed-length array of single characters,
        // padding with '' and never overflowing the length.
        split(value, length) {
            const clean = this.sanitize(value).slice(0, length)
            const digits = []

            for (let i = 0; i < length; i++) {
                digits.push(clean[i] ?? '')
            }

            return digits
        },

        // Collapse the cells back into the stored value. Empty cells simply
        // contribute nothing, so a trailing (or transient middle) gap yields
        // a shorter value rather than embedded spaces.
        join(digits) {
            return digits.map((digit) => digit ?? '').join('')
        },
    }
}
