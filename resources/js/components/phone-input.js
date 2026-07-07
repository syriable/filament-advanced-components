import { CountryIndex, splitTransport } from './phone-input/countries'
import { digitsOnly, formatNational } from './phone-input/formatter'

// The Alpine component behind the PhoneInput field.
//
// `state` is the Livewire-entangled transport string — a self-describing E.164
// value (`+<code><digits>`, with an optional `;ext=`). It is the single value
// the form reads, validates, and stores; PHP (libphonenumber) is the authority
// for everything about it. This component only makes editing pleasant: a
// searchable country selector, national-format masking as you type, and paste
// intelligence. All local working state (`country`, `national`, `extension`)
// collapses back into the transport string through `commit()`.
//
// With the default deferred binding there are zero Livewire round-trips while
// typing; a `->live()` field simply syncs the composed value as it changes.
export default function phoneInput(config) {
    return {
        // --- configuration (static) -----------------------------------------
        countries: config.countries,
        examples: config.examples ?? {},
        hasCountrySelector: config.hasCountrySelector,
        showFlags: config.showFlags,
        showDialCode: config.showDialCode,
        searchEnabled: config.searchEnabled,
        searchDebounce: config.searchDebounce,
        hasExtensionField: config.hasExtension,
        maxExtensionLength: config.maxExtensionLength,
        placeholderFromCountry: config.placeholderFromCountry,
        explicitPlaceholder: config.placeholder ?? null,
        isDisabled: config.isDisabled,
        isReadOnly: config.isReadOnly,

        // --- entangled + local working state --------------------------------
        state: config.state,
        country: null,
        dialCode: 0,
        national: '',
        extension: '',

        // --- selector UI state ----------------------------------------------
        open: false,
        search: '',
        activeIndex: -1,

        // --- internals ------------------------------------------------------
        index: null,
        lastComposed: '',

        init() {
            this.index = new CountryIndex(this.countries)

            this.country = config.initialCountry ?? (this.countries[0]?.iso ?? null)
            this.dialCode = this.index.get(this.country)?.dialCode ?? 0

            // Prefer the server's national rendering (correct trunk digits) for
            // the first paint; fall back to deriving from the transport value.
            if (config.initialNational != null && config.initialNational !== '') {
                this.national = config.initialNational
            } else if (this.state) {
                this.hydrateFrom(this.state)
            }

            this.extension = config.initialExtension ?? ''

            // Anchor the change-detection guard to the server's actual state,
            // not our recomposition of it: the two can differ harmlessly (trunk
            // digits, a read-only display format), and we must never treat that
            // as an external change on the first tick.
            this.lastComposed = this.state ?? ''

            // React only to *external* state changes (a reset, an
            // afterStateUpdated, server hydration) — never to our own commits,
            // so live typing is never clobbered mid-edit.
            this.$watch('state', (value) => {
                if (value !== this.lastComposed) {
                    this.hydrateFrom(value ?? '')
                    this.lastComposed = this.compose()
                }
            })
        },

        // --- derived --------------------------------------------------------
        get selectedCountry() {
            return this.index?.get(this.country) ?? null
        },

        get example() {
            return this.examples[this.country] ?? null
        },

        get placeholder() {
            if (this.explicitPlaceholder !== null) {
                return this.explicitPlaceholder
            }

            return this.placeholderFromCountry ? (this.example ?? '') : ''
        },

        get filteredCountries() {
            return this.index ? this.index.search(this.search) : this.countries
        },

        // The transport value: empty when no national digits were entered, so
        // an optional field round-trips to null; otherwise `+<code><digits>`
        // with the extension as an RFC 3966 suffix.
        compose() {
            const digits = digitsOnly(this.national)

            if (digits === '') {
                return ''
            }

            let transport = '+' + this.dialCode + digits

            if (this.hasExtensionField && digitsOnly(this.extension) !== '') {
                transport += ';ext=' + digitsOnly(this.extension)
            }

            return transport
        },

        // Push the working copy back onto the entangled state and announce it.
        commit() {
            const next = this.compose()
            this.lastComposed = next

            if (this.state !== next) {
                this.state = next
                this.dispatch('phone-changed', { value: next, country: this.country })
            }
        },

        format(digits) {
            return formatNational(digits, this.example)
        },

        // Rebuild the working state from a transport string (external updates
        // and pasted international numbers).
        hydrateFrom(transport) {
            const parts = splitTransport(transport, this.index, this.country)

            if (parts.country) {
                this.country = parts.country.iso
                this.dialCode = parts.country.dialCode
            }

            this.national = this.format(parts.national)

            if (this.hasExtensionField) {
                this.extension = parts.extension
            }
        },

        // --- national input -------------------------------------------------
        onInput(event) {
            if (this.isDisabled || this.isReadOnly) {
                event.target.value = this.national

                return
            }

            const raw = event.target.value

            // A leading "+" means the user is typing or pasting an
            // international number: let the transport splitter detect country.
            if (raw.trim().startsWith('+')) {
                this.hydrateFrom(raw.trim())
                event.target.value = this.national
                this.commit()

                return
            }

            this.national = this.format(digitsOnly(raw))
            event.target.value = this.national
            this.commit()
        },

        onExtensionInput(event) {
            this.extension = digitsOnly(event.target.value).slice(0, this.maxExtensionLength)
            event.target.value = this.extension
            this.commit()
        },

        onPaste(event) {
            if (this.isDisabled || this.isReadOnly) {
                return
            }

            const text = (event.clipboardData || window.clipboardData)?.getData('text') ?? ''

            if (! text) {
                return
            }

            event.preventDefault()

            const trimmed = text.trim()

            if (trimmed.startsWith('+') || trimmed.startsWith('00')) {
                // A full international number: normalise "00" to "+" and detect.
                this.hydrateFrom(trimmed.replace(/^00/, '+'))
            } else {
                this.national = this.format(digitsOnly(trimmed))
            }

            this.commit()
        },

        // --- country selector ----------------------------------------------
        toggle() {
            if (this.isDisabled || this.isReadOnly || ! this.hasCountrySelector) {
                return
            }

            this.open ? this.close() : this.openDropdown()
        },

        openDropdown() {
            this.open = true
            this.search = ''
            this.activeIndex = this.filteredCountries.findIndex((c) => c.iso === this.country)

            this.$nextTick(() => {
                const input = this.$refs.search

                if (input) {
                    input.focus()
                }

                this.scrollToActive()
            })
        },

        close() {
            this.open = false
            this.activeIndex = -1
        },

        selectCountry(iso) {
            const country = this.index.get(iso)

            if (! country) {
                return
            }

            this.country = iso
            this.dialCode = country.dialCode
            // Re-mask the existing digits against the new country's example.
            this.national = this.format(digitsOnly(this.national))

            this.close()
            this.commit()
            this.dispatch('phone-country-changed', { country: iso, dialCode: country.dialCode })

            this.$nextTick(() => this.focusInput())
        },

        onSearchKeydown(event) {
            const list = this.filteredCountries

            if (event.key === 'ArrowDown') {
                event.preventDefault()
                this.activeIndex = Math.min(this.activeIndex + 1, list.length - 1)
                this.scrollToActive()
            } else if (event.key === 'ArrowUp') {
                event.preventDefault()
                this.activeIndex = Math.max(this.activeIndex - 1, 0)
                this.scrollToActive()
            } else if (event.key === 'Enter') {
                event.preventDefault()

                if (list[this.activeIndex]) {
                    this.selectCountry(list[this.activeIndex].iso)
                }
            } else if (event.key === 'Escape') {
                event.preventDefault()
                this.close()
                this.focusInput()
            }
        },

        scrollToActive() {
            this.$nextTick(() => {
                const option = this.$refs.list?.querySelector('[data-active="true"]')

                if (option) {
                    option.scrollIntoView({ block: 'nearest' })
                }
            })
        },

        // --- affordances ----------------------------------------------------
        focusInput() {
            const input = this.$refs.input

            if (input) {
                input.focus()
            }
        },

        clear() {
            this.national = ''
            this.extension = ''
            this.commit()
            this.focusInput()
        },

        async copy() {
            const value = this.compose()

            if (! value || ! navigator.clipboard) {
                return
            }

            try {
                await navigator.clipboard.writeText(value)
                this.dispatch('phone-copied', { value })
            } catch (error) {
                // Clipboard denied (insecure context / permissions) — no-op.
            }
        },

        // --- events ---------------------------------------------------------
        dispatch(name, detail) {
            this.$root.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }))
        },
    }
}
