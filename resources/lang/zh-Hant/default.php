<?php

return [
    'UNDEFINED_STATUS'                      => '未定義回應代碼: :status',
    'UNAUTHORIZED'                          => '使用者未認證',
    'API_ACCESS_DENY'                       => 'API 使用權限不足',
    'INSUFFICIENT_PERMISSION'               => '使用者權限不足',
    'INSUFFICIENT_PERMISSION_ADMINISTRATOR' => '使用者權限不足：管理者',
    'INSUFFICIENT_PERMISSION_SUPER_ADMIN'   => '使用者權限不足：超級管理者',
    'INSUFFICIENT_PERMISSION_ACTION'        => '使用者權限不足無法操作: :model-:method',
    'INSUFFICIENT_PERMISSION_VIEW'          => '使用者權限不足無法觀看',

    // Get
    'GET_ITEM_SUCCESS'                      => '{$ModelName} 取得項目成功',
    'ITEM_NOT_EXIST'                        => '{$ModelName} 項目不存在',
    'IS_LOCKED'                             => '{$ModelName} 項目鎖定中',

    // Create
    'CREATE_SUCCESS'                        => '{$ModelName} 建立項目成功',
    'CREATE_NESTED_SUCCESS'                 => '{$ModelName} 建立項目成功',
    'CREATE_FAIL'                           => '{$ModelName} 建立項目失敗',
    'CREATE_NESTED_FAIL'                    => '{$ModelName} 建立項目失敗',

    // Update
    'STORE_WITH_EXIST_ALIAS'                => '{$ModelName} 已存在相同的別名',
    'STORE_NESTED_WITH_EXIST_PATH'          => '{$ModelName} 已存在相同的別名路徑',
    'UPDATE_SUCCESS'                        => '{$ModelName} 更新項目成功',
    'UPDATE_NESTED_SUCCESS'                 => '{$ModelName} 更新項目成功',
    'UPDATE_FAIL'                           => '{$ModelName} 更新項目失敗',
    'UPDATE_NESTED_FAIL'                    => '{$ModelName} 更新項目失敗',

    //Ordering
    'UPDATE_ORDERING_SUCCESS'               => '{$ModelName} 更新排序成功',
    'UPDATE_ORDERING_FAIL'                  => '{$ModelName} 更新排序失敗',
    'UPDATE_ORDERING_NESTED_SUCCESS'        => '{$ModelName} 更新排序成功',
    'UPDATE_ORDERING_NESTED_FAIL'           => '{$ModelName} 更新排序失敗',

    // Delete
    'DELETE_SUCCESS'                        => '{$ModelName} 刪除項目成功',
    'DELETE_NESTED_SUCCESS'                 => '{$ModelName} 刪除項目成功',
    'DELETE_FAIL'                           => '{$ModelName} 刪除項目失敗',
    'DELETE_NESTED_FAIL'                    => '{$ModelName} 刪除項目失敗',

    // Trash
    'TRASH_SUCCESS'                         => '{$ModelName} 丟到垃圾桶成功',
    'TRASH_FAIL'                            => '{$ModelName} 丟到垃圾桶失敗',

    // Published
    'PUBLISHED_SUCCESS'                     => '{$ModelName} 發佈項目成功',
    'PUBLISHED_FAIL'                        => '{$ModelName} 發佈項目失敗',
    'UNPUBLISHED_SUCCESS'                   => '{$ModelName} 下架項目成功',
    'UNPUBLISHED_FAIL'                      => '{$ModelName} 下架項目失敗',

    // Archive
    'ARCHIVE_SUCCESS'                       => '{$ModelName} 封存項目成功',
    'ARCHIVE_FAIL'                          => '{$ModelName} 封存項目失敗',

    // Search
    'SEARCH_SUCCESS'                        => '{$ModelName} 搜尋項目成功',
    'SEARCH_NESTED_SUCCESS'                 => '{$ModelName} 搜尋項目成功',
    'SEARCH_FAIL'                           => '{$ModelName} 搜尋項目失敗',
    'SEARCH_NESTED_FAIL'                    => '{$ModelName} 搜尋項目失敗',

    // Ordering
    'ORDERING_SUCCESS'                      => '{$ModelName} 排序項目成功',
    'ORDERING_NESTED_SUCCESS'               => '{$ModelName} 排序項目成功',
    'INVALID_ORDERING_DIFF'                 => '{$ModelName} 不合法的排序: :diff',
    'INVALID_INPUT'                         => '輸入的資料有誤',

    // Checkout
    'CHECKOUT_SUCCESS'                      => '{$ModelName} 回存成功',

    // Tree
    'GET_TREE_SUCCESS'                      => '{$ModelName} 取得樹狀結構成功',
    'GET_TREE_LIST_SUCCESS'                 => '{$ModelName} 取得樹狀結構清單成功',

    // Get List
    'GET_LIST_SUCCESS'                      => '{$ModelName} 取得清單成功',
];
