// FocusManager — owns all cursor movement between cells.
//
// Cells are discovered live from the DOM (`[data-otp-cell]`) rather than
// captured once, so the manager keeps working if the row is re-rendered.
// Every move is clamped to a valid index and selects the cell's contents, so
// the next keystroke replaces rather than appends.
export class FocusManager {
    constructor(component) {
        this.component = component
    }

    inputs() {
        return Array.from(
            this.component.$root.querySelectorAll('[data-otp-cell]'),
        )
    }

    index(target) {
        const length = this.component.length
        const clamped = Math.max(0, Math.min(target, length - 1))
        const input = this.inputs()[clamped]

        if (!input) {
            return
        }

        input.focus()

        // Selecting the content makes typing replace the character and gives
        // a clear visual caret target.
        if (typeof input.select === 'function') {
            input.select()
        }
    }

    next(from) {
        this.index(from + 1)
    }

    previous(from) {
        this.index(from - 1)
    }

    // The first empty cell, or the last cell when the code is already full —
    // where focus should land when the field is focused as a whole.
    firstEmpty() {
        const emptyIndex = this.component.digits.findIndex(
            (digit) => (digit ?? '') === '',
        )

        this.index(emptyIndex === -1 ? this.component.length - 1 : emptyIndex)
    }
}
