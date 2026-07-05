<?php

declare(strict_types=1);

return [

    // Per-cell screen-reader label, e.g. "Character 3 of 6".
    'cell_label' => 'Character :position of :total',

    'validation' => [

        // Shown when the submitted code is the wrong length or contains
        // characters outside the field's mode.
        'invalid' => 'The :attribute must be a valid :length-character code.',

    ],

];
