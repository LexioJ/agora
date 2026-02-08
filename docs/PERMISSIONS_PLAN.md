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

### 1.2 Two Permission Dimensions Today

Agora already has **two independent permission dimensions** that operate in parallel:

**Dimension 1: Role-based (WHO can act)**
Determined by Nextcloud group membership. A user's role (moderator, official, etc.) grants broad capabilities like "can moderate inquiries" or "can create official responses."

**Dimension 2: Inquiry-type-based (WHAT is allowed on this content)**
Configured per inquiry type via `inquiryTypeRights` in `AppSettings`. Each inquiry type (`proposal`, `debate`, `petition`, etc.) defines what actions are available:
- `supportInquiry` -- whether supports/votes are enabled
- `supportFeature` -- what kind of support (`binary`, `ternary`, `none`)
- `commentInquiry` -- whether commenting is enabled
- `useResourceInquiry` -- whether attachments are allowed
- `editorType` -- what editor to show (`wysiwyg`, `textarea`)

**The 1.7-beta branch is extending this dimension** with new per-type fields on `InquiryType` entity:
- `allowed_response` -- which response types are valid
- `allowed_transformation` -- which transformations are valid
- `allowed_option_type` -- which option types are valid
- `support_feature` -- now stored per inquiry type at the DB level

These two dimensions are currently unrelated -- the role system doesn't know about inquiry type capabilities, and the type system doesn't know about roles. The `PermissionService` proposed in this plan must combine both.

### 1.3 Share-Based Access (Third Dimension)

A third, implicit permission path exists through **shares**. When a user accesses an inquiry, the backend resolves their access level via `Inquiry::getUserRole()` (`lib/Db/Inquiry.php:327`):

```
Share Type             → User Role         → Access Level
─────────────────────────────────────────────────────────
Owner                  → ROLE_OWNER         → Full control
Personal share (user)  → ROLE_USER          → Participate (comment, support)
Personal share (admin) → ROLE_ADMIN         → Delegated admin
Group share (member)   → ROLE_USER          → Participate
Email share            → ROLE_EMAIL         → Limited external
Public token           → (no role)          → View-only (configurable)
Open inquiry (logged in)→ ROLE_USER         → Participate
No share, not open     → ROLE_NONE          → No access
```

This resolution happens **entirely inside the Inquiry entity** (`lib/Db/Inquiry.php`), mixing data access with authorization logic. The `PermissionService` must take over this responsibility.

### 1.4 Public/Anonymous User Path

Users arriving via **public share tokens** bypass the Nextcloud authentication path entirely:
- `UserSession::getCurrentUser()` returns a share-derived user (Email, Contact, or Ghost type)
- They have **no Nextcloud groups**, therefore **no Agora roles**
- Their permissions come exclusively from the share configuration and inquiry state
- `getAllowCommenting()` and `getAllowSupport()` in `Inquiry.php` explicitly block public share users from commenting

Currently this path is handled by scattered `$this->userSession->getShare()->getType() === 'public'` checks throughout the entity model. The `PermissionService` needs an explicit access path for unauthenticated/token-based users.

### 1.5 Identified Problems

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

#### P8: `getAllowEditInquiry()` missing return and broken access check
`Inquiry.php:626-647`: The method has **no `return false;`** fallback -- PHP returns `null` which is falsy, but this is fragile and violates the `bool` return type. Worse, line 640 checks `if ($this->getAccess())` which evaluates to `true` for **any** non-empty access string, including `'private'`. This means any inquiry with an access level set (which is all of them) passes the edit check.

#### P9: `isGroupeEditor` typo in serialization
`UserBase.php:498`: `getSimpleUserArray()` uses key `'isGroupeEditor'` (typo -- extra 'e') while `getRichUserArray()` on line 457 uses `'isGroupEditor'` (correct). When a non-current user is serialized, the frontend receives `isGroupeEditor: false` while looking for `isGroupEditor`, so the group-editor flag is silently lost for other users.

