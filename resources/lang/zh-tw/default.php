<?php

return [
    'UNAUTHORIZED'                          => '使用者未認證',
    'INSUFFICIENT_PERMISSION'               => '使用者權限不足',
    'INPUT_INVALID'                         => '輸入的欄位不合法 ',

    // Get
    'GET_ITEM_SUCCESS'                      => '{$ModuleName} 取得項目成功',
    'ITEM_NOT_EXIST'                        => '{$ModuleName} 項目不存在 :key: :value',
    'IS_LOCKED'                             => '{$ModuleName} 項目鎖定中',

    // Create
    'CREATE_SUCCESS'                        => '{$ModuleName} 建立項目成功',
    'CREATE_NESTED_SUCCESS'                 => '{$ModuleName} 建立項目成功',
    'CREATE_FAIL'                           => '{$ModuleName} 建立項目失敗',
    'CREATE_NESTED_FAIL'                    => '{$ModuleName} 建立項目失敗',

    // Update
    'STORE_WITH_EXIST_ALIAS'                => '{$ModuleName} 已存在相同的別名',
    'STORE_NESTED_WITH_EXIST_PATH'          => '{$ModuleName} 已存在相同的路徑',
    'UPDATE_SUCCESS'                        => '{$ModuleName} 更新項目成功',
    'UPDATE_NESTED_SUCCESS'                 => '{$ModuleName} 更新項目成功',
    'UPDATE_FAIL'                           => '{$ModuleName} 更新項目失敗',
    'UPDATE_NESTED_FAIL'                    => '{$ModuleName} 更新項目失敗',

    // Delete
    'DELETE_SUCCESS'                        => '{$ModuleName} 刪除項目成功',
    'DELETE_NESTED_SUCCESS'                 => '{$ModuleName} 刪除項目成功',
    'DELETE_FAIL'                           => '{$ModuleName} 刪除項目失敗',
    'DELETE_NESTED_FAIL'                    => '{$ModuleName} 刪除項目失敗',

    // Trash
    'TRASH_SUCCESS'                         => '{$ModuleName} 丟到垃圾桶成功',
    'TRASH_FAIL'                            => '{$ModuleName} 丟到垃圾桶失敗',

    // Published
    'PUBLISHED_SUCCESS'                     => '{$ModuleName} 發布項目成功',
    'PUBLISHED_FAIL'                        => '{$ModuleName} 發布項目失敗',
    'UNPUBLISHED_SUCCESS'                   => '{$ModuleName} 下架項目成功',
    'UNPUBLISHED_FAIL'                      => '{$ModuleName} 下架項目失敗',

    // Archive
    'ARCHIVE_SUCCESS'                       => '{$ModuleName} 封存項目成功',
    'ARCHIVE_FAIL'                          => '{$ModuleName} 封存項目失敗',

    // Search
    'SEARCH_SUCCESS'                        => '{$ModuleName} 搜尋項目成功',
    'SEARCH_NESTED_SUCCESS'                 => '{$ModuleName} 搜尋項目成功',
    'SEARCH_FAIL'                           => '{$ModuleName} 搜尋項目失敗',
    'SEARCH_NESTED_FAIL'                    => '{$ModuleName} 搜尋項目失敗',

    // Ordering
    'ORDERING_SUCCESS'                      => '{$ModuleName} 排序項目成功',
    'ORDERING_NESTED_SUCCESS'               => '{$ModuleName} 排序項目成功',
    'INVALID_ORDERING_DIFF'                 => '{$ModuleName} 不合法的排序: :diff',
];
