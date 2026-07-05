<?php

declare(strict_types=1);

return [

    'notifications' => [

        // Shown when the `onConfirm()` callback throws, unless overridden
        // via `confirmationFailureNotification()`.
        'confirmation_failed' => [
            'title' => 'Unable to save changes',
        ],

    ],

];
