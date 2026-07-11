<?php

declare(strict_types=1);

return [

    // Header fallback when no filename() is configured and the field has no label.
    'untitled' => 'Changes',

    // Screen-reader summary of the header stats.
    'stats_label' => ':additions additions, :deletions deletions',

    'additions' => '+:count',
    'deletions' => '−:count',

    // The collapsed-context toggle row, e.g. "Expand 12 hidden lines".
    'expand' => 'Expand :count hidden line|Expand :count hidden lines',

    // Shown instead of the diff table when oldValue or newValue is empty.
    'no_changes' => 'No changes to show.',

    // The modal()-mode Side-by-side/Inline view toggle.
    'side_by_side' => 'Side by Side',
    'inline' => 'Inline',

    // The modal()-mode Side-by-side box labels.
    'old' => 'Old',
    'new' => 'New',

    // Shown inside a modal()-mode box when that side's value is blank.
    'empty_placeholder' => '(empty)',

    // The modal()-mode footer button, shown only when onRollback() is set.
    'rollback' => 'Rollback',

];