#### P10: Inquiry-type capabilities not enforced on backend
The `inquiryTypeRights` (support, comment, resource toggles per type) are read by the frontend but **not enforced by the backend**. The backend's `getAllowCommenting()` checks `$this->getAllowComment()` (the per-inquiry flag) but never checks whether the inquiry's type allows commenting at all. A crafted API request could bypass type-level restrictions.

---

## 2. Design Goals

1. **Use existing Nextcloud groups and teams** as the primary mechanism for role assignment -- no custom user management UI
2. **Support multi-role users** (a user can be Moderator + Official simultaneously)
3. **Enable scoped permissions** (moderator of a specific group/category, not just global)
4. **Enforce permissions on the backend** with a single authoritative source -- the frontend should query the backend, not compute its own parallel logic
5. **Make role-to-group mapping configurable** by admins, not hardcoded
6. **Unify the three permission dimensions** (role, inquiry-type capabilities, share access) into a single resolver
7. **Handle all user types** including public/anonymous token-based access
8. **Support efficient bulk permission resolution** for list views
9. **Maintain backwards compatibility** during migration
10. **Keep it simple** -- leverage Nextcloud infrastructure rather than building a custom RBAC engine

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
          +---------------------+---------------------+
          |                     |                     |
+---------v---------+ +---------v---------+ +---------v---------+
| Dim 1: Role       | | Dim 2: Type       | | Dim 3: Share      |
| (WHO can act)     | | (WHAT is allowed) | | (HOW they got in) |
+---------+---------+ +---------+---------+ +---------+---------+
          |                     |                     |
          +---------------------+---------------------+
                                |
                    +-----------v-----------+
                    |  Permission Resolver  |  <-- Single authoritative engine
                    |  combines all 3 dims  |
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

A new centralized service that replaces the scattered permission logic and **unifies all three permission dimensions**.

#### `lib/Service/PermissionService.php`

```php
class PermissionService {

    // ── Core API ──────────────────────────────────────────
    can(string $userId, string $permission, PermissionContext $ctx): bool

    // ── Inquiry-level checks ──────────────────────────────
    canViewInquiry(string $userId, Inquiry $inquiry): bool
    canEditInquiry(string $userId, Inquiry $inquiry): bool
    canDeleteInquiry(string $userId, Inquiry $inquiry): bool
    canModerateInquiry(string $userId, Inquiry $inquiry): bool
    canCommentOnInquiry(string $userId, Inquiry $inquiry): bool
    canSupportInquiry(string $userId, Inquiry $inquiry): bool
    canShareInquiry(string $userId, Inquiry $inquiry): bool
    canCreateInquiryInFamily(string $userId, string $family): bool

    // ── Group-level checks ────────────────────────────────
    canCreateInquiryGroup(string $userId): bool
    canModifyInquiryGroup(string $userId, InquiryGroup $group): bool
    canDeleteInquiryGroup(string $userId, InquiryGroup $group): bool

    // ── Bulk resolution (for list views) ──────────────────
    getPermissionsForInquiry(string $userId, Inquiry $inquiry): array
    getPermissionsForInquiries(string $userId, array $inquiries): array
    getPermissionsForInquiryGroup(string $userId, InquiryGroup $group): array
    getMenuPermissions(string $userId): array

    // ── Share/token-based resolution ──────────────────────
    canViewViaToken(string $token, Inquiry $inquiry): bool
    getPermissionsForToken(string $token, Inquiry $inquiry): array
}
```

**The resolver combines all three dimensions:**

1. **Dimension 1 -- Role:** User's roles (from `RoleMappingService`) and role-based rights (from `AppSettings` -- moderator/official rights)
2. **Dimension 2 -- Type capabilities:** Inquiry type configuration from `InquiryType` entity (`supportFeature`, `allowedResponse`, `allowedOptionType`, etc.) and `inquiryTypeRights` from `AppSettings`
3. **Dimension 3 -- Share access:** How the user gained access (owner, personal share, group share, public token, open inquiry) which determines the base access level

#### Resolution logic (pseudocode):

