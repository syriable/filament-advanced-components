import {
    clone,
    packageLetters,
    reorderById,
    uuid,
} from './package-comparison/state'

// The Alpine component behind the PackageComparison form field.
//
// `state` is the Livewire-entangled JSON blob ({ packages, rows }) — the
// single source of truth. Every interaction mutates it locally; nothing here
// talks to the server, so with the default deferred binding zero requests
// fire until the form itself syncs.
//
// `types` are the row type descriptors serialized by the PHP RowType
// classes: { name, label, default, config, hasSettings }. They let new rows,
// packages, and cells be seeded entirely client-side.
export default function packageComparison({
    state,
    isDisabled,
    minPackages,
    maxPackages,
    canAddPackages,
    canDeletePackages,
    types,
    packageTitleTemplate,
    summaryLabels,
    unitLabels,
}) {
    return {
        state,

        collapsed: false,

        typePickerOpen: false,

        settingsRowId: null,

        init() {
            // The server hydrates a normalized state, but a misconfigured
            // statePath shouldn't take the whole page down.
            if (!this.state || typeof this.state !== 'object') {
                this.state = { packages: [], rows: [] }
            }

            this.state.packages ??= []
            this.state.rows ??= []
        },

        get gridStyle() {
            return `grid-template-columns: var(--fi-pc-feature-col-w) repeat(${this.state.packages.length}, minmax(var(--fi-pc-package-col-min-w), 1fr))`
        },

        get summary() {
            const packages = this.state.packages.length
            const rows = this.state.rows.length

            const packagesLabel =
                packages === 1 ? summaryLabels.package : summaryLabels.packages
            const rowsLabel =
                rows === 1 ? summaryLabels.feature : summaryLabels.features

            return `${packages} ${packagesLabel} · ${rows} ${rowsLabel}`
        },

        get canAddPackage() {
            return (
                canAddPackages &&
                !isDisabled &&
                (maxPackages === null ||
                    this.state.packages.length < maxPackages)
            )
        },

        get canDeletePackage() {
            return (
                canDeletePackages &&
                !isDisabled &&
                this.state.packages.length > minPackages
            )
        },

        addPackage() {
            if (!this.canAddPackage) {
                return
            }

            const id = uuid()

            this.state.packages.push({
                id,
                title: packageTitleTemplate.replace(
                    ':letter',
                    packageLetters(this.state.packages.length),
                ),
                meta: {},
            })

            // Seed the new column in every row so cell bindings never hit
            // an undefined value (composite types dereference immediately).
            this.state.rows.forEach((row) => {
                row.values[id] = clone(types[row.type]?.default ?? null)
            })
        },

        removePackage(id) {
            if (!this.canDeletePackage) {
                return
            }

            this.state.packages = this.state.packages.filter(
                (pkg) => pkg.id !== id,
            )

            this.state.rows.forEach((row) => {
                delete row.values[id]
            })
        },

        reorderPackages(event) {
            this.state.packages = reorderById(
                Alpine.raw(this.state.packages),
                event.target.sortable.toArray(),
            )
        },

        addRow(type) {
            const descriptor = types[type]

            if (!descriptor) {
                return
            }

            const values = {}

            this.state.packages.forEach((pkg) => {
                values[pkg.id] = clone(descriptor.default)
            })

            this.state.rows.push({
                id: uuid(),
                label: '',
                type,
                config: clone(descriptor.config),
                values,
            })

            this.typePickerOpen = false
        },

        removeRow(id) {
            this.state.rows = this.state.rows.filter((row) => row.id !== id)

            if (this.settingsRowId === id) {
                this.settingsRowId = null
            }
        },

        reorderRows(event) {
            this.state.rows = reorderById(
                Alpine.raw(this.state.rows),
                event.target.sortable.toArray(),
            )
        },

        toggleSettings(rowId) {
            this.settingsRowId = this.settingsRowId === rowId ? null : rowId
        },

        hasSettings(row) {
            return Boolean(types[row.type]?.hasSettings)
        },

        typeLabel(name) {
            return types[name]?.label ?? name
        },

        unitLabel(unit) {
            return unitLabels?.[unit] ?? unit
        },
    }
}
