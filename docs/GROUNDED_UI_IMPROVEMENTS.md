# UI/UX Improvements

## Current Problems

The system requires too many navigation steps for common tasks. Staff members switch context constantly. Appointment status changes require individual clicks. Today's work is buried under aggregate dashboards.

---

## 1. Default to Today

**Problem**: Dashboard shows lifetime totals (patient count, doctor count). Staff don't care. They need today's work.

**Current behavior**: User logs in → sees KPIs → clicks Appointments → sees all appointments → mentally filters to today.

**Change**: The first thing staff sees after login is today's appointments. Not cards. Not stats. The actual work queue for the day.

**What it replaces**: The KPI cards on the dashboard. Move those to a "Statistics" sub-tab for admins who actually want them.

**Why this is obvious**: Every clinic I've observed has a printed daily schedule taped to the reception desk. The software should start where the paper does.

![Default to Today](img/dashboard_today_view.png)

---

## 2. Inline Patient Context on Hover

**Problem**: When booking or viewing an appointment, you often need patient details (phone, blood group, last visit). Currently this requires navigating to the patient page, then navigating back.

**Current behavior**: See patient name → want phone number → click patient link → new page loads → find number → click back → re-find the appointment.

**Change**: Hovering on any patient name shows a small card with: phone, blood group, last visit date. No navigation. No modal.

**What it replaces**: Repeated navigation to patient detail pages during triage or confirmation cal ls.

**Why this works**: Reception staff make calls while looking at appointments. They need the number without losing their place. The information already exists in the API response; it's just not displayed.

![Patient Hover](img/patient_hover_card.png)

---

## 3. Batch Status Actions

**Problem**: End of day, staff update 15-20 appointment statuses from "scheduled" to "completed" or "no_show". Current UI requires: click row → click dropdown → select status → repeat.

**Current behavior**: One status change = 3 clicks minimum.

**Change**: Checkbox column on appointment table. Select multiple. Single action bar appears: "Mark as Completed" / "Mark as No-Show". One click updates all selected.

**What it replaces**: Individual per-row dropdown interactions.

**Why this matters**: At 5pm, staff want to close out the day in 30 seconds, not 5 minutes. Batch operations respect that the day ends with repetitive cleanup.

![Batch Actions](img/batch_actions.png)

---

## 4. Appointment Row Shows Next Action

**Problem**: Looking at an appointment row, you see status but not what you should do. Staff must mentally decode: "scheduled" means I should confirm, "completed" means I should check billing, etc.

**Current behavior**: Status is displayed. User must infer next step.

**Change**: Replace generic "Actions" column with context-aware primary action:
- scheduled → "Confirm" button (or phone icon to call patient)
- confirmed → "Check In" button
- in_progress → "Complete" button  
- completed → "Create Bill" link (if no bill exists)
- billed → nothing, row is done

The button does the obvious next thing. One click.

**What it replaces**: Generic Edit/Cancel buttons that require a modal to do anything.

**Why this is correct**: The system knows the state. It should surface the likely action. Staff shouldn't have to think about what step comes next.

![Context Buttons](img/context_buttons.png)

---

## 5. Persistent Filters with URL State

**Problem**: Staff frequently filter by doctor or date range. Every page navigation resets filters. They re-select the same doctor 20 times a day.

**Current behavior**: Filter by Dr. Sharma → navigate to billing → come back to appointments → filter lost → re-select Dr. Sharma.

**Change**: Filter state persists in URL hash (e.g., `#/appointments?doctor=3&date=2026-02-01`). Bookmarkable. Shareable. Survives navigation.

**What it replaces**: Nothing added. Current filter dropdowns stay. They just persist.

**Why this is right**: This is how every serious data-heavy tool works (GitHub, Jira, spreadsheets). Filters are work. Don't throw away work.

![Persistent Filters](img/persistent_filters.png)

---

## 6. Change Password on Profile

**Problem**: Users need to change their password but there's no option.

**Change**: Added a "Change Password" card on the Profile page. Enter new password (min 6 chars), click Update.

**API**: `POST /auth/change-password` with `{ new_password: "..." }`

---

## What Was Removed

- All visual enhancement ideas (glows, gradients, animations)
- Timeline metaphors
- Command palettes
- Density toggles
- Session timers
- Any concept that required explanation of "why it's premium"

---

## Implementation Notes

All six changes:
- Use existing API endpoints
- Work with zero animation
- Work in grayscale
- Are testable within 10 minutes of use
