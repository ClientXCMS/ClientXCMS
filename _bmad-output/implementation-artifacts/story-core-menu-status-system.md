# Story: Core Menu Status System

## Story Info
- **Epic:** Core Enhancements
- **Story ID:** core-menu-status-001
- **Status:** review
- **Created:** 2026-01-16
- **Priority:** Medium

## Description

Add a status system to MenuLink model allowing menu items to be set as `active`, `soon`, `maintenance`, or `disabled`. This enables administrators to manage menu visibility and behavior without removing items, with support for scheduled status changes and admin bypass.

## User Story

As an administrator,
I want to set a status (active, soon, maintenance, disabled) on menu items,
So that I can communicate service availability to visitors without removing navigation items.

## Acceptance Criteria

### Status Management

- [x] **AC-1:** MenuLink model supports `status` field with values: `active` (default), `soon`, `maintenance`, `disabled`
- [x] **AC-2:** MenuLink model supports `status_message` field for custom tooltip/message (translatable)
- [x] **AC-3:** MenuLink model supports `status_icon` field for custom status icon (optional)
- [x] **AC-4:** MenuLink model supports `status_starts_at` and `status_ends_at` datetime fields for scheduling
- [x] **AC-5:** Status is automatically applied based on scheduled dates (active outside date range)

### Hierarchy Propagation

- [x] **AC-6:** If a parent menu item has a non-active status, children inherit the visual styling
- [x] **AC-7:** Children can have their own status that takes precedence over parent's status
- [x] **AC-8:** `getEffectiveStatus()` method returns the status to display (considering hierarchy)

### Frontend Behavior

- [x] **AC-9:** Non-active menu items display with reduced opacity (0.5-0.6)
- [x] **AC-10:** Non-active menu items are non-clickable (href="#" or pointer-events: none)
- [x] **AC-11:** Non-active menu items show status badge (using existing badge field or dedicated badge)
- [x] **AC-12:** Tooltip on hover displays `status_message` if configured
- [x] **AC-13:** Status-specific icon displays if `status_icon` is configured

### Admin Bypass

- [x] **AC-14:** Admin users (auth('admin')->check()) see all menu items as normal (clickable)
- [x] **AC-15:** Admin users see a small indicator that item is in non-active status
- [x] **AC-16:** Bypass is hardcoded, not configurable

### Admin Interface

- [x] **AC-17:** Status dropdown in menu editor (front.blade.php, bottom.blade.php)
- [x] **AC-18:** Status message input field (translatable via existing overlay)
- [ ] **AC-19:** Date pickers for `status_starts_at` and `status_ends_at`
- [x] **AC-20:** UI follows existing menu editor patterns (inline, auto-save)

### Seeders

- [x] **AC-21:** Default status values configurable via theme seeders
- [x] **AC-22:** Migration includes sensible defaults (status = 'active')

---

## Technical Design

### Database Changes

**Migration: `2026_01_16_000001_add_status_to_menu_links.php`**

```php
Schema::table('theme_menu_links', function (Blueprint $table) {
    $table->string('status')->default('active'); // active, soon, maintenance, disabled
    $table->string('status_message')->nullable(); // Custom message (translatable via Translatable trait)
    $table->string('status_icon')->nullable(); // Custom icon class (e.g., bi bi-clock)
    $table->timestamp('status_starts_at')->nullable(); // When status becomes active
    $table->timestamp('status_ends_at')->nullable(); // When status ends (reverts to active)
});
```

### Model Changes

**File: `app/Models/Personalization/MenuLink.php`**

