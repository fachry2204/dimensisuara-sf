# Brainstorming: Unified Split Sheet for Release & Publishing

**Requirement:** Songwriter and composer can be reused between release and publishing. Split sheet should contain name, email, phone, LMK input, publisher. Terminology will use Author and Composer.

## Phase 3: DESIGN (Composition-Driven Architecture)

### 1. Database Schema
- **`compositions`**: `id`, `profileid`, `title`, `iswc`, `created_at`, `updated_at`.
- **`split_sheets`**: `id`, `composition_id`.
- **`split_sheet_entries`**: `id`, `split_sheet_id`, `name`, `email`, `phone`, `role` (Author / Composer), `share` (nullable), `lmk`, `publisher`.
- **`tracks`** (modified): add `composition_id` (Foreign Key). A Track belongs to ONE Composition. Remove old `composer`/`songwriter` fields.
- **`publishings`** (modified): add `composition_id`.

*(Note: For legacy data, we run a migration to create a `Composition` for every existing track based on its `title`, and move the old `composer`/`songwriter` string fields into `split_sheet_entries`.)*

### 2. Solving the Frontend "Unsaved Composition" Issue
The frontend is built with Blade and Alpine.js. The `release` store (`Alpine.store('release')`) can perfectly handle the frontend draft state for multiple tracks sharing one unsaved composition.

**Frontend Solution (State Management):**
Instead of relying on database IDs during the draft phase, we use a local **Composition Array** in the Alpine store.
1. Add `compositions: []` to `Alpine.store('release')`.
2. The user creates "Composition A" in the Track 1 modal. The frontend pushes it to `compositions` array.
3. Track 1 state saves a reference: `composition_ref: 0` (the array index or a local UUID).
4. In Track 2's modal, the "Select Composition" dropdown reads from TWO sources: the API (previously saved compositions) AND the local `compositions` array.
5. User selects "Composition A" for Track 2. Track 2 state saves `composition_ref: 0`.

**Backend Solution (Service Layer):**
When the Release payload is submitted:
1. The backend loops through the `compositions` array in the payload and saves them to the database, keeping track of their new IDs (e.g., `ref 0 -> ID 50`).
2. The backend loops through the `tracks`. If a track has `composition_ref: 0`, it assigns `composition_id = 50`. 

### 3. Publishing Data Flow
When a user clicks "Create Publishing" for a Track:
1. The system checks the Track's `composition_id`.
2. It creates a `Publishing` record linked to that `composition_id`.
3. **Legal Data Gap**: Since the `split_sheet_entries` only have basic info (email, phone), the Publishing module will act as a "Verification" step to collect KTP, NPWP, and e-signatures required for the contract.

### AFFECTED USE CASES (consolidated):
- **MODIFIED:** Track creation (Release module)
- **MODIFIED:** Publishing request creation
- **NEW:** Composition management (CRUD for compositions & split sheets)

## Phase 4: ENHANCEMENTS INCLUDED
- **Total Share Validation**: Backend/frontend validation added to ensure the `share` percentages on a split sheet equal exactly 100% before a Publishing request can be submitted.
