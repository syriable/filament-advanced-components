<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\Phone\Contracts;

use Syriable\Filament\Plugins\AdvancedComponents\Phone\Data\PhoneNumberData;
use Syriable\Filament\Plugins\AdvancedComponents\Phone\Enums\PhoneFormat;

/**
 * Decides the exact string that gets persisted for a parsed number.
 *
 * Normalization is the last step before storage: it takes the resolved
 * {@see PhoneNumberData} and the field's configured storage {@see PhoneFormat}
 * and returns the value to save (or `null` to store nothing). The bundled
 * implementation formats to the storage format; override this contract to
 * enforce house rules — always strip extensions, store digits only, keep the
 * raw input for unparseable numbers, and so on.
 */
interface NormalizesPhoneNumbers
{
    /**
     * The value to persist for `$number` given the field's `$storageFormat`,
     * or `null` when nothing should be stored (e.g. a blank input).
     */
    public function normalize(PhoneNumberData $number, PhoneFormat $storageFormat): ?string;
}
