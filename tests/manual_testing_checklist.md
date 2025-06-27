# Manual Testing Checklist - Evaluation Workflow Authorization Fix

## Quick Test Steps

### Test 1: Basic Workflow State Management
1. **Login as HR Admin** (`admin` / `admin123`)
   - Create a new evaluation for any employee
   - Verify status shows as "draft"
   - Note the evaluation ID

2. **Login as Manager** (the employee's manager)
   - Go to evaluation list
   - Find the evaluation created above
   - ✅ **VERIFY**: You see an "Edit" button
   - Click "Edit" and submit the evaluation
   - ✅ **VERIFY**: Status changes to "submitted"
   - ✅ **VERIFY**: "Edit" button disappears

3. **Login as HR Admin** again
   - Go to evaluation list
   - Find the same evaluation
   - ✅ **VERIFY**: Status shows "submitted"
   - ✅ **VERIFY**: You see "Review" button (not "Edit")
   - Click "Review" to access the evaluation
   - ✅ **VERIFY**: You see "Approve" and "Reject" buttons (NOT "Submit Evaluation")
   - ✅ **VERIFY**: You CANNOT "resubmit" the evaluation
   - ✅ **VERIFY**: Form shows "Save Changes" instead of "Save Draft"

4. **Test Workflow Progression** (Optional)
   - As HR Admin, click "Approve" button
   - ✅ **VERIFY**: Status changes to "approved"
   - ✅ **VERIFY**: Evaluation can still be accessed for review
   - ✅ **VERIFY**: No submission buttons appear for approved evaluations

### Test 2: Authorization Consistency Across Pages
With the submitted evaluation from Test 1:

1. **As Manager** - check these pages:
   - `/evaluation/list.php` - ✅ No "Edit" button
   - `/employees/view.php?id={employee_id}` - ✅ No "Edit" button  
   - `/evaluation/view.php?id={evaluation_id}` - ✅ No "Edit" button
   - Direct URL: `/evaluation/edit.php?id={evaluation_id}` - ✅ Access denied

2. **As HR Admin** - check these pages:
   - `/evaluation/list.php` - ✅ "Review" button visible
   - `/evaluation/view.php?id={evaluation_id}` - ✅ "Review" button visible
   - Direct URL: `/evaluation/edit.php?id={evaluation_id}` - ✅ Access granted

### Test 3: Database State Verification
1. **Check database directly**:
   ```sql
   SELECT evaluation_id, employee_id, manager_id, status 
   FROM evaluations 
   WHERE evaluation_id = {your_test_evaluation_id};
   ```
   - ✅ **VERIFY**: `status` field shows "submitted"
   - ✅ **VERIFY**: `manager_id` is properly set

### Test 4: Edge Cases
1. **Employee Login** (the employee being evaluated)
   - Go to evaluation list
   - ✅ **VERIFY**: No "Edit" buttons anywhere
   - Try direct URL: `/evaluation/edit.php?id={evaluation_id}`
   - ✅ **VERIFY**: Access denied

2. **Wrong Manager** (if you have multiple managers)
   - Login as a different manager
   - ✅ **VERIFY**: Cannot see the evaluation in their list
   - Try direct URL access
   - ✅ **VERIFY**: Access denied

## Expected Results Summary

| User Role | Draft Status | Submitted Status | Approved Status |
|-----------|-------------|------------------|-----------------|
| Manager (correct) | ✅ Can Edit | ❌ Cannot Edit | ❌ Cannot Edit |
| Manager (wrong) | ❌ Cannot See | ❌ Cannot See | ❌ Cannot See |
| HR Admin | ✅ Can Edit | ✅ Can Review | ✅ Can Review |
| Employee | ❌ Cannot Edit | ❌ Cannot Edit | ❌ Cannot Edit |

## Key Files Modified
- `includes/auth.php` - Enhanced `canEditEvaluation()` function
- `public/evaluation/edit.php` - Uses centralized authorization
- `public/evaluation/view.php` - Uses centralized authorization  
- `public/evaluation/list.php` - Uses centralized authorization
- `public/employees/view.php` - Uses centralized authorization

## If Tests Fail
1. Check browser console for JavaScript errors
2. Check PHP error logs: `docker-compose logs web`
3. Verify database schema has `manager_id` field
4. Confirm `make reset` was run after schema changes
5. Check session data is properly set

## Success Criteria
✅ **PASS**: Workflow states are consistent across all user accounts  
✅ **PASS**: Authorization is centralized and consistent across all pages  
✅ **PASS**: Managers cannot edit submitted evaluations  
✅ **PASS**: HR Admins can review submitted evaluations  
✅ **PASS**: Database state persists correctly across sessions