# JJAJ

Laravel 程式碼生成工具，採用 Repository-Service 設計模式，快速建構 CRUD 功能。

## 安裝

```bash
composer require daydreamlab/jjaj
```

## 核心命令

### jjaj:mc
生成完整的 MVC 結構，包含 Model、Controller、Service、Repository、Request、Migration 和 Constants。

```bash
php artisan jjaj:mc {name} {--admin} {--front} {--component=}
```

**參數說明:**
- `name`: 模型名稱（必填，使用單數形式）
- `--admin`: 生成後台管理功能
- `--front`: 生成前台功能
- `--component=`: 套件名稱，將檔案放置於 `packages/{package_name}`

**範例:**
```bash
php artisan jjaj:mc Category --admin --front --component=Cms
```

**生成檔案路徑:**
- Controller: `app/Http/Controllers/API/{name}/`
- Model: `app/Models/{name}/`
- Repository: `app/Repositories/{name}/`
- Service: `app/Services/{name}/`
- Request: `app/Http/Requests/{name}/`
- Constant: `app/constants/{name}/`
- Migration: `database/migrations/`

## 工具命令

### jjaj:clear
清除 Laravel 快取
```bash
php artisan jjaj:clear
```
等同於執行:
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan view:clear`

### jjaj:refresh
重新整理資料庫和 Passport
```bash
php artisan jjaj:refresh
```
等同於執行:
- `php artisan migrate:refresh`
- `php artisan passport:install`

### jjaj:delete
刪除由 `jjaj:mc` 生成的檔案
```bash
php artisan jjaj:delete {name}
```

### jjaj:test
生成測試檔案
```bash
php artisan jjaj:test {name} {--unit} {--feature} {--type=} {--admin} {--front}
```

**參數說明:**
- `--type=`: 測試類型 (controller, model, repository, service)
- `--unit`: 生成單元測試
- `--feature`: 生成功能測試

**範例:**
```bash
php artisan jjaj:test Category --unit --type=service --admin --front
```

## 個別組件命令

### 生成 Model
```bash
php artisan jjaj:model {name} {--admin} {--front} {--component=}
```

### 生成 Controller
```bash
php artisan jjaj:controller {name} {--admin} {--front} {--component=}
```

### 生成 Service
```bash
php artisan jjaj:service {name} {--admin} {--front} {--component=}
```

### 生成 Repository
```bash
php artisan jjaj:repository {name} {--admin} {--front} {--component=}
```

### 生成 Request
```bash
php artisan jjaj:request {name} {--admin} {--front} {--component=}
```

### 生成 Migration
```bash
php artisan jjaj:migration {name} {--create=} {--table=}
```

### 生成 Constant
```bash
php artisan jjaj:constant {name} {--model=}
```

## 架構說明

### 設計模式
採用 Repository-Service 設計模式，分離資料存取與業務邏輯：

```
Controller (API層)
    ↓
Service (業務邏輯層)
    ↓
Repository (資料存取層)
    ↓
Model (資料模型層)
```

### 基礎功能
生成的程式碼包含以下功能：

**CRUD 操作:**
- `store`: 新增/更新資料
- `remove`: 刪除資料
- `search`: 搜尋資料
- `getItem`: 取得單筆資料
- `getList`: 取得列表

**狀態管理:**
- `state`: 變更發布狀態（發布/取消發布/封存/垃圾桶）

**排序功能:**
- `ordering`: 調整項目排序
- `orderingByRef`: 依參考項目調整排序

**其他功能:**
- `checkout`: 解除鎖定
- `featured`: 設定精選狀態

### Request 驗證類型
自動生成多種 Request 驗證類別：
- `StorePost`: 儲存資料驗證
- `RemovePost`: 刪除資料驗證
- `StatePost`: 狀態變更驗證
- `SearchPost`: 搜尋參數驗證
- `OrderingPost`: 排序驗證
- `FeaturedPost`: 精選設定驗證
- `CheckoutPost`: 解鎖驗證
- `OrderingByRefPost`: 參考排序驗證

## 輔助工具

### Helper Functions
```php
show($data)                    // 除錯輸出
startLog()                     // 開始 SQL 查詢記錄
showLog()                      // 顯示 SQL 查詢記錄
showLogTime()                  // 顯示查詢時間
showLogCount()                 // 顯示查詢次數
flushLog()                     // 清除查詢記錄
stopLog()                      // 停止查詢記錄
getJson($path, $assoc)         // 讀取 JSON 檔案
arrayToXmlStr($array)          // 陣列轉 XML
xmlStrToArray($xmlStr)         // XML 轉陣列
```

### 驗證規則
提供台灣地區專用驗證規則：
- `TaiwanID`: 身分證字號
- `TaiwanMobilePhone`: 手機號碼
- `TaiwanResidentCertificateIDNumber`: 居留證號碼
- `TaiwanTaxSerialNumber`: 稅籍編號
- `TaiwanUnifiedBusinessNumber`: 統一編號

## 依賴套件

- `daydreamlab/observer`: 觀察者模式支援

## 系統需求

- PHP >= 7.4
- Laravel >= 8.0

## 授權

此套件專為 Jordan, Jerry, Alex 和 Jackie 開發使用。