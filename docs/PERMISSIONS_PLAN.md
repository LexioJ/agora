# Agora Permissions System - Redesign Plan

## 1. Current State Analysis

### 1.1 How Permissions Work Today

Agora currently uses **five hardcoded Nextcloud groups** to assign roles:

| Nextcloud Group         | Internal Constant         | Role           | Purpose                              |
|-------------------------|---------------------------|----------------|--------------------------------------|
| `Agora Users`           | `GROUP_USERS`             | User           | Access shared files / basic app use  |
| `Agora Legislative`     | `GROUP_LEGISLATIVE`       | Legislative    | Access Legislative family menu       |
| `Agora Moderator`       | `GROUP_MODERATOR`         | Moderator      | Moderation management                |
| `Agora Official`        | `GROUP_OFFICIAL`          | Official       | Create official responses            |
| `Agora Group Editor`    | `GROUP_GROUP_EDITOR`      | Group Editor   | Manage inquiry groups                |

These groups must be **manually created** in Nextcloud with **exact names**. Users are added to them by a Nextcloud admin. The app then checks membership via `IGroupManager::isInGroup()`.

### 1.2 Identified Problems

#### P1: Static, hardcoded group names
Group names like `"Agora Moderator"` are string constants in `lib/Model/Group/Group.php` and `lib/Model/User/User.php`. There is a naming inconsistency: `Group.php` uses display names (`"Agora Moderator"`) while `User.php` uses internal IDs (`"agora_moderator"`). If a Nextcloud admin creates the group with a slightly different name, the entire permission check breaks silently.

#### P2: Single-role hierarchy via `getRole()`
`User::getRole()` (`lib/Model/User/User.php:65`) returns **only the first matching role** in priority order (moderator > official > groupEditor > legislative). A user who is both a Moderator and an Official will only be seen as a Moderator. The `getRoles()` method exists but is not used by most permission logic.

#### P3: Frontend/backend permission divergence
- **Frontend** (`src/utils/permissions.ts`): A comprehensive 1792-line permission engine with context-based checks, covering inquiries, options, groups, comments, supports, shares, and more.
- **Backend** (`lib/Db/Inquiry.php`): Permission checks embedded directly in the entity model (via `request()` and `getIsAllowed()`), tightly coupled to entity state.
- These two systems evolved independently and have **no shared contract**. A permission that is denied on the frontend may be allowed by the backend API, or vice versa.

#### P4: No per-action backend middleware enforcement
Controllers use Nextcloud's `#[NoAdminRequired]` attribute to allow non-admin access, but there is **no Agora-specific authorization middleware**. Permission checks happen inside services or entity methods, leading to inconsistent enforcement. Some endpoints may lack checks entirely.

#### P5: Role-based rights are globally configured, not scoped
`ModeratorRights` and `OfficialRights` are stored in `IAppConfig` as single JSON objects. Every moderator gets the same rights. There is no way to say "User X is moderator only for Legislative inquiries" or "Group Y can moderate only their own inquiry group."

#### P6: No delegation or scoping mechanism
The current system has no concept of scoped permissions. You cannot assign someone as moderator for a specific inquiry group, category, or family without making them a global moderator.

#### P7: `hasGroupAccess()` returns `false` when there are no restrictions
In `permissions.ts:599`, when an inquiry has no group restrictions, `hasGroupAccess()` returns `false` instead of `true`. This is a bug that likely causes issues with permissions for unrestricted content.

---

## 2. Design Goals

1. **Use existing Nextcloud groups and teams** as the primary mechanism for role assignment -- no custom user management UI
2. **Support multi-role users** (a user can be Moderator + Official simultaneously)
3. **Enable scoped permissions** (moderator of a specific group/category, not just global)
4. **Enforce permissions on the backend** with a single authoritative source -- the frontend should query the backend, not compute its own parallel logic
5. **Make role-to-group mapping configurable** by admins, not hardcoded
6. **Maintain backwards compatibility** during migration
7. **Keep it simple** -- leverage Nextcloud infrastructure rather than building a custom RBAC engine

---

## 3. Proposed Architecture

### 3.1 Overview

