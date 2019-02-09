<?php

return [

    'folder' => env('SYNC_FOLDER', database_path('synchronizations')),
    'table' => env('SYNC_TABLE', 'synchronizations'),
];
