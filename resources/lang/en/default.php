<?php

return [
    'UNDEFINED_STATUS'                      => 'Undefined status string: :status',
    'API_ACCESS_DENY'                       => 'Insufficient permission access deny',
    'UNAUTHORIZED'                          => 'User unauthorized. Please Login first',
    'INSUFFICIENT_PERMISSION'               => 'Insufficient permission',
    'INSUFFICIENT_PERMISSION_ADMINISTRATOR' => 'Insufficient permission administrator',
    'INSUFFICIENT_PERMISSION_SUPER_ADMIN'   => 'Insufficient permission super admin',
    'INSUFFICIENT_PERMISSION_ACTION'        => 'Insufficient permission :model-:method',
    'INSUFFICIENT_PERMISSION_VIEW'          => 'Insufficient permission view',

    // Get
    'GET_ITEM_SUCCESS'                      => '{$ModelName} get item success',
    'ITEM_NOT_EXIST'                        => '{$ModelName} item not exist',
    'IS_LOCKED'                             => '{$ModelName} is locked',

    // Create
    'CREATE_SUCCESS'                        => '{$ModelName} create success',
    'CREATE_NESTED_SUCCESS'                 => '{$ModelName} create success',
    'CREATE_FAIL'                           => '{$ModelName} create fail',
    'CREATE_NESTED_FAIL'                    => '{$ModelName} create nested fail',

    // Update
    'STORE_WITH_EXIST_ALIAS'                => '{$ModelName} store with exist alias',
    'STORE_NESTED_WITH_EXIST_PATH'          => '{$ModelName} store with exist path',
    'UPDATE_SUCCESS'                        => '{$ModelName} update success',
    'UPDATE_NESTED_SUCCESS'                 => '{$ModelName} update nested success',
    'UPDATE_FAIL'                           => '{$ModelName} update fail',
    'UPDATE_NESTED_FAIL'                    => '{$ModelName} update nested fail',

    // Delete
    'DELETE_SUCCESS'                        => '{$ModelName} delete success',
    'DELETE_NESTED_SUCCESS'                 => '{$ModelName} delete nested success',
    'DELETE_FAIL'                           => '{$ModelName} delete fail',
    'DELETE_NESTED_FAIL'                    => '{$ModelName} delete nested fail',

    // Trash
    'TRASH_SUCCESS'                         => '{$ModelName} trash success',
    'TRASH_FAIL'                            => '{$ModelName} trash fail',

    // Published
    'PUBLISHED_SUCCESS'                     => '{$ModelName} published success',
    'PUBLISHED_FAIL'                        => '{$ModelName} published fail',
    'UNPUBLISHED_SUCCESS'                   => '{$ModelName} unpublished success',
    'UNPUBLISHED_FAIL'                      => '{$ModelName} unpublished fail',

    // Archive
    'ARCHIVE_SUCCESS'                       => '{$ModelName} archive success',
    'ARCHIVE_FAIL'                          => '{$ModelName} archive fail',

    // Search
    'SEARCH_SUCCESS'                        => '{$ModelName} search success',
    'SEARCH_NESTED_SUCCESS'                 => '{$ModelName} search nested success',
    'SEARCH_FAIL'                           => '{$ModelName} search fail',
    'SEARCH_NESTED_FAIL'                    => '{$ModelName} search nested fail',

    // Ordering
    'ORDERING_SUCCESS'                      => '{$ModelName} ordering success',
    'ORDERING_NESTED_SUCCESS'               => '{$ModelName} ordering nested success',
    'INVALID_ORDERING_DIFF'                 => '{$ModelName} invalid ordering diff, target ordering: :target_ordering',
    'INVALID_INPUT'                         => 'Invalid input',
];