```
                    +-----------------------+
                    |  Nextcloud Groups /   |
                    |  Teams (Circles)      |
                    +-----------+-----------+
                                |
                    +-----------v-----------+
                    | Role Mapping Service  |  <-- Admin-configurable
                    | (group -> role)       |
                    +-----------+-----------+
                                |
                    +-----------v-----------+
                    |  Permission Resolver  |  <-- Single authoritative engine
                    |  (role + scope + ctx) |
                    +-----------+-----------+
                           |         |
                    +------v--+  +---v---------+
                    | Backend |  | Frontend    |
                    | Enforcer|  | (reads from |
                    | (API)   |  |  backend)   |
                    +---------+  +-------------+
```

### 3.2 Layer 1: Configurable Role Mapping

**Current:** Hardcoded group names in PHP constants.
**Proposed:** An admin settings panel where the Nextcloud admin maps Nextcloud groups (or Teams/Circles) to Agora roles.

#### New DB table: `oc_agora_role_mapping`

| Column         | Type    | Description                                          |
|----------------|---------|------------------------------------------------------|
| `id`           | int     | Primary key                                          |
| `role`         | string  | Agora role identifier (e.g., `moderator`, `official`) |
| `group_id`     | string  | Nextcloud group GID or Circles ID                    |
| `group_type`   | string  | `group` or `circle`                                  |
| `scope_type`   | string  | `global`, `family`, `category`, `inquiry_group`      |
| `scope_id`     | string  | The ID of the scoped entity (null = global)          |
| `created_at`   | int     | Timestamp                                            |

**How it works:**
- Admin navigates to Agora Settings > Role Mappings
- For each Agora role, they select one or more Nextcloud groups or teams
- Optionally, they scope the mapping: "Group X has moderator rights for the Legislative family only"
- The mapping is stored in `oc_agora_role_mapping`
- On app initialization, a migration creates default mappings matching the current hardcoded group names, ensuring backwards compatibility

#### Available Roles (extensible):

| Role Key        | Description                                 | Default Group          |
|-----------------|---------------------------------------------|------------------------|
| `user`          | Basic app access, view shared inquiries     | `Agora Users`          |
| `moderator`     | Moderation powers                           | `Agora Moderator`      |
| `official`      | Create official responses                   | `Agora Official`       |
| `legislative`   | Access legislative family                   | `Agora Legislative`    |
| `group_editor`  | Create/manage inquiry groups                | `Agora Group Editor`   |

#### Key Changes in `User.php`:

```php
// BEFORE (hardcoded):
public function getIsModerator(): bool {
    return $this->groupManager->isInGroup($this->getId(), 'Agora Moderator');
}

// AFTER (configurable):
public function getIsModerator(string $scopeType = 'global', string $scopeId = null): bool {
    return $this->roleMappingService->hasRole($this->getId(), 'moderator', $scopeType, $scopeId);
}
```

### 3.3 Layer 2: Permission Resolver Service

A new centralized service that replaces the scattered permission logic.

#### `lib/Service/PermissionService.php`

```
class PermissionService {
    // Core permission checks
    can(string $userId, string $permission, PermissionContext $ctx): bool

    // Convenience methods
    canViewInquiry(string $userId, Inquiry $inquiry): bool
    canEditInquiry(string $userId, Inquiry $inquiry): bool
    canDeleteInquiry(string $userId, Inquiry $inquiry): bool
    canModerateInquiry(string $userId, Inquiry $inquiry): bool
    canCommentOnInquiry(string $userId, Inquiry $inquiry): bool
    canSupportInquiry(string $userId, Inquiry $inquiry): bool
    canShareInquiry(string $userId, Inquiry $inquiry): bool
    canCreateInquiryInFamily(string $userId, string $family): bool

    // Group operations
    canCreateInquiryGroup(string $userId): bool
    canModifyInquiryGroup(string $userId, InquiryGroup $group): bool
    canDeleteInquiryGroup(string $userId, InquiryGroup $group): bool

    // Bulk: compute all permissions at once for API responses
    getPermissionsForInquiry(string $userId, Inquiry $inquiry): array
    getPermissionsForInquiryGroup(string $userId, InquiryGroup $group): array
    getMenuPermissions(string $userId): array
}
```

**The resolver combines:**
1. User's roles (from `RoleMappingService`)
2. Role-based rights configuration (from `AppSettings` -- moderator/official rights)
3. Content state (access level, moderation status, archive status, etc.)
4. Ownership
5. Share-based access
6. Group membership for `owned_group` restrictions

