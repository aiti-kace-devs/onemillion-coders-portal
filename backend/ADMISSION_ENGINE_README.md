# Chainable Admission Engine

## Overview

A sophisticated, pipeline-based admission system for Laravel that uses the **Strategy Pattern** and **Laravel Pipeline** to create flexible, reusable admission rules. This engine allows admins to configure custom admission pipelines for programmes and courses through an intuitive Backpack admin interface.

## Architecture

### Core Components

#### 1. **Admission Rules (Strategy Pattern)**
- **Location**: `app/Services/AdmissionRules/`
- **Interface**: `AdmissionRuleInterface`
- **7 Predefined Rules**:
  1. **PassMark** - Filters by minimum exam score (hard filter, priority 0)
  2. **CompletedExam** - Ensures exam completion and submission
  3. **AppliedBefore** - Filters/prioritizes by application date
  4. **SortByDate** - First-come, first-served sorting
  5. **GenderQuota** - Ensures minimum gender representation
  6. **AgeRange** - Filters by age constraints
  7. **EducationalLevel** - Sorts by educational hierarchy

#### 2. **Admission Service**
- **Location**: `app/Services/AdmissionService.php`
- **Responsibilities**:
  - Preview admission (dry-run mode)
  - Execute admission with pipeline
  - Batch processing
  - Email queuing via jobs
  - Statistics calculation

#### 3. **Statistics Service**
- **Location**: `app/Services/AdmissionStatisticsService.php`
- **Features**:
  - Caches admission statistics (24-hour TTL)
  - Auto-invalidation after admission runs
  - Manual cache refresh endpoint

#### 4. **Event-Driven Auto-Replacement**
- **Event**: `App\Events\AdmissionRejected`
- **Listener**: `App\Listeners\ReplaceRejectedAdmission`
- **Behavior**: When a student rejects admission, the next eligible student is automatically admitted

#### 5. **Backpack Admin UI**
Three CRUD interfaces:
- **Admission Rules** (`/admin/admission-rule`) - Manage rule definitions
- **Rule Pipeline** (`/admin/rule-pipeline`) - Assign rules to programmes/courses with priorities
- **Admission History** (`/admin/admission-run`) - View past runs with detailed statistics

## Database Schema

### New Tables

1. **`rules`**
   - `id`, `name`, `rule_class_path`, `description`
   - `default_parameters` (JSON), `is_active`

2. **`rule_assignments`** (Polymorphic Pivot)
   - `rule_id`, `ruleable_type`, `ruleable_id`
   - `value` (JSON - override parameters), `priority`

3. **`admission_runs`**
   - `course_id`, `batch_id`, `run_by`
   - `rules_applied` (JSON), `selected_count`, `admitted_count`
   - `emailed_count`, `accepted_count`, `rejected_count`
   - `manual_count`, `automated_count`, `status`

### Modified Tables

1. **`users`**
   - Added: `educational_level` (string)

2. **`courses`**
   - Added: `auto_admit_on` (date), `auto_admit_limit` (int)
   - `auto_admit_enabled` (bool), `last_auto_admit_at` (timestamp)

3. **`user_admission`**
   - Added: `admission_source` (enum: 'automated', 'manual')

## Usage

### 1. Setup Rules (Admin Panel)

```
/admin/admission-rule
```
- View pre-seeded rules
- Create custom rules by specifying a class path
- Configure default parameters as JSON

### 2. Build Pipeline (Admin Panel)

```
/admin/rule-pipeline
```
- Select Programme or Course
- Attach rules with priority (0 = highest)
- Override default parameters per assignment
- Example Pipeline:
  ```
  Priority 0: PassMark ({"pass_mark": 70})
  Priority 1: CompletedExam
  Priority 2: SortByDate ({"direction": "asc"})
  Priority 3: GenderQuota ({"gender": "female", "min_count": 20})
  ```

### 3. Run Automated Admission