```
canComment(userId, inquiry):
    # Dimension 3: Does the user have access at all?
    accessRole = resolveShareAccess(userId, inquiry)
    if accessRole == NONE → return false
    if accessRole is public-token → return false  (public can't comment)

    # Dimension 2: Does this inquiry TYPE allow commenting?
    typeRights = getInquiryTypeRights(inquiry.type)
    if typeRights.commentInquiry == false → return false

    # Dimension 1: Per-inquiry flag (owner-configured)
    if inquiry.allowComment == false → return false

    # State: Is the inquiry in a commentable state?
    if inquiry.isLocked or inquiry.isArchived or inquiry.isDeleted → return false

    return true
```

**This replaces:**
- `src/utils/permissions.ts` as the authoritative source (frontend becomes read-only consumer)
- `lib/Db/Inquiry.php::request()` / `getIsAllowed()` / `getUserRole()` (moved to service)
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

**For token-based access**, the middleware detects the `#[ShareTokenRequired]` attribute (already exists in the codebase) and switches to `PermissionService::getPermissionsForToken()` instead of user-based resolution.

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

#### Preserving the 1.7-beta groundwork

The 1.7-beta branch already introduced `InquiryStoreLike`, `OptionStoreLike`, and `InquiryGroupStoreLike` interfaces plus `createInquiryContext()` / `createOptionContext()` / `createInquiryGroupContext()` factory functions. These are useful structural improvements:

- **Keep the interfaces** -- they become the TypeScript types for server-provided data
- **Keep the factory functions** -- but simplify them to extract `permissions` from server data instead of computing them
- **Keep `PermissionContext`** -- but it becomes a thin container for the server-provided permission flags rather than input to a local computation engine
- **Remove** the complex `getTypeConfigPermissions()`, `getDisplayPermissions()`, `getEditPermissions()` chains -- these are replaced by server-side resolution

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

## 4. Relationship to 1.7-beta Work Already in Progress

The `release/1.7-beta` branch contains changes that are **directionally aligned** with this plan but don't yet implement the structural redesign. Here is how each in-progress change relates:

### 4.1 What 1.7-beta has done

| Change | Where | Relation to this plan |
|--------|-------|-----------------------|
| `InquiryStoreLike`, `OptionStoreLike`, `InquiryGroupStoreLike` interfaces | `permissions.ts` | **Aligned** -- these become the TypeScript shapes for server-provided data. Keep them. |
| `createInquiryContext()`, `createOptionContext()`, `createInquiryGroupContext()` factories | `permissions.ts` | **Aligned** -- keep but simplify once backend provides permissions. |
| `supportMode` renamed to `supportFeature` throughout | `AppSettings`, `Inquiry`, `permissions.ts` | **Aligned** -- the naming is better. No conflict. |
| `support_feature` column added to `agora_inquiries` and `agora_inq_type` tables | `TableSchema.php`, `InquiryType.php` | **Aligned** -- per-type capability at DB level is exactly what Dimension 2 needs. |
| `allowed_response`, `allowed_transformation`, `allowed_option_type` on `InquiryType` | `TableSchema.php`, `InquiryType.php` | **Aligned** -- more type-level capability flags. The `PermissionService` will read these. |
| `checkSettingType()` reverted to OCP `getValueType()` approach | `AppSettings.php` | **Aligned** -- cleaner implementation. No conflict. |
| Null-safe service calls (`?->findAll() ?? []`) | `AppSettings.php` | **Aligned** -- defensive coding. No conflict. |
| `getTypeConfigPermissions()` extracted in frontend | `permissions.ts` | **Will be replaced** -- this function duplicates what the backend should decide. |
| `console.log(" GET TYPE CONGIG IN PERMISSION ")` debug line | `permissions.ts` | **Must be removed** before any release. |

### 4.2 What 1.7-beta has NOT changed