**This replaces:**
- `src/utils/permissions.ts` as the authoritative source (frontend becomes read-only consumer)
- `lib/Db/Inquiry.php::request()` / `getIsAllowed()` (moved to service)
- Inline checks in services and controllers

### 3.4 Layer 3: Backend Enforcement via Middleware

#### `lib/Middleware/PermissionMiddleware.php`

A new middleware that reads PHP attributes on controller methods and enforces permissions before the action executes.

```php
// New attribute:
#[RequirePermission('edit', entityType: 'inquiry', paramName: 'id')]
```

**Usage in controllers:**

```php
// BEFORE:
#[NoAdminRequired]
public function update(int $id, string $title, ...): JSONResponse {
    // Permission check buried in service or entity
    $inquiry = $this->inquiryService->get($id);
    // ... hope the service checks permissions
}

// AFTER:
#[NoAdminRequired]
#[RequirePermission('edit', entityType: 'inquiry', paramName: 'id')]
public function update(int $id, string $title, ...): JSONResponse {
    // Permission already verified by middleware
    $inquiry = $this->inquiryService->get($id);
    // ... proceed safely
}
```

**How the middleware works:**
1. Intercepts request before controller action
2. Reads `#[RequirePermission]` attributes
3. Extracts entity ID from request parameters
4. Calls `PermissionService::can()` with the current user, requested permission, and entity
5. Throws `ForbiddenException` (HTTP 403) if denied
6. Controller only runs if authorized

#### Permissions to enforce:

| Permission          | Entity Types               | Description                           |
|---------------------|----------------------------|---------------------------------------|
| `view`              | inquiry, inquiry_group     | View content                          |
| `edit`              | inquiry, inquiry_group     | Modify content                        |
| `delete`            | inquiry, inquiry_group     | Delete content                        |
| `archive`           | inquiry, inquiry_group     | Archive content                       |
| `restore`           | inquiry, inquiry_group     | Restore from archive                  |
| `transfer`          | inquiry                    | Change ownership                      |
| `moderate`          | inquiry                    | Change moderation status              |
| `comment`           | inquiry, option            | Add comments                          |
| `support`           | inquiry, option            | Add votes/supports                    |
| `share`             | inquiry                    | Add shares                            |
| `use_resource`      | inquiry                    | Manage attachments                    |
| `create`            | inquiry, inquiry_group     | Create new content                    |
| `manage_members`    | inquiry_group              | Manage group members                  |
| `manage_permissions`| inquiry_group              | Manage group-level permissions        |

### 3.5 Layer 4: Frontend Permissions (Consumer)

**Current:** The frontend computes permissions independently from `permissions.ts`.
**Proposed:** The backend includes a `permissions` object in every API response.

#### API response structure:

```json
{
  "inquiry": {
    "id": 42,
    "title": "...",
    "permissions": {
      "canView": true,
      "canEdit": true,
      "canDelete": false,
      "canArchive": true,
      "canComment": true,
      "canSupport": true,
      "canShare": false,
      "canModerate": false,
      "canTransfer": false,
      "canUseResource": true
    }
  }
}
```

The frontend `permissions.ts` is refactored to simply read these server-provided values instead of recomputing them:

```typescript
// BEFORE: Complex local computation
export function canEdit(context: PermissionContext): boolean {
    if (context.isLocked || context.isArchived || ...) { ... }
    if (context.userType === UserType.Admin || context.isOwner) { ... }
    if (context.userType === UserType.Moderator) { ... }
    // 50+ lines of logic
}

// AFTER: Read from server response
export function canEdit(inquiry: InquiryStoreLike): boolean {
    return inquiry.permissions?.canEdit ?? false
}
```

**Benefits:**
- Single source of truth for permissions
- Frontend can never diverge from backend
- Less JavaScript to maintain and ship
- Permission changes require only backend updates

#### Session endpoint enhancement:

The existing session/init API already returns `appPermissions` and `currentUser`. Enhance it to include:

```json
{
  "currentUser": {
    "id": "alice",
    "roles": ["moderator", "legislative"],
    "scopedRoles": [
      { "role": "moderator", "scopeType": "family", "scopeId": "legislative" }
    ]
  },
  "menuPermissions": {
    "legislative": true,
    "official": false,
    "moderation": true,
    "groups": true
  }
}
```

