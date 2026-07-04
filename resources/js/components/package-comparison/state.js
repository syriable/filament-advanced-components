// Pure, dependency-free helpers for the package-comparison Alpine component.
// Kept apart from the component so they stay trivially unit-testable and the
// component file reads as behavior only.

export function uuid() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID()
    }

    // Insecure-context fallback (e.g. plain-HTTP local dev).
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0
        const value = char === 'x' ? random : (random & 0x3) | 0x8

        return value.toString(16)
    })
}

export function clone(value) {
    if (value === null || typeof value !== 'object') {
        return value
    }

    return typeof structuredClone === 'function'
        ? structuredClone(value)
        : JSON.parse(JSON.stringify(value))
}

// Reorders `items` to match `orderedIds` (SortableJS's `toArray()` output),
// defensively appending any item whose id the DOM did not report.
export function reorderById(items, orderedIds) {
    const byId = new Map(items.map((item) => [item.id, item]))

    const reordered = orderedIds
        .map((id) => byId.get(id))
        .filter((item) => item !== undefined)

    items.forEach((item) => {
        if (!orderedIds.includes(item.id)) {
            reordered.push(item)
        }
    })

    return reordered
}

// 0 → "A", 25 → "Z", 26 → "AA" … mirrors StateNormalizer::defaultPackageTitle().
export function packageLetters(index) {
    let letters = ''

    do {
        letters = String.fromCharCode(65 + (index % 26)) + letters
        index = Math.floor(index / 26) - 1
    } while (index >= 0)

    return letters
}
