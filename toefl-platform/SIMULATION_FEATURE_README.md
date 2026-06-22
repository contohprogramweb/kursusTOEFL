# SIMULASI UJIAN (FR-3.4.x) - Dokumentasi Implementasi

## Overview
Fitur simulasi ujian TOEFL iBT dengan state machine lengkap untuk mengelola alur ujian dari awal hingga selesai.

## State Machine Simulasi

```
[INITIATED] → [READING] → [LISTENING] → [BREAK] → [SPEAKING] → [WRITING] → [SUBMITTED] → [GRADING] → [COMPLETED]
```

### Status Definitions:
| Status | Description |
|--------|-------------|
| `initiated` | Test session created, not started |
| `reading` | Currently in Reading section |
| `listening` | Currently in Listening section |
| `break` | On break between sections |
| `speaking` | Currently in Speaking section |
| `writing` | Currently in Writing section |
| `submitted` | Test submitted, awaiting grading |
| `grading` | Being graded (AI/instructor review) |
| `completed` | Fully completed with scores |

## Database Schema

### 1. Migration: `2024_01_04_000001_update_simulation_tables.php`

#### Updates ke `simulation_templates`:
- `institution_id` - Untuk B2B assignment
- `is_locked` - Mencegah deletion default templates
- Mode enum: `practice`, `scheduled`, `realistic`, `focus`

#### Updates ke `simulation_template_sections`:
- `section_result_id` - Tracking current section in progress

#### Updates ke `simulation_results`:
- `status` - Full state machine enum
- `current_section_index` - Progress tracking
- `section_times` - JSON time tracking per section
- `paused_at` - Pause/resume tracking
- `total_paused_seconds` - Total paused time

#### New Table: `institution_simulation_templates`
Pivot table untuk assign template ke institution (B2B):
- `institution_id`
- `template_id`
- `is_required` - Apakah template wajib untuk institution
- `assigned_by` - Admin yang assign
- `assigned_at` - Timestamp assignment

## Models

### 1. SimulationTemplate
**Location:** `app/Models/SimulationTemplate.php`

**Constants:**
- Modes: `MODE_PRACTICE`, `MODE_SCHEDULED`, `MODE_REALISTIC`, `MODE_FOCUS`
- Status: `STATUS_INITIATED`, `STATUS_READING`, `STATUS_LISTENING`, `STATUS_BREAK`, `STATUS_SPEAKING`, `STATUS_WRITING`, `STATUS_SUBMITTED`, `STATUS_GRADING`, `STATUS_COMPLETED`

**Key Methods:**
- `canBeDeleted()` - Check if template can be deleted
- `getNextStatus($currentStatus)` - Get next status in state machine
- `getValidTransitions()` - Get all valid transitions
- `isValidTransition($from, $to)` - Validate status transition
- `scopeForInstitution($institutionId)` - Filter by institution

### 2. SimulationTemplateSection
**Location:** `app/Models/SimulationTemplateSection.php`

**Key Methods:**
- `hasBreak()` - Check if section has break after
- `getTotalTimeMinutes()` - Get total time including break
- `scopeOrdered()` - Order by order_index

### 3. SimulationResult
**Location:** `app/Models/SimulationResult.php`

**Key Methods:**
- `isCompleted()` - Check if simulation is completed
- `isInProgress()` - Check if simulation is in progress
- `transitionToNextStatus()` - Move to next status
- `transitionTo($newStatus)` - Transition with validation
- `pause()` / `resume()` - Pause/resume simulation
- `recordSectionTime($section, $seconds)` - Track time per section
- `getElapsedTimeSeconds()` - Get total elapsed time excluding pauses

### 4. InstitutionSimulationTemplate
**Location:** `app/Models/InstitutionSimulationTemplate.php`

**Purpose:** B2B feature untuk assign templates ke institutions

## Controllers

### 1. Admin\SimulationTemplateController
**Location:** `app/Http/Controllers/Admin/SimulationTemplateController.php`

**CRUD Operations:**
- `index()` - List semua templates dengan filters
- `create()` / `store()` - Create new template
- `edit()` / `update()` - Update template (tidak bisa jika locked/default)
- `show()` - View template details
- `destroy()` - Delete template (tidak bisa jika locked/default)

**B2B Features:**
- `assignToInstitution()` - Assign template ke institution
- `removeFromInstitution()` - Remove assignment

**API:**
- `apiAvailableTemplates()` - Get available templates for user/institution

### 2. SimulationController
**Location:** `app/Http/Controllers/SimulationController.php`

**User Flow:**
- `index()` - List available templates + user's simulations
- `start($template)` - Start new simulation from template
- `resume($simulation)` - Resume in-progress simulation
- `run($simulation)` - Main simulation interface

