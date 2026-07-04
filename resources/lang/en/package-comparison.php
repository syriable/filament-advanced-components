<?php

declare(strict_types=1);

return [
    'features' => 'Features',

    'empty' => 'No features yet — use "Add feature" to create the first row.',

    'package_default_title' => 'Package :letter',

    'package_title_placeholder' => 'Package name',

    'feature_label_placeholder' => 'Feature name',

    'cell_placeholder' => 'Value',

    'footer_placeholder' => 'e.g. Select',

    'select_empty_option' => '—',

    'no_options' => 'No options yet — add some in the row settings.',

    'delivery_amount' => 'Delivery amount',

    'delivery_unit' => 'Delivery unit',

    'summary' => [
        'package' => 'package',
        'packages' => 'packages',
        'feature' => 'feature',
        'features' => 'features',
    ],

    'units' => [
        'days' => 'Days',
        'hours' => 'Hours',
    ],

    'types' => [
        'boolean' => 'Checkmark',
        'text' => 'Text',
        'number' => 'Number',
        'price' => 'Price',
        'select' => 'Select',
        'radio' => 'Radio',
        'textarea' => 'Textarea',
        'description' => 'Description',
        'delivery' => 'Delivery time',
        'footer' => 'Footer',
    ],

    'actions' => [
        'add_package' => 'Add package',
        'add_feature' => 'Add feature',
        'remove_package' => 'Remove package',
        'remove_feature' => 'Remove feature',
        'reorder_package' => 'Drag to reorder package',
        'reorder_feature' => 'Drag to reorder feature',
        'feature_settings' => 'Feature settings',
        'toggle_collapse' => 'Toggle editor',
        'add_option' => 'Add option',
        'remove_option' => 'Remove option',
    ],

    'settings' => [
        'options' => 'Options',
        'option_placeholder' => 'Option label',
        'currency' => 'Currency',
    ],

    'validation' => [
        'min_packages' => '{1} At least one package is required.|[2,*] At least :min packages are required.',
        'max_packages' => '{1} No more than one package is allowed.|[2,*] No more than :max packages are allowed.',
    ],
];