### 3.6 Admin UI for Role Mapping

A new section in the existing Admin Settings page.

#### Component: `AdminRoleMappings.vue`

**Layout:**

```
+-------------------------------------------------------+
| Role Mappings                                         |
+-------------------------------------------------------+
| Role: Moderator                                       |
|   [Agora Moderator     ] [x]  (Global)               |
|   [City Council Members] [x]  (Family: Legislative)  |
|   [+ Add group...]                                    |
|                                                       |
| Role: Official                                        |
|   [Agora Official      ] [x]  (Global)               |
|   [+ Add group...]                                    |
|                                                       |
| Role: Legislative                                     |
|   [Agora Legislative   ] [x]  (Global)               |
|   [+ Add group...]                                    |
|                                                       |
| Role: Group Editor                                    |
|   [Agora Group Editor  ] [x]  (Global)               |
|   [+ Add group...]                                    |
|                                                       |
| Role: App User                                        |
|   [Agora Users         ] [x]  (Global)               |
|   [All Staff           ] [x]  (Global)               |
|   [+ Add group...]                                    |
+-------------------------------------------------------+
```

**Features:**
- Autocomplete group/team search using Nextcloud's `IGroupManager::search()`
- Optional scope selector (Global, Family, Category, Inquiry Group)
- Shows current members count per mapping
- Validates that groups exist before saving

---

## 4. Implementation Plan

### Phase 1: Foundation (Backend Permission Service)

**Goal:** Create the backend permission service as the single source of truth without changing any existing behavior.

#### Steps:

1. **Create `RoleMappingService`**
   - New file: `lib/Service/RoleMappingService.php`
   - Methods: `hasRole(userId, role, scopeType?, scopeId?)`, `getUserRoles(userId)`, `getRoleMappings(role?)`
   - Initially reads from both the new `oc_agora_role_mapping` table AND falls back to the existing hardcoded group checks
   - New file: `lib/Db/RoleMapping.php` (entity)
   - New file: `lib/Db/RoleMappingMapper.php` (mapper)

2. **Create `PermissionService`**
   - New file: `lib/Service/PermissionService.php`
   - Port all permission logic from `permissions.ts` and `Inquiry.php` into PHP methods
   - Write unit tests for every permission check
   - Wire into DI container via `Application.php`

3. **Create DB migration for `oc_agora_role_mapping`**
   - New migration file
   - Include a repair step that seeds default mappings from the current hardcoded group names
   - Ensure existing installations get backwards-compatible defaults

4. **Add permissions to API responses**
   - Modify `Inquiry::jsonSerialize()` to include a `permissions` array
   - Modify `InquiryGroup::jsonSerialize()` similarly
   - The `getPermissionsArray()` method in `Inquiry.php` already exists but delegates to `PermissionService`

#### Files to create:
- `lib/Service/RoleMappingService.php`
- `lib/Service/PermissionService.php`
- `lib/Db/RoleMapping.php`
- `lib/Db/RoleMappingMapper.php`
- `lib/Migration/Version010700Date[timestamp].php`
- `tests/Unit/Service/PermissionServiceTest.php`
- `tests/Unit/Service/RoleMappingServiceTest.php`

#### Files to modify:
- `lib/AppInfo/Application.php` (register new services)
- `lib/Db/Inquiry.php` (delegate `getPermissionsArray()` to `PermissionService`)
- `lib/Model/User/User.php` (use `RoleMappingService` instead of hardcoded groups)

### Phase 2: Backend Enforcement (Middleware)

**Goal:** Ensure all API endpoints enforce permissions consistently.

#### Steps:

1. **Create `RequirePermission` attribute**
   - New file: `lib/Attributes/RequirePermission.php`

2. **Create `PermissionMiddleware`**
   - New file: `lib/Middleware/PermissionMiddleware.php`
   - Register in `Application.php`
   - Intercepts controller methods annotated with `#[RequirePermission]`

3. **Annotate all controllers**
   - `InquiryApiController`: add `#[RequirePermission]` to all mutating endpoints
   - `CommentApiController`: add for comment creation/deletion
   - `SupportApiController`: add for support creation/deletion
   - `ShareApiController`: add for share management
   - `AttachmentApiController`: add for attachment management
   - `InquiryGroupController`: add for group management
   - `OptionApiController` / `OptionController`: add for option management
   - `OfficialResponseController`: add for official response creation

