# Learning Analytics Intelligence

R5 adds teacher-facing learning signals without turning course data into unsupported predictions.

## Evidence first

Learning Signals are derived from actual course progress, submitted Quiz attempts, and recorded Quiz answers. They are presented as review cues, not diagnoses of student ability.

## Repeated attempts

For item-level evidence, one student must not have more statistical weight merely because they used more retries. The engine therefore uses the latest submitted attempt for each student/Quiz pair when calculating current item-level evidence and the latest-attempt pass rate.

Course-level historical metrics already available in the LMS remain unchanged.

## Minimum item sample

A question receives an action label only after at least three representative responses:

- below 50% correct: **review**;
- 50% to below 70% correct: **monitor**;
- 70% or higher: **healthy**;
- fewer than 3 responses: **insufficient**.

The threshold is deliberately small enough for classroom use but the UI explicitly marks small samples as limited. Teachers should consider the lesson context, wording, answer key, and distractors before interpreting low correctness as weak concept mastery.

## Course learning signals

The engine can surface signals for:

- high-priority students already identified by the existing intervention rules;
- low Quiz participation;
- low latest-attempt pass rate when there are at least three representative attempts;
- assessment items with enough responses and a correct rate below 70%;
- low average course progress;
- insufficient evidence when data is still sparse.

## Privacy and scope

The Learning Signals JSON endpoint is protected by the same course-owner authorization as the existing instructor analytics page. No answer key or correct option identifier is returned in item insight payloads.

R5 does not modify grades, attempt scores, passing decisions, student progress, or publication rules.