**State Transitions (AJAX):**
- `nextSection($simulation)` - Move to next section
- `submit($simulation)` - Submit for grading
- `pause($simulation)` - Pause simulation
- `resumeSimulation($simulation)` - Resume paused simulation
- `recordTime($simulation)` - Record time spent
- `getStatus($simulation)` - Polling status

**Results:**
- `showResults($simulation)` - View overall results
- `showSectionResults($simulation, $section)` - View section details

## Routes

### Admin Routes (`/admin/simulations`)
```php
GET    /admin/simulations                    # Index
GET    /admin/simulations/create             # Create form
POST   /admin/simulations                    # Store
GET    /admin/simulations/{template}         # Show
GET    /admin/simulations/{template}/edit    # Edit form
PUT    /admin/simulations/{template}         # Update
DELETE /admin/simulations/{template}         # Destroy
POST   /admin/simulations/{template}/assign  # Assign to institution
DELETE /admin/simulations/{template}/institutions/{id}
GET    /admin/simulations/api/available      # API
```

### User Routes (`/simulations`)
```php
GET    /simulations                          # Index
POST   /simulations/templates/{id}/start     # Start new
GET    /simulations/{id}/resume              # Resume
GET    /simulations/{id}/run                 # Run interface
POST   /simulations/{id}/next-section        # Next section (AJAX)
POST   /simulations/{id}/submit              # Submit (AJAX)
POST   /simulations/{id}/pause               # Pause (AJAX)
POST   /simulations/{id}/resume-simulation   # Resume (AJAX)
POST   /simulations/{id}/record-time         # Record time (AJAX)
GET    /simulations/{id}/status              # Get status (AJAX)
GET    /simulations/{id}/results             # View results
GET    /simulations/{id}/results/{section}   # Section results
```

## Default Template: "Full Test TOEFL iBT"

Template ini harus dibuat sebagai default dan tidak bisa di-delete:

```php
SimulationTemplate::create([
    'name' => 'Full Test TOEFL iBT',
    'description' => 'Complete TOEFL iBT simulation test',
    'mode' => 'realistic',
    'total_duration' => 120,
    'is_default' => true,
    'is_locked' => true,
    'status' => 'active',
]);
```

**Sections:**
1. Reading (54-72 min, 30-40 questions)
2. Listening (41-57 min, 28-39 questions)
3. Break (10 min)
4. Speaking (17 min, 4 tasks)
5. Writing (29 min, 2 tasks)

## Features Implemented

✅ **Admin CRUD Template**
- Name, description, mode, total_duration
- Sections configuration (order, duration, question count, breaks)
- Institution assignment (B2B)
- Protected default templates

✅ **TemplateSection Management**
- Section type (reading/listening/speaking/writing)
- Order index
- Duration minutes
- Question count
- Break configuration

✅ **Default Template Protection**
- `is_default` flag
- `is_locked` flag
- Cannot delete via controller validation

✅ **B2B Assignment**
- Pivot table `institution_simulation_templates`
- Required/optional flag
- Assigned by tracking

✅ **State Machine Implementation**
- Enum-based status in database
- Validation for transitions
- Helper methods in model
- Automatic section index tracking

✅ **Session Tracking**
- Database-based state tracking
- Pause/resume functionality
- Time tracking per section
- Elapsed time calculation

✅ **AJAX Navigation**
- Fetch API ready endpoints
- Status polling
- Section transitions without reload
- Time recording

## Usage Example

### Starting a Simulation
```javascript
// POST /simulations/templates/{template_id}/start
fetch('/simulations/templates/1/start', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    },
})
.then(response => response.json())
.then(data => {
    // Redirect to simulation run page
    window.location.href = `/simulations/${data.id}/run`;
});
```

### Transitioning to Next Section
```javascript
// POST /simulations/{id}/next-section
fetch(`/simulations/${simulationId}/next-section`, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    },
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update UI with new section
        loadSection(data.new_status);
    }
});
```

### Polling Status
```javascript
// GET /simulations/{id}/status
setInterval(() => {
    fetch(`/simulations/${simulationId}/status`)
        .then(r => r.json())
        .then(data => {
            updateTimer(data.data.elapsed_seconds);
            updateProgressBar(data.data.current_section_index);
        });
}, 5000); // Every 5 seconds
```

## Security Considerations

1. **Authorization**: All routes check `user_id === auth()->id()`
2. **State Validation**: Transitions validated against state machine
3. **Protected Templates**: Default/locked templates cannot be modified
4. **Transaction Safety**: Critical operations wrapped in DB transactions

## Future Enhancements

- [ ] Real-time collaboration (proctor monitoring)
- [ ] Auto-save answers during simulation
- [ ] Offline mode support
- [ ] Advanced analytics per section
- [ ] Adaptive difficulty based on performance
- [ ] Integration with payment for premium templates