4. **Remove inline permission checks from services**
   - `InquiryService.php`: Remove `$inquiry->request()` calls, since middleware handles it
   - Other services: similarly remove redundant checks

#### Files to create:
- `lib/Attributes/RequirePermission.php`
- `lib/Middleware/PermissionMiddleware.php`

#### Files to modify:
- `lib/AppInfo/Application.php`
- `lib/Controller/InquiryApiController.php`
- `lib/Controller/CommentApiController.php`
- `lib/Controller/SupportApiController.php`
- `lib/Controller/ShareApiController.php`
- `lib/Controller/AttachmentApiController.php`
- `lib/Controller/InquiryGroupController.php`
- `lib/Controller/OptionApiController.php`
- `lib/Controller/OptionController.php`
- `lib/Controller/OfficialResponseController.php`
- `lib/Service/InquiryService.php`

### Phase 3: Frontend Migration

**Goal:** Refactor the frontend to consume server-provided permissions instead of computing them locally.

#### Steps:

1. **Update Pinia stores to store server permissions**
   - `stores/inquiry.ts`: Store the `permissions` object from API response
   - `stores/inquiryGroup.ts`: Same
   - `stores/session.ts`: Store `menuPermissions` and `scopedRoles`

2. **Refactor `permissions.ts`**
   - Keep the file but simplify all functions to read from store data
   - Remove all the complex computation logic
   - Keep the interfaces/types for type safety
   - Functions become thin wrappers: `canEdit(inquiry) => inquiry.permissions.canEdit`

3. **Update Vue components**
   - Components already call functions like `canEdit(context)` -- the function signature stays the same but the implementation changes
   - Update `createInquiryContext()` to build context from server data
   - Menu visibility in `Navigation.vue`, `NavigationMenu.vue` uses `menuPermissions` from session

4. **Remove console.log statements**
   - `permissions.ts` has leftover debug logs (lines 363, 364, 410) that should be removed

#### Files to modify:
- `src/utils/permissions.ts`
- `src/stores/inquiry.ts`
- `src/stores/inquiryGroup.ts`
- `src/stores/session.ts`
- `src/components/Inquiry/InquiryItemActions.vue`
- `src/components/Inquiry/InquiryActionToolbar.vue`
- `src/views/Navigation.vue`
- `src/views/NavigationMenu.vue`

### Phase 4: Admin UI & Scoped Permissions

**Goal:** Allow admins to configure role mappings and enable scoped permissions.

#### Steps:

1. **Create admin API endpoints**
   - `SettingsController`: Add CRUD endpoints for role mappings
   - `GET /api/v1.0/admin/role-mappings`
   - `POST /api/v1.0/admin/role-mapping`
   - `PUT /api/v1.0/admin/role-mapping/{id}`
   - `DELETE /api/v1.0/admin/role-mapping/{id}`

2. **Create admin UI component**
   - New file: `src/components/Settings/AdminSettings/AdminRoleMappings.vue`
   - Group/team search autocomplete
   - Scope selector (Global, Family, Category, Inquiry Group)
   - Add to existing admin settings page

3. **Create API module for role mappings**
   - New file: `src/Api/modules/roleMappings.ts`

4. **Enable scoped permission checks**
   - Update `PermissionService` to check scope when evaluating permissions
   - Example: A user in `City Council` with `moderator` role scoped to `family:legislative` can moderate only legislative inquiries

#### Files to create:
- `src/components/Settings/AdminSettings/AdminRoleMappings.vue`
- `src/Api/modules/roleMappings.ts`

#### Files to modify:
- `lib/Controller/SettingsController.php`
- `appinfo/routes.php`
- `src/components/Settings/AdminSettings/index` (or parent component)

---

## 5. Migration Strategy

### 5.1 Backwards Compatibility

The migration creates default `oc_agora_role_mapping` entries matching the current hardcoded groups:

```sql
INSERT INTO oc_agora_role_mapping (role, group_id, group_type, scope_type, scope_id)
VALUES
  ('user',         'Agora Users',        'group', 'global', NULL),
  ('moderator',    'Agora Moderator',    'group', 'global', NULL),
  ('official',     'Agora Official',     'group', 'global', NULL),
  ('legislative',  'Agora Legislative',  'group', 'global', NULL),
  ('group_editor', 'Agora Group Editor', 'group', 'global', NULL);
```

