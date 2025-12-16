# Inventory Management System - Backend Documentation
 Table of Contents
1. [Backend Folder Structure](#backend-folder-structure)
2. [Main Controllers & Services](#main-controllers--services)
3. [Database Schema Overview](#database-schema-overview)
4. [Request Validation Classes](#request-validation-classes)
5. [Security Implementation](#security-implementation)
6. [API Endpoints](api-endpoints)

---

Backend Folder Structure

```
Inventory-Backend/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   ├── CheckDueDates.php          # Scheduled task for overdue items
│   │   │   └── CreateAdmin.php            # Artisan command: php artisan admin:create
│   │   └── Kernel.php                    # Console kernel (scheduled tasks)
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/
│   │   │   │   ├── AuthController.php     # Authentication (login, register, logout)
│   │   │   │   ├── CategoryController.php # Category CRUD operations
│   │   │   │   ├── ItemController.php     # Item CRUD operations
│   │   │   │   ├── TransactionController.php # Borrow/return transactions
│   │   │   │   ├── NotificationController.php # User notifications
│   │   │   │   └── UserController.php     # User management (admin only)
│   │   │   └── Controller.php             # Base controller
│   │   │
│   │   ├── Middleware/
│   │   │   ├── Authenticate.php           # Laravel authentication middleware
│   │   │   └── RoleMiddleware.php         # Role-based access control
│   │   │
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php       # Login validation
│   │       │   ├── RegisterRequest.php   # Registration validation
│   │       │   ├── UpdateProfileRequest.php
│   │       │   └── ChangePasswordRequest.php
│   │       ├── Category/
│   │       │   ├── StoreCategoryRequest.php
│   │       │   └── UpdateCategoryRequest.php
│   │       ├── Item/
│   │       │   ├── StoreItemRequest.php
│   │       │   └── UpdateItemRequest.php
│   │       └── Transaction/
│   │           └── BorrowTransactionRequest.php
│   │
│   ├── Models/
│   │   ├── User.php                       # User model with roles & permissions
│   │   ├── Category.php                   # Category model
│   │   ├── Item.php                       # Item model
│   │   ├── Transaction.php                # Transaction model
│   │   └── Notification.php               # Notification model
│   │
│   └── Providers/
│       ├── AppServiceProvider.php         # Application service provider
│       ├── AuthServiceProvider.php        # Authentication service provider
│       ├── EventServiceProvider.php       # Event service provider
│       └── RouteServiceProvider.php       # Route service provider
│
├── bootstrap/
│   ├── app.php                            # Application bootstrap (middleware, routes)
│   └── cache/                              # Bootstrap cache files
│
├── config/
│   ├── app.php                            # Application configuration
│   ├── auth.php                           # Authentication configuration
│   ├── cors.php                           # CORS configuration
│   ├── database.php                       # Database configuration
│   ├── permission.php                    # Spatie Permission package config
│   └── sanctum.php                        # Laravel Sanctum config
│
├── database/
│   ├── migrations/                        # Database migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2025_10_15_120718_update_users_table.php
│   │   ├── 2025_10_15_120820_create_categories_table.php
│   │   ├── 2025_10_15_120851_create_items_table.php
│   │   ├── 2025_10_15_120930_create_transactions_table.php
│   │   ├── 2025_10_15_120943_create_notifications_table.php
│   │   ├── 2025_10_15_120958_create_permission_tables.php
│   │   └── 2025_11_29_125220_create_personal_access_tokens_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php              # Main seeder
│       ├── RoleSeeder.php                 # Roles & permissions seeder
│       └── AdminSeeder.php                # Admin user seeder
│
├── routes/
│   ├── api.php                            # API routes
│   ├── web.php                            # Web routes
│   └── console.php                        # Console routes
│
├── storage/
│   ├── app/                               # File storage
│   │   ├── public/                        # Public files (images)
│   │   └── private/                       # Private files
│   ├── framework/                         # Framework files
│   │   ├── cache/                         # Application cache
│   │   ├── sessions/                      # Session files
│   │   └── views/                         # Compiled views
│   └── logs/                              # Application logs
│
└── public/                                 # Public web root
    └── index.php                          # Entry point
```

---

Main Controllers & Services

1. AuthController (`app/Http/Controllers/API/AuthController.php`)

**Purpose:** Handles user authentication and profile management.

**Methods:**
- `register(RegisterRequest $request)` - User registration
  - Validates input via `RegisterRequest`
  - Hashes password using `Hash::make()`
  - Assigns default 'user' role
  - Returns user data with token
  
- `login(LoginRequest $request)` - User login
  - Validates credentials via `LoginRequest`
  - Uses `Auth::attempt()` for authentication
  - Checks if user is restricted
  - Generates Sanctum token
  - Returns user data with token
  
- `logout(Request $request)` - User logout
  - Revokes current access token
  
- `user(Request $request)` - Get authenticated user
  - Returns current user with roles
  
- `updateProfile(UpdateProfileRequest $request)` - Update user profile
  - Updates name, email, profile image
  - Validates via `UpdateProfileRequest`
  
- `changePassword(ChangePasswordRequest $request)` - Change password
  - Validates current password
  - Hashes new password
  - Updates user password

Security Features:
- ✅ Password hashing with `Hash::make()`
- ✅ Input validation via FormRequest classes
- ✅ Account restriction check
- ✅ Token-based authentication (Sanctum)

---

### 2. **ItemController** (`app/Http/Controllers/API/ItemController.php`)

**Purpose:** Manages inventory items (admin only).

**Methods:**
- `index(Request $request)` - List all items
  - Supports filtering by category_id and status
  - Eager loads category relationship
  - Returns paginated results
  
- `store(StoreItemRequest $request)` - Create new item
  - Validates via `StoreItemRequest`
  - Handles image upload and resizing
  - Creates item record
  
- `show($id)` - Get single item
  - Returns item with category relationship
  
- `update(UpdateItemRequest $request, $id)` - Update item
  - Validates via `UpdateItemRequest`
  - Updates item details
  - Handles image replacement
  
- `destroy($id)` - Delete item
  - Soft deletes item
  - Deletes associated image

**Security Features:**
- ✅ Admin-only access via `role:admin` middleware
- ✅ Input validation via FormRequest
- ✅ Image validation (type, size)
- ✅ Error logging

---

### 3. **CategoryController** (`app/Http/Controllers/API/CategoryController.php`)

**Purpose:** Manages item categories (admin only).

**Methods:**
- `index()` - List all categories
- `store(StoreCategoryRequest $request)` - Create category
- `show($id)` - Get single category
- `update(UpdateCategoryRequest $request, $id)` - Update category
- `destroy($id)` - Delete category (soft delete)

**Security Features:**
- ✅ Admin-only access
- ✅ Input validation via FormRequest
- ✅ Soft deletes enabled

---

### 4. **TransactionController** (`app/Http/Controllers/API/TransactionController.php`)

**Purpose:** Manages borrow/return transactions.

**Methods:**
- `index(Request $request)` - List transactions
  - Users see only their transactions
  - Admins see all transactions
  - Supports filtering
  
- `borrow(BorrowTransactionRequest $request)` - Borrow item
  - Validates via `BorrowTransactionRequest`
  - Checks item availability
  - Creates transaction record
  - Updates item quantity and status
  - Creates notification
  
- `return($id)` - Return item
  - Updates transaction with return_date
  - Updates item quantity and status
  - Creates notification
  
- `cancel($id)` - Cancel transaction (admin only)
  - Cancels pending transaction
  - Restores item availability

**Security Features:**
- ✅ User can only see their own transactions
- ✅ Input validation via FormRequest
- ✅ Business logic validation (availability, dates)
- ✅ Automatic status updates

---

### 5. **NotificationController** (`app/Http/Controllers/API/NotificationController.php`)

**Purpose:** Manages user notifications.

**Methods:**
- `index()` - Get user notifications
- `getUnreadCount()` - Get unread notification count
- `markAsRead($id)` - Mark notification as read
- `markAllAsRead()` - Mark all notifications as read

**Security Features:**
- ✅ Users can only access their own notifications
- ✅ Authenticated access required

---

### 6. **UserController** (`app/Http/Controllers/API/UserController.php`)

**Purpose:** User management (admin only).

**Methods:**
- `index()` - List all users
  - Returns users with roles
  - Excludes passwords
  
- `toggleRestriction($id)` - Toggle user restriction
  - Restricts/unrestricts user account
  - Used for overdue item management

**Security Features:**
- ✅ Admin-only access
- ✅ Password never exposed in responses

---

## Request Validation Classes

### Purpose of Request Classes

**Request classes** (`app/Http/Requests/`) are Laravel FormRequest classes that:

1. **Centralize Validation Logic**
   - Define validation rules in one place
   - Reusable across controllers
   - Easy to maintain and test

2. **Automatic Validation**
   - Laravel automatically validates requests before controller methods execute
   - Returns 422 error if validation fails
   - No need for manual validation in controllers

3. **Custom Error Messages**
   - Define user-friendly error messages
   - Consistent error responses

4. **Authorization**
   - `authorize()` method checks if user can perform action
   - Returns 403 if unauthorized

### Request Classes Implemented

#### Auth Requests
- **LoginRequest**: Validates email and password
- **RegisterRequest**: Validates name, email, password (with confirmation), profile_image
- **UpdateProfileRequest**: Validates profile updates
- **ChangePasswordRequest**: Validates password change with current password check

#### Category Requests
- **StoreCategoryRequest**: Validates category creation (name, description)
- **UpdateCategoryRequest**: Validates category updates

#### Item Requests
- **StoreItemRequest**: Validates item creation (name, category_id, quantity, image, status)
- **UpdateItemRequest**: Validates item updates

#### Transaction Requests
- **BorrowTransactionRequest**: Validates borrow transaction (item_id, borrow_date, due_date)

### Example: BorrowTransactionRequest

```php
public function rules(): array
{
    return [
        'item_id' => 'required|exists:items,id',
        'borrow_date' => 'required|date|after_or_equal:today',
        'due_date' => 'required|date|after:borrow_date',
    ];
}
```

**Validation Features:**
- ✅ Required field validation
- ✅ Database existence validation (`exists:items,id`)
- ✅ Date validation
- ✅ Date comparison (due_date after borrow_date)
- ✅ Custom error messages

### Are Request Classes Working?

**✅ YES** - All controllers use FormRequest classes:
- Controllers type-hint FormRequest classes
- Laravel automatically validates before controller execution
- Returns 422 with validation errors if invalid
- Custom error messages are returned

### Are Request Classes Necessary?

**✅ YES** - They provide:
1. **Security**: Input validation prevents invalid/malicious data
2. **Code Quality**: Separation of concerns
3. **Maintainability**: Easy to update validation rules
4. **Consistency**: Uniform validation across the application
5. **User Experience**: Clear, custom error messages

---

## Security Implementation

### 1. Input Sanitization

**✅ Implemented via Laravel's built-in features:**

- **Automatic XSS Protection**: Laravel escapes output by default
- **SQL Injection Protection**: Eloquent ORM uses parameterized queries
- **CSRF Protection**: Enabled for web routes
- **Mass Assignment Protection**: Uses `$fillable` array in models

**Example from User Model:**
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'profile_image',
    'is_restricted',
];
```

**Request Validation:**
- All inputs validated via FormRequest classes
- Type checking (string, integer, date, etc.)
- Format validation (email, image, etc.)
- Size limits (max:255, max:2048 for images)

**Example from RegisterRequest:**
```php
'email' => 'required|string|email|max:255|unique:users',
'password' => ['required', 'confirmed', Password::defaults()],
'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
```

---

### 2. Authentication Middleware

**✅ Implemented using Laravel Sanctum:**

**Route Protection:**
```php
// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // All routes here require authentication
});
```

**Middleware Applied:**
- **`auth:sanctum`**: Validates API token
  - Applied to all protected routes
  - Token must be sent in `Authorization: Bearer {token}` header
  - Returns 401 if token invalid/missing

- **`role:admin`**: Role-based access control
  - Applied to admin-only routes
  - Uses `RoleMiddleware`
  - Returns 403 if user doesn't have 'admin' role

**Example from routes/api.php:**
```php
// Admin routes
Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('items', ItemController::class);
});
```

RoleMiddleware Implementation:
```php
public function handle(Request $request, Closure $next, $role): Response
{
    if (!$request->user() || !$request->user()->hasRole($role)) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized. You do not have the required role.'
        ], 403);
    }
    return $next($request);
}
```

---

### 3. Password Hashing

**✅ Implemented using Laravel's Hash facade:**

**Registration:**
```php
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password), // ✅ Password hashed
]);
```

**Password Change:**
```php
$user->password = Hash::make($request->password); // ✅ New password hashed
$user->save();
```

**Model Auto-Hashing:**
```php
// User.php
protected function casts(): array
{
    return [
        'password' => 'hashed', // ✅ Automatically hashes on assignment
    ];
}
```

**Password Validation:**
- Uses `Password::defaults()` in RegisterRequest
- Requires password confirmation
- Enforces password strength rules

**Login Authentication:**
```php
if (!Auth::attempt($request->only('email', 'password'))) {
    // ✅ Laravel automatically compares hashed password
    return response()->json(['message' => 'Invalid credentials'], 401);
}
```

**Security Features:**
- ✅ Passwords never stored in plain text
- ✅ Uses bcrypt algorithm (default Laravel)
- ✅ Automatic hashing on model assignment
- ✅ Secure password comparison on login

---

### 4. Additional Security Features

**CORS Configuration:**
- Configured in `config/cors.php`
- Allows specific origins (localhost:3000, localhost:3001)
- Prevents unauthorized cross-origin requests

**Error Handling:**
- Detailed errors only in development (`APP_DEBUG=true`)
- Generic errors in production
- Error logging for debugging

**Account Restrictions:**
- `is_restricted` flag prevents login
- Used for users with overdue items
- Checked during login process

**Token Management:**
- Laravel Sanctum tokens
- Token expiration support
- Token revocation on logout
- Secure token storage

---

## Database Schema Overview

### Core Business Tables

#### 1. **users**
- `id` (PK)
- `name`, `email` (unique)
- `password` (hashed)
- `profile_image`
- `is_restricted` (boolean)
- `email_verified_at`, `remember_token`
- `created_at`, `updated_at`

**Relationships:**
- Has many: `transactions`, `notifications`
- Belongs to many: `roles`, `permissions` (polymorphic)

#### 2. **categories**
- `id` (PK)
- `name`, `description`
- `created_at`, `updated_at`, `deleted_at` (soft delete)

**Relationships:**
- Has many: `items` (cascade delete)

#### 3. **items**
- `id` (PK)
- `name`, `description`
- `category_id` (FK → categories)
- `quantity` (integer)
- `image` (string)
- `status` (enum: available, borrowed, maintenance)
- `created_at`, `updated_at`, `deleted_at` (soft delete)

**Relationships:**
- Belongs to: `category`
- Has many: `transactions` (cascade delete)

#### 4. **transactions**
- `id` (PK)
- `user_id` (FK → users, cascade delete)
- `item_id` (FK → items, cascade delete)
- `borrow_date`, `due_date`, `return_date`
- `status` (enum: borrowed, returned, overdue)
- `created_at`, `updated_at`, `deleted_at` (soft delete)

**Relationships:**
- Belongs to: `user`, `item`

#### 5. **notifications**
- `id` (PK)
- `user_id` (FK → users, cascade delete)
- `message` (text)
- `status` (enum: unread, read)
- `created_at`, `updated_at`

**Relationships:**
- Belongs to: `user`

### Permission System Tables (Spatie Laravel Permission)

#### 6. **roles**
- `id` (PK)
- `name`, `guard_name` (unique together)
- `created_at`, `updated_at`

#### 7. **permissions**
- `id` (PK)
- `name`, `guard_name` (unique together)
- `created_at`, `updated_at`

#### 8. **model_has_roles**
- `role_id` (FK → roles)
- `model_type`, `model_id` (polymorphic)
- Composite primary key

#### 9. **model_has_permissions**
- `permission_id` (FK → permissions)
- `model_type`, `model_id` (polymorphic)
- Composite primary key

#### 10. **role_has_permissions**
- `permission_id` (FK → permissions)
- `role_id` (FK → roles)
- Composite primary key

### System Tables

#### 11. **personal_access_tokens** (Laravel Sanctum)
- `id` (PK)
- `tokenable_type`, `tokenable_id` (polymorphic)
- `name`, `token` (unique)
- `abilities`, `last_used_at`, `expires_at`
- `created_at`, `updated_at`

#### 12. **sessions**
- `id` (PK, string)
- `user_id` (FK → users)
- `ip_address`, `user_agent`
- `payload`, `last_activity`

#### 13. **password_reset_tokens**
- `email` (PK)
- `token`
- `created_at`

### Key Database Features

- ✅ **Foreign Keys**: All relationships use foreign keys with CASCADE DELETE
- ✅ **Soft Deletes**: Categories, Items, Transactions support soft deletes
- ✅ **Indexes**: Foreign keys and frequently queried fields are indexed
- ✅ **Enums**: Status fields use ENUM for data validation
- ✅ **Polymorphic Relations**: Permission system uses polymorphic relations
- ✅ **Timestamps**: All tables include created_at/updated_at

---

## API Endpoints

### Public Endpoints
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `GET /api/health` - Health check

### Protected Endpoints (Require `auth:sanctum`)

#### Authentication
- `POST /api/logout` - Logout
- `GET /api/user` - Get authenticated user
- `PUT /api/user/profile` - Update profile
- `PUT /api/user/password` - Change password

#### User Endpoints
- `GET /api/user/categories` - List categories
- `GET /api/user/categories/{id}` - Get category
- `GET /api/user/items` - List items
- `GET /api/user/items/{id}` - Get item
- `GET /api/user/transactions` - List user transactions
- `POST /api/user/transactions/borrow` - Borrow item
- `PUT /api/user/transactions/{id}/return` - Return item
- `GET /api/user/notifications` - List notifications
- `GET /api/user/notifications/unread-count` - Get unread count
- `PUT /api/user/notifications/{id}/read` - Mark as read
- `PUT /api/user/notifications/mark-all-read` - Mark all as read

#### Admin Endpoints (Require `role:admin`)
- `GET /api/admin/categories` - List categories
- `POST /api/admin/categories` - Create category
- `GET /api/admin/categories/{id}` - Get category
- `PUT /api/admin/categories/{id}` - Update category
- `DELETE /api/admin/categories/{id}` - Delete category
- `GET /api/admin/items` - List items
- `POST /api/admin/items` - Create item
- `GET /api/admin/items/{id}` - Get item
- `PUT /api/admin/items/{id}` - Update item
- `DELETE /api/admin/items/{id}` - Delete item
- `GET /api/admin/transactions` - List all transactions
- `PUT /api/admin/transactions/{id}/cancel` - Cancel transaction
- `GET /api/admin/users` - List users
- `PUT /api/admin/users/{id}/toggle-restriction` - Toggle user restriction

---

## Summary

### ✅ Security Features Implemented

1. **Input Sanitization**
   - ✅ FormRequest validation on all inputs
   - ✅ Type checking and format validation
   - ✅ Size limits and file type validation
   - ✅ XSS protection via Laravel escaping
   - ✅ SQL injection protection via Eloquent ORM

2. **Authentication Middleware**
   - ✅ Laravel Sanctum token authentication
   - ✅ `auth:sanctum` middleware on protected routes
   - ✅ Token-based API authentication
   - ✅ Automatic token validation

3. **Password Hashing**
   - ✅ `Hash::make()` for password hashing
   - ✅ Automatic hashing on model assignment
   - ✅ Secure password comparison on login
   - ✅ Password strength validation

4. **Authorization**
   - ✅ Role-based access control (RBAC)
   - ✅ `role:admin` middleware for admin routes
   - ✅ User restriction system
   - ✅ Permission system (Spatie Laravel Permission)

5. **Additional Security**
   - ✅ CORS configuration
   - ✅ Error handling (detailed in dev, generic in prod)
   - ✅ Soft deletes for data recovery
   - ✅ Foreign key constraints
   - ✅ Input validation on all endpoints

### ✅ Request Classes Status

- **Working**: ✅ All Request classes are properly implemented and working
- **Necessary**: ✅ Yes, they provide essential validation and security
- **Purpose**: Centralize validation logic, improve code quality, enhance security

---

## Quick Reference

**Start Server:**
```bash
php artisan serve
```

**Create Admin:**
```bash
php artisan admin:create
```

**Run Migrations:**
```bash
php artisan migrate
php artisan db:seed
```

**Clear Cache:**
```bash
php artisan optimize:clear
```

---

hi