- `User.php` role constants and hardcoded group checks -- **unchanged**
- `Group.php` display-name-style constants -- **unchanged**
- `Inquiry.php` `request()` / `getIsAllowed()` / `getUserRole()` -- **unchanged**
- No new permission middleware or attributes
- No backend enforcement for inquiry-type capabilities
- The `isGroupeEditor` typo in `UserBase.php` -- **unchanged**
- The `getAllowEditInquiry()` missing return / broken access check -- **unchanged**

### 4.3 Template System Interaction

The 1.7-beta branch introduces a **template system** (`TemplateController`, `TemplateLoader`, `TemplateCatalog`, template JSON files, `TemplateSetupWizard.vue`). Templates define pre-configured inquiry setups for specific use cases (citizen participation, education, enterprise HR, etc.).

**How templates relate to permissions:**
- Templates are a **creation-time** concern: they set initial configuration (inquiry type, fields, support mode) when an admin sets up the app or creates new inquiry structures
- The permission system is a **runtime** concern: it enforces access based on current state
- **No conflict** -- templates produce inquiries with type configurations, and the permission system enforces capabilities based on those types
- **One consideration**: templates may define inquiry types that don't exist in the default set. The `PermissionService` should gracefully handle unknown inquiry types by falling back to restrictive defaults rather than crashing

---

## 5. Implementation Plan

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
   - **Must combine all three dimensions**: role checks, inquiry-type capability checks, and share-based access resolution
   - Read `InquiryType` capabilities (`supportFeature`, `allowedResponse`, etc.) when resolving type-level permissions
   - Write unit tests for every permission check
   - Wire into DI container via `Application.php`

3. **Create DB migration for `oc_agora_role_mapping`**
   - New migration file
   - Include a repair step that seeds default mappings from the current hardcoded group names
   - Ensure existing installations get backwards-compatible defaults

4. **Add permissions to API responses**
   - Modify `Inquiry::jsonSerialize()` to include a `permissions` array
   - Modify `Option::jsonSerialize()` similarly (options inherit inquiry permissions + have own type checks)
   - Modify `InquiryGroup::jsonSerialize()` similarly
   - The `getPermissionsArray()` method in `Inquiry.php` already exists but delegates to `PermissionService`

5. **Add bulk permission resolution for list views**
   - `getPermissionsForInquiries(userId, inquiries[])` batches shared lookups:
     - Resolve user roles **once** (not per inquiry)
     - Batch-load inquiry type configurations
     - Resolve group memberships once, reuse across inquiries
   - This prevents N+1 DB queries when loading the inquiry list

#### Files to create:
- `lib/Service/RoleMappingService.php`
- `lib/Service/PermissionService.php`
- `lib/Db/RoleMapping.php`
- `lib/Db/RoleMappingMapper.php`
- `lib/Migration/Version010800Date[timestamp].php` (or next version after 1.7)
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
   - Detects whether the request is user-based or token-based (via existing `#[ShareTokenRequired]`) and routes to the appropriate `PermissionService` method

3. **Annotate all controllers**
   - `InquiryApiController`: add `#[RequirePermission]` to all mutating endpoints
   - `CommentApiController`: add for comment creation/deletion
   - `SupportApiController`: add for support creation/deletion
   - `ShareApiController`: add for share management
   - `AttachmentApiController`: add for attachment management
   - `InquiryGroupController`: add for group management
   - `OptionApiController` / `OptionController`: add for option management
   - `OfficialResponseController`: add for official response creation

4. **Enforce inquiry-type capabilities on backend**
   - When a comment is submitted, middleware checks `inquiryTypeRights[type].commentInquiry`
   - When a support is submitted, middleware checks `inquiryTypeRights[type].supportInquiry`
   - When an attachment is uploaded, middleware checks `inquiryTypeRights[type].useResourceInquiry`
   - This closes the gap where type-level restrictions were only enforced by the frontend

5. **Remove inline permission checks from services**
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
   - `stores/option.ts`: Same (new in 1.7-beta)
   - `stores/session.ts`: Store `menuPermissions` and `scopedRoles`