```php
// Add to $fillable
'status',
'status_message',
'status_icon',
'status_starts_at',
'status_ends_at',

// Add to $casts
'status_starts_at' => 'datetime',
'status_ends_at' => 'datetime',

// Add to $translatableKeys
'status_message' => 'text',

// Status constants
const STATUS_ACTIVE = 'active';
const STATUS_SOON = 'soon';
const STATUS_MAINTENANCE = 'maintenance';
const STATUS_DISABLED = 'disabled';

const STATUSES = [
    self::STATUS_ACTIVE,
    self::STATUS_SOON,
    self::STATUS_MAINTENANCE,
    self::STATUS_DISABLED,
];

// Get computed status based on schedule
public function getComputedStatus(): string
{
    $now = now();

    // If outside scheduled window, return active
    if ($this->status_starts_at && $now < $this->status_starts_at) {
        return self::STATUS_ACTIVE;
    }
    if ($this->status_ends_at && $now > $this->status_ends_at) {
        return self::STATUS_ACTIVE;
    }

    return $this->status ?? self::STATUS_ACTIVE;
}

// Get effective status considering parent hierarchy
public function getEffectiveStatus(): string
{
    $myStatus = $this->getComputedStatus();

    // If I have a non-active status, use it
    if ($myStatus !== self::STATUS_ACTIVE) {
        return $myStatus;
    }

    // Check parent's effective status
    if ($this->parent_id && $this->parent) {
        $parentStatus = $this->parent->getEffectiveStatus();
        if ($parentStatus !== self::STATUS_ACTIVE) {
            return $parentStatus;
        }
    }

    return self::STATUS_ACTIVE;
}

// Check if menu item should be interactive
public function isInteractive(): bool
{
    // Admin bypass
    if (auth('admin')->check()) {
        return true;
    }

    return $this->getEffectiveStatus() === self::STATUS_ACTIVE;
}

// Check if current user can bypass status restrictions
public function canBypassStatus(): bool
{
    return auth('admin')->check();
}

// Get status badge text
public function getStatusBadge(): ?string
{
    $status = $this->getEffectiveStatus();

    return match($status) {
        self::STATUS_SOON => __('personalization.menu_status.soon'),
        self::STATUS_MAINTENANCE => __('personalization.menu_status.maintenance'),
        self::STATUS_DISABLED => __('personalization.menu_status.disabled'),
        default => null,
    };
}

// Get status icon (custom or default)
public function getStatusIcon(): ?string
{
    if ($this->status_icon) {
        return $this->status_icon;
    }

    $status = $this->getEffectiveStatus();

    return match($status) {
        self::STATUS_SOON => 'bi bi-clock',
        self::STATUS_MAINTENANCE => 'bi bi-wrench',
        self::STATUS_DISABLED => 'bi bi-x-circle',
        default => null,
    };
}

// Get CSS classes for status styling
public function getStatusClasses(): string
{
    if ($this->canBypassStatus()) {
        $status = $this->getEffectiveStatus();
        if ($status !== self::STATUS_ACTIVE) {
            return 'ring-2 ring-yellow-500/50'; // Admin indicator
        }
        return '';
    }

    $status = $this->getEffectiveStatus();

    return match($status) {
        self::STATUS_SOON => 'opacity-60 cursor-not-allowed',
        self::STATUS_MAINTENANCE => 'opacity-50 cursor-not-allowed',
        self::STATUS_DISABLED => 'opacity-40 cursor-not-allowed pointer-events-none',
        default => '',
    };
}
```

### Translation Keys

**File: `resources/lang/en/personalization.php`**

```php
'menu_status' => [
    'label' => 'Status',
    'active' => 'Active',
    'soon' => 'Coming Soon',
    'maintenance' => 'Maintenance',
    'disabled' => 'Disabled',
    'message_placeholder' => 'Status message (optional)',
    'starts_at' => 'Starts at',
    'ends_at' => 'Ends at',
    'schedule_help' => 'Leave empty for immediate effect',
],
```

**File: `resources/lang/fr/personalization.php`**

```php
'menu_status' => [
    'label' => 'Statut',
    'active' => 'Actif',
    'soon' => 'Bientot disponible',
    'maintenance' => 'Maintenance',
    'disabled' => 'Desactive',
    'message_placeholder' => 'Message de statut (optionnel)',
    'starts_at' => 'Debut',
    'ends_at' => 'Fin',
    'schedule_help' => 'Laisser vide pour effet immediat',
],
```

---

## Tasks

### Phase 1: Database & Model

- [x] **TASK-1:** Create migration `2026_01_16_000001_add_status_to_menu_links.php`
- [x] **TASK-2:** Update MenuLink model with new fields, constants, and methods
- [x] **TASK-3:** Add translation keys for status labels