Existing installations will behave identically after upgrade.

### 5.2 Group Name Inconsistency Fix

The current code has an inconsistency:
- `Group.php` uses: `'Agora Moderator'` (display name style)
- `User.php` uses: `'agora_moderator'` (internal ID style)

The `RoleMappingService` resolves this by looking up groups via `IGroupManager::get()`, which accepts the GID. The migration step should detect which naming convention the existing installation uses and create the mapping accordingly.

### 5.3 Deprecation Path

| Phase | Hardcoded Groups | RoleMappingService | PermissionService | Middleware |
|-------|------------------|--------------------|-------------------|------------|
| 1     | Still active     | Introduced (fallback to hardcoded) | Introduced | Not yet |
| 2     | Deprecated       | Primary            | Enforcing         | Active     |
| 3     | Removed          | Primary            | Enforcing         | Active     |

---

## 6. Specific Bug Fixes to Include

### 6.1 `hasGroupAccess()` default return value
**File:** `src/utils/permissions.ts:599`
**Bug:** Returns `false` when there are no group restrictions, which incorrectly denies access to unrestricted content.
**Fix:** Return `true` when `context.hasGroupRestrictions` is `false`.

### 6.2 Single-role limitation in `getRole()`
**File:** `lib/Model/User/User.php:65`
**Bug:** Returns only the first matching role, ignoring additional roles.
**Fix:** Replace `getRole()` usage with `getRoles()` throughout, or better yet, use `RoleMappingService::getUserRoles()`.

### 6.3 `getCurrentUserType()` drops Group Editor to User
**File:** `src/utils/permissions.ts:226`
**Bug:** Group editors are returned as `UserType.User`, losing their elevated permissions.
**Fix:** Either add `UserType.GroupEditor` to the enum or support multi-role user type resolution.

### 6.4 Debug console.log statements
**File:** `src/utils/permissions.ts:363-364, 410`
**Bug:** Debug logging in production code.
**Fix:** Remove these lines.

---

## 7. Security Considerations

1. **Backend is authoritative:** The frontend permission data is for UI convenience only. All mutations are validated server-side by the middleware. A user cannot bypass permissions by manipulating the frontend.

2. **Permission caching:** Role lookups hit the database. Add short-lived (per-request) caching in `RoleMappingService` to avoid repeated DB queries for the same user within a single request.

3. **Admin-only configuration:** Role mapping CRUD endpoints must require admin authentication (`#[AdminRequired]` or equivalent).

4. **Audit logging:** When permissions are changed (role mappings added/removed), log the change with the admin user ID, timestamp, and what changed.

5. **Scope validation:** When creating scoped role mappings, validate that the scope target exists (e.g., the family or inquiry group actually exists in the database).

---

## 8. Testing Strategy

### Unit Tests
- `PermissionServiceTest`: Test every permission check with various role/state combinations
- `RoleMappingServiceTest`: Test role resolution with global and scoped mappings
- `PermissionMiddlewareTest`: Test that middleware correctly allows/denies based on attributes

### Integration Tests
- Create users with various group memberships
- Verify API endpoints return correct HTTP status codes (200 vs 403)
- Verify `permissions` objects in API responses reflect actual access

### Migration Tests
- Test upgrade from current version: verify default role mappings are created
- Test that existing users retain their permissions after migration
- Test that the group name inconsistency is handled correctly

---

## 9. Summary of Deliverables

| Deliverable                  | Type          | Phase |
|------------------------------|---------------|-------|
| `oc_agora_role_mapping` table | DB Migration  | 1     |
| `RoleMappingService`         | PHP Service   | 1     |
| `PermissionService`          | PHP Service   | 1     |
| Permissions in API responses  | API Change    | 1     |
| `RequirePermission` attribute | PHP Attribute | 2     |
| `PermissionMiddleware`        | Middleware    | 2     |
| Controller annotations        | Code Change   | 2     |
| Frontend permissions refactor  | TypeScript    | 3     |
| Bug fixes (6.1-6.4)          | Code Change   | 3     |
| Admin Role Mappings UI        | Vue Component | 4     |
| Role Mapping API endpoints    | API           | 4     |
| Scoped permission support     | Service       | 4     |
| Unit + Integration tests      | Tests         | 1-4   |