#### Via Admin Panel
```
/admin/admission/run
```
- Select Course & Batch
- Preview selected students
- Execute admission

#### Via Console (Interactive)
```bash
php artisan admission:auto-admit
```
Prompts for:
- Branch → Centre → Course selection
- Student limit

#### Via Console (Direct)
```bash
php artisan admission:auto-admit {course_id} --limit=50 --batch-id=1
```

#### Dry Run
```bash
php artisan admission:auto-admit --dry-run --limit=30
```

### 4. Scheduled Auto-Admission

The scheduler runs daily at midnight (`00:00`) and automatically admits students for courses where:
- `auto_admit_enabled = true`
- `auto_admit_on <= today`
- No admission run today (`last_auto_admit_at < today`)

**Configure in Course CRUD**:
```php
$course->update([
    'auto_admit_enabled' => true,
    'auto_admit_on' => '2026-03-01',
    'auto_admit_limit' => 50,
]);
```

### 5. Statistics & Caching

#### Get Statistics
```php
$statsService = app(AdmissionStatisticsService::class);
$stats = $statsService->getStatistics($course, $batch);

// Returns:
// [
//     'total_admitted' => 50,
//     'manual_admissions' => 5,
//     'automated_admissions' => 45,
//     'emails_sent' => 48,
//     'accepted' => 35,
//     'rejected' => 10,
//     'pending' => 5,
//     'gender_breakdown' => ['male' => 28, 'female' => 22],
//     'avg_exam_score' => 78.5,
//     'last_updated' => '2026-02-09 12:34:56'
// ]
```

#### Refresh Cache
```php
$statsService->refreshCache($course, $batch);
```

Cache auto-invalidates after:
- New admission run (automated or manual)
- Manual admission via admin panel

## How Rules Work

### Rule Interface

Every rule implements:
```php
public function apply(Builder $query, $value, Closure $next): Builder;
```

### Pipeline Execution

The `AdmissionService` uses Laravel's `Pipeline` to chain rules:
```php
User::query()
    ->where('registered_course', $course_id)
    ->whereDoesntHave('admission')
    -> [PassMark Filter]
    -> [CompletedExam Filter]
    -> [SortByDate Ordering]
    -> [GenderQuota Prioritization]
    -> limit($limit)
    -> get();
```

### Fallback Logic

- If a **Course** has no rules assigned, it inherits rules from its **Programme**
- Implemented in `Course::getEffectiveRules()`

## API Endpoints (Admin Only)

### Preview Admission
```http
POST /admin/admission/preview
Content-Type: application/json

{
  "course_id": 1,
  "batch_id": 2,
  "limit": 50
}
```

**Response**:
```json
{
  "success": true,
  "students": [...],
  "stats": {
    "total_selected": 50,
    "gender_breakdown": {"male": 28, "female": 22},
    "avg_exam_score": 75.3
  },
  "rules": [...]
}
```

### Execute Admission
```http
POST /admin/admission/execute
Content-Type: application/json

{
  "course_id": 1,
  "batch_id": 2,
  "limit": 50,
  "session_id": null
}
```

**Response**:
```json
{
  "success": true,
  "message": "Successfully admitted 50 students. Emails sent to 48 students.",
  "admission_run_id": 123,
  "admitted_count": 50
}
```

## Advanced Features

### Custom Rules

Create a new rule class:
```php
namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class DistanceFromCampus implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $maxDistance = $value['max_distance'] ?? $this->params['max_distance'] ?? 50;
        
        $query->whereRaw('
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) <= ?
        ', [
            $this->params['campus_lat'],
            $this->params['campus_lon'],
            $this->params['campus_lat'],
            $maxDistance
        ]);
        
        return $next($query);
    }
}
```

Then add to `rules` table:
```php
Rule::create([
    'name' => 'Distance from Campus',
    'rule_class_path' => 'App\\Services\\AdmissionRules\\DistanceFromCampus',
    'description' => 'Filter by proximity to campus',
    'default_parameters' => json_encode([
        'campus_lat' => 5.6037,
        'campus_lon' => -0.1870,
        'max_distance' => 50
    ]),
]);
```