2. **Refactor `permissions.ts`**
   - Keep the file but simplify all functions to read from store data
   - **Keep** the `InquiryStoreLike`, `OptionStoreLike`, `InquiryGroupStoreLike` interfaces (from 1.7-beta) -- add a `permissions` field to each
   - **Keep** `createInquiryContext()` etc. but simplify to extract server permissions
   - **Remove** `getTypeConfigPermissions()`, `getDisplayPermissions()`, `getEditPermissions()` computation chains
   - **Remove** all the complex computation logic in `canSupport()`, `canComment()`, etc.
   - Functions become thin wrappers: `canEdit(inquiry) => inquiry.permissions.canEdit`

3. **Update Vue components**
   - Components already call functions like `canEdit(context)` -- the function signature stays the same but the implementation changes
   - Update `createInquiryContext()` to build context from server data
   - Menu visibility in `Navigation.vue`, `NavigationMenu.vue` uses `menuPermissions` from session

4. **Fix the bugs listed in Section 7**

#### Files to modify:
- `src/utils/permissions.ts`
- `src/stores/inquiry.ts`
- `src/stores/inquiryGroup.ts`
- `src/stores/option.ts`
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

## 6. Migration Strategy

### 6.1 Backwards Compatibility

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

### 6.2 Group Name Inconsistency Fix

The current code has an inconsistency:
- `Group.php` uses: `'Agora Moderator'` (display name style)
- `User.php` uses: `'agora_moderator'` (internal ID style)

The `RoleMappingService` resolves this by looking up groups via `IGroupManager::get()`, which accepts the GID. The migration step should detect which naming convention the existing installation uses and create the mapping accordingly.

### 6.3 Deprecation Path

| Phase | Hardcoded Groups | RoleMappingService | PermissionService | Middleware |
|-------|------------------|--------------------|-------------------|------------|
| 1     | Still active     | Introduced (fallback to hardcoded) | Introduced | Not yet |
| 2     | Deprecated       | Primary            | Enforcing         | Active     |
| 3     | Removed          | Primary            | Enforcing         | Active     |

### 6.4 Template System Compatibility

No migration needed for templates. Templates produce inquiry configurations that the `PermissionService` reads at runtime. However, if custom templates define non-standard inquiry types, the `PermissionService` should:
- Fall back to a restrictive default (`supportInquiry: false, commentInquiry: false`) for unknown types
- Log a warning so the admin can configure rights for the new type
- Allow the admin to add custom type rights via the existing `inquiryTypeRights` admin setting

---

## 7. Specific Bug Fixes to Include

### 7.1 `hasGroupAccess()` default return value
**File:** `src/utils/permissions.ts:599`
**Bug:** Returns `false` when there are no group restrictions, which incorrectly denies access to unrestricted content.
**Fix:** Return `true` when `context.hasGroupRestrictions` is `false`.

### 7.2 Single-role limitation in `getRole()`
**File:** `lib/Model/User/User.php:65`
**Bug:** Returns only the first matching role, ignoring additional roles.
**Fix:** Replace `getRole()` usage with `getRoles()` throughout, or better yet, use `RoleMappingService::getUserRoles()`.

### 7.3 `getCurrentUserType()` drops Group Editor to User
**File:** `src/utils/permissions.ts:226`
**Bug:** Group editors are returned as `UserType.User`, losing their elevated permissions. The code explicitly says `return UserType.User // Group editors are regular users with extra permissions` but then the `UserType.User` path doesn't have group-editor capabilities.
**Fix:** Either add `UserType.GroupEditor` to the enum or support multi-role user type resolution. Best resolved by moving to server-provided permissions where the backend already knows the user's full role set.

### 7.4 Debug console.log statements
**File:** `src/utils/permissions.ts` (line with `" GET TYPE CONGIG IN PERMISSION "`)
**Bug:** Debug logging in production code, added in 1.7-beta.
**Fix:** Remove these lines.

