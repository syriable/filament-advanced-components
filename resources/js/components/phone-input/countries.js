// Country lookup, dial-code matching, and search for the PhoneInput client.
//
// This is a small, self-contained index over the country catalog the server
// passes down. It never talks to the network and holds no phone logic beyond
// prefix matching — the authoritative parse still happens in PHP.

import { digitsOnly } from './formatter'

export class CountryIndex {
    constructor(countries) {
        this.countries = countries
        this.byIso = new Map(countries.map((country) => [country.iso, country]))
    }

    get(iso) {
        return this.byIso.get(iso) ?? null
    }

    // The country whose dial code is the longest prefix of an E.164 string
    // (without the leading `+`). Longer codes win so `+1` (US) and `+1876`
    // (Jamaica) resolve correctly; ties prefer `preferHint` when it also
    // matches, otherwise the first catalog entry (already alphabetically or
    // preference-ordered by the server).
    matchByDialCode(e164Digits, preferHint = null) {
        let best = null

        for (const country of this.countries) {
            const code = String(country.dialCode)

            if (! e164Digits.startsWith(code)) {
                continue
            }

            if (best === null || code.length > String(best.dialCode).length) {
                best = country
            } else if (code.length === String(best.dialCode).length && country.iso === preferHint) {
                best = country
            }
        }

        return best
    }

    // Filter the catalog by a free-text query matching name, ISO code, or dial
    // code. An empty query returns everything, untouched.
    search(query) {
        const needle = (query ?? '').trim().toLowerCase()

        if (needle === '') {
            return this.countries
        }

        const normalizedDial = needle.replace(/^\+/, '')

        return this.countries.filter((country) => {
            return (
                country.name.toLowerCase().includes(needle) ||
                country.iso.toLowerCase().includes(needle) ||
                String(country.dialCode).includes(normalizedDial)
            )
        })
    }
}

// Split an E.164-ish transport value into its parts: the dial-code-matched
// country (if any), the national significant digits, and the extension.
export function splitTransport(transport, index, preferHint = null) {
    let value = transport ?? ''
    let extension = ''

    const extMatch = value.match(/;ext=(\d+)/i)

    if (extMatch) {
        extension = extMatch[1]
        value = value.slice(0, extMatch.index)
    }

    if (! value.startsWith('+')) {
        return { country: null, national: digitsOnly(value), extension }
    }

    const e164Digits = digitsOnly(value)
    const country = index.matchByDialCode(e164Digits, preferHint)

    if (! country) {
        return { country: null, national: e164Digits, extension }
    }

    return {
        country,
        national: e164Digits.slice(String(country.dialCode).length),
        extension,
    }
}
