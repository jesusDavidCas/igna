<?php

return [
    'required' => 'The :attribute field is required.',
    'file' => 'The :attribute must be a valid file.',
    'max' => [
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'attributes' => [
        'file' => 'file',
        'title' => 'title',
        'deliverable_type' => 'deliverable type',
    ],
];
