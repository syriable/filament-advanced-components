// KeyboardManager — non-printing key behavior (printing keys flow through the
// `input` event on the component instead).
//
// Backspace clears the current cell or, when it is already empty, steps back
// and clears the previous one; Delete clears in place; the arrows, Home, and
// End move without editing. All of these are no-ops while disabled or
// read-only (except pure navigation, which stays available for reading).
export class KeyboardManager {
    constructor(component) {
        this.component = component
    }

    handle(event, index) {
        const component = this.component

        if (component.isDisabled) {
            return
        }

        switch (event.key) {
            case 'Backspace':
                event.preventDefault()

                if (component.isReadOnly) {
                    return
                }

                if ((component.digits[index] ?? '') !== '') {
                    component.setDigit(index, '')
                    component.syncInputs()
                    component.commit()
                } else if (index > 0) {
                    component.setDigit(index - 1, '')
                    component.syncInputs()
                    component.commit()
                    component.focus.index(index - 1)
                }

                break

            case 'Delete':
                event.preventDefault()

                if (component.isReadOnly) {
                    return
                }

                component.setDigit(index, '')
                component.syncInputs()
                component.commit()

                break

            case 'ArrowLeft':
                event.preventDefault()
                component.focus.previous(index)

                break

            case 'ArrowRight':
                event.preventDefault()
                component.focus.next(index)

                break

            case 'Home':
                event.preventDefault()
                component.focus.index(0)

                break

            case 'End':
                event.preventDefault()
                component.focus.index(component.length - 1)

                break

            default:
                break
        }
    }
}