### 7.5 `isGroupeEditor` typo in serialization
**File:** `lib/Model/UserBase.php:498`
**Bug:** `getSimpleUserArray()` uses key `'isGroupeEditor'` (extra 'e'). The rich array on line 457 uses correct `'isGroupEditor'`. When a non-current user is serialized via the simple array, the frontend receives an unknown key, and `isGroupEditor` is missing. This silently breaks group-editor permission checks for other users in shared contexts.
**Fix:** Change `'isGroupeEditor'` to `'isGroupEditor'` in `getSimpleUserArray()`.

### 7.6 `getAllowEditInquiry()` missing return and broken access check
**File:** `lib/Db/Inquiry.php:626-647`
**Bugs (two in one method):**
1. **No `return false;` at end** -- PHP implicitly returns `null`, which is falsy but violates the declared `bool` return type.
2. **Line 640: `if ($this->getAccess())`** evaluates to `true` for any non-empty string, including `'private'` and `'hidden'`. Since every inquiry has an access level, this condition is almost always true, making the method return `true` for virtually everyone.

**Fix:** Add `return false;` at the end. Replace line 640 with a proper access-level check. This method should be superseded by `PermissionService::canEditInquiry()` in Phase 1, but the bug should be fixed immediately as it's a security issue.

### 7.7 Inquiry-type capabilities not enforced on backend
**File:** `lib/Db/Inquiry.php` (`getAllowCommenting()`, `getAllowSupporting()`)
**Bug:** These methods check the per-inquiry flag (`allowComment`, `allowSupport`) but never check whether the inquiry's **type** allows the action. A user could craft an API request to comment on an `official` type inquiry where `commentInquiry: false`.
**Fix:** In Phase 2, the `PermissionService` checks both the per-inquiry flag AND the type-level configuration. Until then, add type-level checks to the existing methods.

---

## 8. Performance Considerations

### 8.1 List View Bulk Resolution

The inquiry list can return dozens or hundreds of inquiries. Computing permissions per-inquiry would cause N+1 performance problems:

- **N role lookups** (querying `oc_agora_role_mapping` per inquiry)
- **N group membership checks** (`IGroupManager::isInGroup()` per inquiry)
- **N type configuration loads** (per inquiry type)

**Solution: `getPermissionsForInquiries(userId, inquiries[])`**

This bulk method:
1. Resolves user roles **once** via `RoleMappingService::getUserRoles(userId)` -- single DB query
2. Loads **all** inquiry type configurations in one query -- `InquiryTypeMapper::findAll()`
3. Resolves user's group memberships **once** -- `IGroupManager::getUserGroups(user)`
4. Iterates over inquiries using the pre-fetched data -- pure computation, no additional DB queries

### 8.2 Per-Request Caching

`RoleMappingService` caches role lookups for the duration of a single HTTP request:

```php
class RoleMappingService {
    private array $roleCache = [];  // userId -> roles[]

    public function getUserRoles(string $userId): array {
        if (!isset($this->roleCache[$userId])) {
            $this->roleCache[$userId] = $this->loadRolesFromDb($userId);
        }
        return $this->roleCache[$userId];
    }
}
```

Since services are instantiated per-request in Nextcloud's DI container, the cache is automatically cleared between requests.

### 8.3 Inquiry Type Configuration Caching

`InquiryType` configurations change rarely (only when admin edits settings). Cache them in `PermissionService` on first access per request:

```php
private ?array $typeConfigCache = null;

private function getTypeConfig(string $type): array {
    if ($this->typeConfigCache === null) {
        $this->typeConfigCache = [];
        foreach ($this->inquiryTypeMapper->findAll() as $t) {
            $this->typeConfigCache[$t->getInquiryType()] = $t;
        }
    }
    return $this->typeConfigCache[$type] ?? $this->getDefaultTypeConfig();
}
```

---

## 9. Security Considerations

1. **Backend is authoritative:** The frontend permission data is for UI convenience only. All mutations are validated server-side by the middleware. A user cannot bypass permissions by manipulating the frontend.

2. **Permission caching:** Role lookups hit the database. Add short-lived (per-request) caching in `RoleMappingService` to avoid repeated DB queries for the same user within a single request.

