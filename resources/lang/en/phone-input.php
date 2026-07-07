<?php

declare(strict_types=1);

return [

    // Country selector.
    'select_country' => 'Select country',
    'search_placeholder' => 'Search countries…',
    'no_results' => 'No countries found',

    // Affordances.
    'copy' => 'Copy number',
    'clear' => 'Clear number',

    // Extension.
    'extension' => 'Extension',
    'extension_abbr' => 'ext.',

    // Hints.
    'example' => 'e.g.',

    // Line types — the label shown by ->showType().
    'types' => [
        'mobile' => 'Mobile',
        'fixed_line' => 'Landline',
        'fixed_line_or_mobile' => 'Phone',
        'toll_free' => 'Toll-free',
        'premium_rate' => 'Premium rate',
        'shared_cost' => 'Shared cost',
        'voip' => 'VOIP',
        'personal_number' => 'Personal number',
        'pager' => 'Pager',
        'uan' => 'UAN',
        'voicemail' => 'Voicemail',
        'unknown' => 'Phone',
    ],

    // Validation messages, keyed by the failure reason. :attribute is the
    // field's validation name.
    'validation' => [
        'not_a_number' => 'The :attribute is not a valid phone number.',
        'invalid_country' => 'The :attribute has an unrecognised country code.',
        'too_short' => 'The :attribute is too short.',
        'too_long' => 'The :attribute is too long.',
        'invalid' => 'The :attribute is not a valid phone number.',
        'invalid_type' => 'The :attribute must be a valid phone number of the required type.',
        'country_not_allowed' => 'The :attribute is from a country that is not allowed.',
    ],

];
