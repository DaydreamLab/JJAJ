<?php

return [
    'API_ACCESS_DENY'                       => 403,
    'INVALID_INPUT'                         => 403,
    'UNDEFINED_STATUS'                      => 500,
    'UNAUTHORIZED'                          => 401,
    'INSUFFICIENT_PERMISSION'               => 403,
    'INSUFFICIENT_PERMISSION_ADMINISTRATOR' => 403,
    'INSUFFICIENT_PERMISSION_SUPER_ADMIN'   => 403,
    'INSUFFICIENT_PERMISSION_ACTION'        => 403,
    'INSUFFICIENT_PERMISSION_VIEW'          => 403,

    // Get
    'GET_ITEM_SUCCESS'                      => 200,
    'ITEM_NOT_EXIST'                        => 404,
    'IS_LOCKED'                             => 403,

    // Create
    'CREATE_SUCCESS'                        => 200,
    'CREATE_FAIL'                           => 500,
    'CREATE_NESTED_SUCCESS'                 => 200,
    'CREATE_NESTED_FAIL'                    => 500,

    // Update
    'STORE_WITH_EXIST_ALIAS'                => 403,
    'STORE_NESTED_WITH_EXIST_PATH'          => 403,
    'UPDATE_SUCCESS'                        => 200,
    'UPDATE_FAIL'                           => 500,
    'UPDATE_NESTED_SUCCESS'                 => 200,
    'UPDATE_NESTED_FAIL'                    => 500,

    // Trash
    'TRASH_SUCCESS'                         => 200,
    'TRASH_FAIL'                            => 500,

    // Delete
    'DELETE_SUCCESS'                        => 200,
    'DELETE_FAIL'                           => 500,
    'DELETE_NESTED_SUCCESS'                 => 200,
    'DELETE_NESTED_FAIL'                    => 500,

    // Publish
    'PUBLISH_SUCCESS'                       => 200,
    'PUBLISH_FAIL'                          => 500,

    // Unpublished
    'UNPUBLISH_SUCCESS'                     => 200,
    'UNPUBLISH_FAIL'                        => 500,

    // Archive
    'ARCHIVE_SUCCESS'                       => 200,
    'ARCHIVE_FAIL'                          => 500,

    // Search
    'SEARCH_SUCCESS'                        => 200,
    'SEARCH_FAIL'                           => 500,

    // Ordering
    'UPDATE_ORDERING_SUCCESS'               => 200,
    'UPDATE_ORDERING_FAIL'                  => 500,
    'UPDATE_ORDERING_NESTED_SUCCESS'        => 200,
    'UPDATE_ORDERING_NESTED_FAIL'           => 500,

    // Checkout
    'CHECKOUT_SUCCESS'                      => 200,
    'CHECKOUT_FAIL'                         => 500,

    // Restore
    'RESTORE_SUCCESS'                       => 200,
    'RESTORE_FAIL'                          => 500,

    // Get Tree
    'GET_TREE_SUCCESS'                      => 200,

    // Get List
    'GET_LIST_SUCCESS'                      => 200
];