3. **Admin-only configuration:** Role mapping CRUD endpoints must require admin authentication (`#[AdminRequired]` or equivalent).

4. **Audit logging:** When permissions are changed (role mappings added/removed), log the change with the admin user ID, timestamp, and what changed.

5. **Scope validation:** When creating scoped role mappings, validate that the scope target exists (e.g., the family or inquiry group actually exists in the database).

6. **Token-based access isolation:** Public share tokens must never escalate to authenticated-user permissions. The middleware must treat token access as a distinct path that cannot be combined with role-based access.

7. **Unknown inquiry types:** When an inquiry type is not found in the configuration (e.g., created by a template), the `PermissionService` should default to **restrictive** permissions (deny comment, support, resource) rather than permissive. This follows the principle of least privilege.

8. **Fix P8 (`getAllowEditInquiry`) immediately:** This bug effectively grants edit access to anyone with access to any inquiry. It should be patched in the current release before Phase 1 is complete.

---

## 10. Testing Strategy

### Unit Tests
- `PermissionServiceTest`: Test every permission check with various role/state/type combinations
  - Test each dimension in isolation (role only, type only, share only)
  - Test dimension combinations (moderator + official type + group share)
  - Test edge cases (unknown type, expired inquiry, archived + moderator, etc.)
- `RoleMappingServiceTest`: Test role resolution with global and scoped mappings
- `PermissionMiddlewareTest`: Test that middleware correctly allows/denies based on attributes
- `BulkPermissionTest`: Test that `getPermissionsForInquiries()` returns identical results to individual calls

### Integration Tests
- Create users with various group memberships
- Verify API endpoints return correct HTTP status codes (200 vs 403)
- Verify `permissions` objects in API responses reflect actual access
- **Token-based access tests**: Verify public tokens get view-only permissions, cannot comment/support
- **Type capability tests**: Verify that a comment request on a `supportInquiry: false` type returns 403

### Migration Tests
- Test upgrade from current version: verify default role mappings are created
- Test that existing users retain their permissions after migration
- Test that the group name inconsistency is handled correctly

### Regression Tests
- Verify all 7.x bug fixes don't regress
- Verify `isGroupEditor` is consistently named in both serialization arrays
- Verify `getAllowEditInquiry()` correctly denies non-owners on private inquiries

---

## 11. Summary of Deliverables

| Deliverable                       | Type          | Phase |
|-----------------------------------|---------------|-------|
| `oc_agora_role_mapping` table     | DB Migration  | 1     |
| `RoleMappingService`              | PHP Service   | 1     |
| `PermissionService` (3-dim)       | PHP Service   | 1     |
| Bulk permission resolution        | PHP Service   | 1     |
| Permissions in API responses      | API Change    | 1     |
| `RequirePermission` attribute     | PHP Attribute | 2     |
| `PermissionMiddleware`            | Middleware    | 2     |
| Controller annotations            | Code Change   | 2     |
| Type capability backend enforce   | Middleware    | 2     |
| Token-based permission path       | Middleware    | 2     |
| Frontend permissions refactor     | TypeScript    | 3     |
| Bug fixes (7.1-7.7)              | Code Change   | 1-3   |
| Admin Role Mappings UI            | Vue Component | 4     |
| Role Mapping API endpoints        | API           | 4     |
| Scoped permission support         | Service       | 4     |
| Unit + Integration tests          | Tests         | 1-4   |

### Priority of bug fixes:

| Bug  | Severity | When to fix |
|------|----------|-------------|
| 7.6  | **Critical** -- security (edit bypass) | Immediately, before Phase 1 |
| 7.5  | High -- silent data loss | Phase 1 |
| 7.7  | High -- backend bypass | Phase 2 |
| 7.1  | Medium -- access denial | Phase 3 |
| 7.2  | Medium -- wrong role | Phase 1 |
| 7.3  | Medium -- lost capability | Phase 3 |
| 7.4  | Low -- debug noise | Phase 3 |