### Phase 2: Admin Interface

- [x] **TASK-4:** Update `front.blade.php` - add status dropdown and fields
- [x] **TASK-5:** Update `bottom.blade.php` - add status dropdown and fields
- [x] **TASK-6:** Update JavaScript auto-save to include new fields
- [x] **TASK-7:** Add status_message to translation overlays

### Phase 3: Frontend Rendering

- [x] **TASK-8:** Update `desktop-nav.blade.php` - apply status styling and behavior
- [x] **TASK-9:** Update `mega-menu.blade.php` - apply status styling and behavior
- [x] **TASK-10:** Update `mobile-drawer.blade.php` - apply status styling and behavior
- [x] **TASK-11:** Update footer menu rendering - apply status styling

### Phase 4: Controller & Validation

- [x] **TASK-12:** Update `MenuLinkRequest.php` - add validation for new fields
- [x] **TASK-13:** Add date validation (ends_at must be after starts_at if both set)

### Phase 5: Testing & Documentation

- [x] **TASK-14:** Test status scheduling (start/end dates)
- [x] **TASK-15:** Test hierarchy propagation
- [x] **TASK-16:** Test admin bypass
- [x] **TASK-17:** Test all menu types (front, bottom, footer)

---

## Implementation Notes

### Status Behavior Summary

| Status | Opacity | Clickable | Badge | Default Icon |
|--------|---------|-----------|-------|--------------|
| active | 100% | Yes | None | None |
| soon | 60% | No | "Coming Soon" | bi-clock |
| maintenance | 50% | No | "Maintenance" | bi-wrench |
| disabled | 40% | No | "Disabled" | bi-x-circle |

### Admin Bypass Visual

When admin is logged in and viewing a non-active menu item:
- Item remains fully clickable
- Yellow ring indicator shows item has non-active status
- Hover tooltip still shows status message

### Scheduling Logic

```
if (status_starts_at is set AND now < status_starts_at):
    effective_status = 'active'  # Not started yet
elif (status_ends_at is set AND now > status_ends_at):
    effective_status = 'active'  # Already ended
else:
    effective_status = status  # Within scheduled window
```

---

## Dependencies

- MenuLink model (existing)
- Menu admin views (existing)
- Frontend menu components (existing)
- Translatable trait (existing)

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Performance with hierarchy check | Medium | Cache effective status, limit depth |
| Breaking existing menus | High | Default status = 'active', backward compatible |
| Complex scheduling edge cases | Low | Clear documentation, simple logic |

---

## Dev Agent Record

### Implementation Log

| Date | Action | Notes |
|------|--------|-------|
| 2026-01-16 | Story created | Ready for implementation |
| 2026-01-16 | Implementation complete | All tasks completed except AC-19 (date pickers for scheduling) |

### Files Modified

- `database/migrations/2026_01_16_000001_add_status_to_menu_links.php` - Created migration
- `app/Models/Personalization/MenuLink.php` - Added status fields, methods, and constants
- `app/Http/Requests/Personalization/MenuLinkRequest.php` - Added validation rules
- `lang/en/personalization.php` - Added menu_status translation keys
- `lang/fr/personalization.php` - Added menu_status translation keys
- `resources/views/admin/personalization/settings/front.blade.php` - Added status dropdown and overlay updates
- `resources/views/admin/personalization/settings/bottom.blade.php` - Added status dropdown and overlay updates
- `resources/themes/cerbonix/views/includes/header/desktop-nav.blade.php` - Added status styling
- `resources/themes/cerbonix/views/includes/header/mega-menu.blade.php` - Added status styling
- `resources/themes/cerbonix/views/includes/header/mobile-drawer.blade.php` - Added status styling
- `resources/themes/cerbonix/views/includes/footer.blade.php` - Added status styling

### Known Limitations

- **AC-19 not implemented:** Date pickers for `status_starts_at` and `status_ends_at` are not added to the admin UI. The fields exist in the database and model, but the UI only supports the status dropdown. Scheduling can be done via database/tinker for now.