### Manual Admission Tracking

When admitting manually via admin panel, set `admission_source`:
```php
UserAdmission::create([
    'user_id' => $userId,
    'course_id' => $courseId,
    'batch_id' => $batchId,
    'admission_source' => 'manual', // Important!
    'email_sent' => true,
]);
```

### Event Listener for Auto-Replacement

When a user rejects admission, fire the event:
```php
use App\Events\AdmissionRejected;

$admission->delete(); // or mark as rejected

event(new AdmissionRejected($admission, shouldReplace: true));
```

The listener will automatically find and admit the next eligible student using the same pipeline rules.

## Testing

### Test the Command
```bash
# Interactive mode
php artisan admission:auto-admit

# Direct mode (course ID 5, limit 30)
php artisan admission:auto-admit 5 --limit=30

# Dry run to see preview
php artisan admission:auto-admit 5 --dry-run
```

### Test the Scheduler
```bash
# Run all scheduled tasks
php artisan schedule:run

# Test specific task
php artisan schedule:test
```

## File Structure

```
backend/
├── app/
│   ├── Console/Commands/
│   │   └── AutoAdmitStudentsCommand.php
│   ├── Events/
│   │   └── AdmissionRejected.php
│   ├── Listeners/
│   │   └── ReplaceRejectedAdmission.php
│   ├── Http/Controllers/Admin/
│   │   ├── RuleCrudController.php
│   │   ├── RulePipelineCrudController.php
│   │   └── AdmissionRunCrudController.php
│   ├── Models/
│   │   ├── Rule.php
│   │   ├── AdmissionRun.php
│   │   ├── Course.php (updated)
│   │   ├── Programme.php (updated)
│   │   ├── User.php (updated)
│   │   └── UserAdmission.php (updated)
│   ├── Services/
│   │   ├── AdmissionService.php
│   │   ├── AdmissionStatisticsService.php
│   │   └── AdmissionRules/
│   │       ├── AdmissionRuleInterface.php
│   │       ├── PassMark.php
│   │       ├── CompletedExam.php
│   │       ├── AppliedBefore.php
│   │       ├── SortByDate.php
│   │       ├── GenderQuota.php
│   │       ├── AgeRange.php
│   │       └── EducationalLevel.php
│   └── Providers/
│       └── EventServiceProvider.php (updated)
├── database/
│   ├── migrations/
│   │   ├── 2026_02_09_164857_add_educational_level_to_users_table.php
│   │   ├── 2026_02_09_164858_add_auto_admission_to_courses_table.php
│   │   ├── 2026_02_09_164858_create_rules_table.php
│   │   ├── 2026_02_09_164859_add_admission_source_to_user_admission_table.php
│   │   ├── 2026_02_09_164859_create_admission_runs_table.php
│   │   └── 2026_02_09_164900_create_rule_assignments_table.php
│   └── seeders/
│       └── AdmissionRulesSeeder.php
└── routes/
    └── backpack/
        └── custom.php (updated)
```

## Maintenance

### Prune Old Admission Runs
```bash
php artisan tinker
AdmissionRun::where('created_at', '<', now()->subMonths(6))->delete();
```

### Monitor Scheduled Tasks
```bash
# Check last run
php artisan schedule:list
```

### Clear Statistics Cache
```bash
php artisan cache:forget 'admission_stats:*'
```

## Security Considerations

1. **Permissions**: Add Backpack permission checks to all CRUD controllers
2. **Validation**: Ensure JSON parameters are validated before execution
3. **Rate Limiting**: Apply throttling to preview/execute endpoints
4. **Audit Logs**: `admission_runs` table serves as audit trail

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review admission run history: `/admin/admission-run`
- Enable debug mode to see SQL queries

---

**Built**: February 9, 2026  
**Framework**: Laravel 10+ with Backpack for Laravel
