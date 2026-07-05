// ClipboardManager — turns a paste into a distributed fill.
//
// The pasted text is handed straight to the component's `distribute()`, which
// sanitizes it (dropping separators, whitespace, and invalid characters),
// writes from the pasted-into cell onward, and stops at the last cell —
// giving partial, complete, replacement, and overflow-protected pastes for
// free.
export class ClipboardManager {
    constructor(component) {
        this.component = component
    }

    handle(event, index) {
        const component = this.component

        // We always take over: the default paste would dump the whole string
        // into a single maxlength=1 cell.
        event.preventDefault()

        if (component.isDisabled || component.isReadOnly) {
            return
        }

        const clipboard = event.clipboardData || window.clipboardData
        const text = clipboard ? clipboard.getData('text') : ''

        component.distribute(text, index)
    }
}
