# R8 — Usability Validation

R8 validates whether the finished LMS-WEB can actually be used by representative teachers and students without relying on developer guidance. Automated tests remain useful for correctness, but they are **not** treated as usability evidence.

## Participant target

Use real representative users whenever possible.

- Instructor / teacher: **3–5 participants**.
- Student: **5–10 participants**.
- Do not combine instructor and student findings into one undifferentiated result. Report role-specific findings as well as the overall summary.
- Participants should not be coached through the interface after a task starts unless the session is explicitly marked as assisted.

The target is appropriate for formative product validation. It is not a claim of population-level statistical generalization.

## Metrics

Each observed task records:

- `completed`: 1 when the participant reaches the intended outcome without the facilitator completing the task for them; otherwise 0.
- `time_seconds`: elapsed task time in seconds.
- `errors`: count of observable incorrect actions that materially interrupt the task.
- `confusion_score`: observer rating 1–5, where 1 = smooth/no visible confusion and 5 = unable to proceed without help.
- `notes`: short factual observation, not an interpretation of the participant's personality or ability.

After all task scenarios, collect the 10 standard System Usability Scale (SUS) responses on a 1–5 Likert scale. The application command calculates the standard 0–100 SUS transformation; do not enter a fabricated SUS total.

## Instructor task scenarios

Use a fresh Instructor account and a prepared learning source document.

| ID | Task | Completion criterion |
| --- | --- | --- |
| I1 | Create a new course | Course exists and is visible in the instructor course collection |
| I2 | Add learning material | Material is attached to the intended course section |
| I3 | Create or generate a Slidebook | Slidebook draft/review exists from the chosen material |
| I4 | Review and publish a Slidebook | Teacher resolves blocking quality issues, approves, and publishes the intended version |
| I5 | Generate an AI Quiz | Quiz draft is created from the selected Slidebook |
| I6 | Review AI questions | Teacher verifies/edits questions so no unresolved review items remain |
| I7 | Publish learning flow | Required material/Quiz/course publication steps are completed |
| I8 | Read learning results | Teacher can locate progress, Quiz results, and Learning Signals for the course |

Do not tell participants which menu item or button to click. The facilitator may repeat the task goal but should not teach the path during measurement.

## Student task scenarios

Use an active Student account and a prepared published course.

| ID | Task | Completion criterion |
| --- | --- | --- |
| S1 | Find and join the prepared course | Active enrollment exists |
| S2 | Open the assigned learning material | Correct material is visible |
| S3 | Navigate the Slidebook | Participant can move through the Slidebook and identify the current learning section |
| S4 | Start and complete a Quiz | A submitted Quiz attempt exists |
| S5 | Interpret Quiz feedback | Participant can identify score/status and available feedback without facilitator explanation |
| S6 | Find course progress | Participant can locate their progress/completion state |

## Session procedure

1. Explain that the **product is being tested, not the participant**.
2. Obtain any consent required by the institution before observation/recording.
3. Give one task at a time using the task wording above or an equivalent neutral wording.
4. Start timing when the task is presented and stop when the completion criterion is met or the facilitator terminates the task.
5. Record errors and confusion during observation. Avoid prompting the participant toward a specific control.
6. After the assigned tasks, collect brief qualitative feedback: what was easiest, what was most confusing, and what the participant expected to happen differently.
7. Collect SUS responses after the participant has finished using the system.

## Task result CSV

Required headers:

```csv
participant_id,role,task_id,completed,time_seconds,errors,confusion_score,notes
```

Allowed roles are `instructor` and `student`. `completed` accepts `0/1`, `true/false`, or `yes/no`. `confusion_score` must be 1–5.

Example:

```csv
participant_id,role,task_id,completed,time_seconds,errors,confusion_score,notes
G01,instructor,I1,1,84,0,1,
G01,instructor,I3,1,151,1,2,sempat mencari tombol generate di halaman material
S01,student,S1,1,47,0,1,
S01,student,S4,1,205,0,1,
```

## SUS response CSV

Required headers:

```csv
participant_id,role,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10
```

Each `q1`–`q10` value must be 1–5. Keep the original SUS positive/negative item order intact so the standard scoring formula remains valid.

## Generate the report

```bash
php artisan usability:report storage/app/usability/tasks.csv \
  --sus=storage/app/usability/sus.csv
```

Machine-readable output:

```bash
php artisan usability:report storage/app/usability/tasks.csv \
  --sus=storage/app/usability/sus.csv \
  --json
```

The command reports:

- number of participants and task observations;
- task success rate;
- median task time;
- average errors;
- average observed confusion;
- the same metrics by role and by task;
- average and median SUS, including role-specific summaries.

## Interpretation guardrails

R8 is a **formative usability validation**, not a statistical effectiveness experiment. Do not claim that a small convenience sample proves the system is universally usable. Use the findings to identify recurring friction, especially tasks with low success, high error counts, high confusion, or consistent qualitative complaints.

A practical prioritization rule is:

- Critical: user cannot finish a core task or encounters a blocker/data-integrity risk.
- High: repeated confusion or errors across multiple participants on a core flow.
- Medium: task succeeds but requires avoidable searching/re-reading.
- Low: cosmetic preference with no meaningful task impact.

Fix product problems based on evidence, rerun the affected tasks, and retain both the before/after observations. R8 is complete only when real participant data has been collected and the most important recurring usability blockers have been addressed or explicitly documented.
