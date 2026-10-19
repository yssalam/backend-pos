<?php

return [
    'api' => filter_var(env('DEMO_API', false), FILTER_VALIDATE_BOOL),
    'data_file' => storage_path('app/demo-data.json'),
];
