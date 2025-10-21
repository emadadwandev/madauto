● 📋 Comprehensive Implementation Plan

  Based on your requirements, I'll implement a complete Multi-Location Menu Management System with full modifier support. This is a
  significant enhancement that will transform the integration into a full-fledged menu management platform.

  🎯 Feature Breakdown

  1. Multi-Location Management

  - Location Model with:
    - Address details (street, city, postal code)
    - Connected platforms (Careem, Talabat, both)
    - Opening hours (configurable per day)
    - Busy mode toggle (pause orders)
    - Active/inactive status
  - Location Dashboard:
    - Add/edit/delete locations
    - Toggle busy mode
    - View location-specific analytics
    - Assign menus to locations

  2. Menu Management System

  - Menu Model with:
    - Name, description, image
    - Active/inactive status
    - Platform assignments (one menu → multiple platforms)
    - Location assignments (one menu → multiple locations)
    - Publishing status (draft, published)
  - MenuItem Model with:
    - Name, description, image
    - Default quantity, price, tax
    - SKU mapping to Loyverse
    - Category/grouping
    - Availability settings
  - Menu Dashboard:
    - Create/edit/delete menus
    - Drag-and-drop item ordering
    - Bulk import/export
    - Menu preview
    - Publish to platforms

  3. Modifier System

  - Modifier Model:
    - Name, price adjustment
    - Loyverse modifier ID mapping
    - Active status
  - ModifierGroup Model:
    - Name, selection rules (single/multiple)
    - Min/max selections
    - Required/optional
  - MenuItem ↔ ModifierGroup relationship
  - Enhanced Order Processing:
    - Map incoming order modifiers to Loyverse modifier IDs
    - Apply modifier pricing correctly
    - Handle modifier groups in receipts

  📊 Database Schema Design

  -- LOCATIONS TABLE
  locations:
    - id
    - tenant_id (FK)
    - name
    - address_line1, address_line2, city, state, postal_code, country
    - platforms (JSON: ['careem', 'talabat'])
    - opening_hours (JSON: {monday: {open: '09:00', close: '22:00'}, ...})
    - is_busy (boolean)
    - is_active (boolean)
    - metadata (JSON)
    - created_at, updated_at

  -- MENUS TABLE
  menus:
    - id
    - tenant_id (FK)
    - name
    - description
    - image_url
    - status (draft/published)
    - is_active (boolean)
    - published_at
    - created_at, updated_at

  -- MENU_ITEMS TABLE
  menu_items:
    - id
    - menu_id (FK)
    - tenant_id (FK)
    - name
    - description
    - image_url
    - sku
    - default_quantity
    - price
    - tax_rate
    - loyverse_item_id
    - loyverse_variant_id
    - category
    - sort_order
    - is_active (boolean)
    - created_at, updated_at

  -- MODIFIERS TABLE
  modifiers:
    - id
    - tenant_id (FK)
    - name
    - price_adjustment (decimal)
    - loyverse_modifier_id
    - is_active (boolean)
    - created_at, updated_at

  -- MODIFIER_GROUPS TABLE
  modifier_groups:
    - id
    - tenant_id (FK)
    - name
    - selection_type (single/multiple)
    - min_selections
    - max_selections
    - is_required (boolean)
    - is_active (boolean)
    - created_at, updated_at

  -- PIVOT: menu_item_modifier_group
    - menu_item_id (FK)
    - modifier_group_id (FK)
    - sort_order

  -- PIVOT: modifier_group_modifier
    - modifier_group_id (FK)
    - modifier_id (FK)
    - sort_order

  -- PIVOT: menu_platform (menu assignments)
    - menu_id (FK)
    - platform (careem/talabat)
    - published_at
    - sync_status

  -- PIVOT: menu_location (menu assignments)
    - menu_id (FK)
    - location_id (FK)
    - is_active (boolean)

  🔄 Key Workflows

  Menu Publishing Flow:
  1. Create Menu → Add Items → Assign Modifiers
  2. Select Target Platforms (Careem/Talabat)
  3. Select Target Locations (Store A, B, C)
  4. Click "Publish"
  5. System syncs menu to platform APIs
  6. Menu becomes available on platform

  Order Processing with Modifiers:
  1. Webhook receives order with modifiers
  2. OrderTransformerService maps:
     - MenuItem → Loyverse Item ID
     - Modifiers → Loyverse Modifier IDs
  3. Build Loyverse receipt with:
     - line_items with proper modifier arrays
     - Modifier pricing included
  4. Sync to Loyverse POS

  🚀 Implementation Phases

  Phase 1: Database & Models (3-4 hours)
  - Create all migrations
  - Build models with relationships
  - Add tenant scoping

  Phase 2: Location Management (2-3 hours)
  - Location CRUD controller
  - Location management UI
  - Opening hours editor
  - Busy mode toggle

  Phase 3: Menu & MenuItem Management (4-5 hours)
  - Menu CRUD controller
  - MenuItem CRUD with image upload
  - Menu builder UI
  - Menu preview

  Phase 4: Modifier System (3-4 hours)
  - Modifier & ModifierGroup CRUD
  - Modifier group assignment UI
  - Modifier selection rules

  Phase 5: Menu Publishing (3-4 hours)
  - Platform sync service
  - Location assignment
  - Publishing workflow
  - Sync status tracking

  Phase 6: Enhanced Order Processing (2-3 hours)
  - Update OrderTransformerService
  - Modifier ID mapping
  - Testing with real orders

  Total Estimated Time: 17-23 hours
