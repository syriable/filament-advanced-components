import { createStateManager } from './otp-input/state'
import { FocusManager } from './otp-input/focus'
import { KeyboardManager } from './otp-input/keyboard'
import { ClipboardManager } from './otp-input/clipboard'

// The Alpine component behind the OtpInput field.
//
// `state` is the Livewire-entangled string — the single source of truth that
// the form reads and validates. Everything else is local: the `digits` array
// is the per-cell working copy, and all typing, navigation, and pasting
// mutate it in memory, syncing back to `state` only through `commit()`. With
// the default deferred binding that means zero Livewire round-trips while the
// user types; a `->live()` field simply syncs the joined value as it changes.
//
// Responsibilities are delegated to small managers (state, focus, keyboard,
// clipboard) so this file stays a thin orchestrator.
export default function otpInput(config) {
    return {
        length: config.length,
        characterClass: config.characterClass,
        isPrivate: config.isPrivate,
        isDisabled: config.isDisabled,
        isReadOnly: config.isReadOnly,
        hasAutocomplete: config.hasAutocomplete,
        shouldAutoSubmit: config.shouldAutoSubmit,
        autoSubmitAction: config.autoSubmitAction,
        maskCharacter: config.maskCharacter,

        state: config.state,

        digits: [],

        manager: null,
        focus: null,
        keyboard: null,
        clipboard: null,

        init() {
            this.manager = createStateManager(this.characterClass)
            this.focus = new FocusManager(this)
            this.keyboard = new KeyboardManager(this)
            this.clipboard = new ClipboardManager(this)

            this.digits = this.manager.split(this.state ?? '', this.length)
            this.syncInputs()

            // React to state set from outside the component — server
            // hydration, a form reset, an afterStateUpdated callback — but
            // not to our own commits, so local typing (including transient
            // gaps) is never clobbered mid-edit.
            this.$watch('state', (value) => {
                if (this.manager.sanitize(value) !== this.value) {
                    this.digits = this.manager.split(value ?? '', this.length)
                    this.syncInputs()
                }
            })
        },

        // The stored value: the cells joined, separator-free.
        get value() {
            return this.manager.join(this.digits)
        },

        get isComplete() {
            return this.value.length === this.length
        },

        // What a given cell should show: nothing when empty, the mask glyph
        // in private mode, otherwise the real character.
        displayValue(index) {
            const digit = this.digits[index] ?? ''

            if (digit === '') {
                return ''
            }

            return this.isPrivate ? this.maskCharacter : digit
        },

        setDigit(index, character) {
            this.digits[index] = character
        },

        // Push the working copy back onto the entangled state, and fire
        // auto-submit once the code is complete.
        commit() {
            const next = this.value

            if (this.state !== next) {
                this.state = next
            }

            if (this.shouldAutoSubmit && this.isComplete) {
                this.autoSubmit()
            }
        },

        onInput(event, index) {
            if (this.isDisabled || this.isReadOnly) {
                event.target.value = this.displayValue(index)

                return
            }

            const characters = this.manager.sanitize(event.target.value)

            // Rejected keystroke (invalid character): restore the cell.
            if (characters.length === 0) {
                this.setDigit(index, '')
                event.target.value = ''
                this.commit()

                return
            }

            // A single valid character: place it and advance.
            if (characters.length === 1) {
                this.setDigit(index, characters)
                event.target.value = this.displayValue(index)
                this.commit()
                this.focus.next(index)

                return
            }

            // Multiple characters in one event — an autofilled one-time-code
            // or a paste that bypassed the paste handler: distribute them.
            this.distribute(characters, index)
        },

        // Write a run of characters across the cells from `start`, stopping at
        // the last cell (overflow protection), then move focus to the first
        // still-empty cell.
        distribute(input, start = 0) {
            const characters = this.manager.sanitize(input)

            if (characters.length === 0) {
                return
            }

            let cursor = start

            for (const character of characters) {
                if (cursor >= this.length) {
                    break
                }

                this.setDigit(cursor, character)
                cursor += 1
            }

            this.syncInputs()
            this.commit()
            this.focus.index(Math.min(cursor, this.length - 1))
        },

        onKeydown(event, index) {
            this.keyboard.handle(event, index)
        },

        onPaste(event, index) {
            this.clipboard.handle(event, index)
        },

        onFocus(event) {
            // Select the cell so the next keystroke replaces its content.
            event.target.select()
        },

        // Mirror the working copy into the actual inputs. Used after any bulk
        // change (paste, distribute, delete) and on external state updates;
        // single-character typing writes its own cell inline.
        syncInputs() {
            this.focus.inputs().forEach((input, index) => {
                input.value = this.displayValue(index)
            })
        },

        // Public API: empty the field and focus the first cell.
        clear() {
            this.digits = Array.from({ length: this.length }, () => '')
            this.syncInputs()
            this.commit()
            this.focus.index(0)
        },

        // Fire a cancelable `otp-completed` event carrying the value; if it
        // isn't canceled and a Livewire action name was configured, call it.
        autoSubmit() {
            const value = this.value

            const event = new CustomEvent('otp-completed', {
                detail: { value },
                bubbles: true,
                cancelable: true,
            })

            const notCanceled = this.$root.dispatchEvent(event)

            if (notCanceled && this.autoSubmitAction && this.$wire) {
                this.$wire.call(this.autoSubmitAction, value)
            }
        },
    }
}
