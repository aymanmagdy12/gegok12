# Teacher, student and parent dashboards

`/learning/academy/dashboard` is a real role-specific dashboard. Teacher cards show only assigned groups/students, with the timetable, register links and pending leave count. Student and parent cards show current eight-session attendance/homework percentages. Parent cards contain only active linked children; multiple children are supported. An empty account explicitly says no student is assigned. Existing `/teacher/dashboard`, `/student/dashboard`, `/parent/dashboard` and login entry paths lead into this dashboard in academy mode.

## Eight-session checklist

Open a group and choose Open the eight-session checklist. The current group roster gets eight fixed session slots per student. Expand a student and choose ✓, × or — for attendance/homework, then save that student's eight slots. Blank/unrecorded stays NULL; it is not counted as absence or missing homework. Percentages are present/8 and completed-homework/8, accompanied by explicit unrecorded counts. They describe progress across the whole set, including future blank slots, not a rate limited to recorded sessions.

Only the assigned teacher can edit current students/current sets. Admins can inspect; students/parents are read-only. Stale tabs cannot overwrite a newer revision. Reopening the set adds new roster members with blank slots and does not duplicate marks. Another set can be started explicitly; the previous set is archived and remains readable in the child's checklist history. Repeated stale requests cannot open multiple next sets. These sets do not automatically advance curriculum level/unit. Transfers retain history; the former group cannot edit a transferred child's marks.

Earlier dated lesson registers and attendance are retained separately, not imported into eight-slot sets or double-counted. A new checklist starts blank. The dated register is an optional collapsed section.

## Shared sidebar and personal links

Parent desktop/mobile sidebar uses the same structure and theme as the other account layouts. Dashboard, children and Messages are prominent. The selected child's recorded-session, homework and feedback/report-card links appear on the left, alongside internal feedback/conversation links. A child selector switches links when multiple children are linked. Teachers see links for an authorized selected student; the group-page selector is limited to its current roster. Link ownership is checked before loading data. Missing links are labelled rather than fabricated. External Drive sharing permissions are unchanged.

## Messages

Native server-rendered student conversations replace the broken legacy chat entry in academy navigation. Students, linked parents and currently assigned teachers can send/reply, as can academy administrators. Every thread is explicitly shared with those participants; it is not a private two-person DM. Student/tenant authorization is checked on every read/write. Existing academy teacher-feedback messages appear in the thread, while older school-chat tables remain untouched. Unsupported historical chat conversations are not silently migrated. Legacy teacher/student chat landing pages redirect to the native inbox. Plain-text messages are escaped; no realtime/WebSocket or email-delivery claim is made.

## Update

```bash
git fetch origin && git show origin/feature/academy-dashboards-checklists:scripts/preview-academy.sh | bash
```

The updater retains local code/env/assets, applies only additive migrations and stops on conflicts. It does not reset/reseed students or modify GitHub port visibility. Open `/learning/academy/dashboard` afterwards. Source changes are not automatically deployed to the running Codespace. Tests exercise native HTTP flows with layout fixtures and role/tenant boundaries; live browser behavior still needs a deployment check.
