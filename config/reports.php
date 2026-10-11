<?php

return [
    'max_export_rows' => max(
        1,
        (int) env('REPORT_EXPORT_MAX_ROWS', 10000)
    ),

    'queue' => env('REPORTS_QUEUE', 'reports'),
];
