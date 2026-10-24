# Focus monitor (Vemhere) — quizaccess_vemfocus

A quiz access rule for Moodle 4.2 to 4.5. It records when a student leaves a quiz attempt page
(another app or tab, a locked screen) and for how long. It is a lighter cousin of Safe Exam Browser:
nothing to install on students' devices, works on phones.

## For the site administrator

- **Install:** Site administration → Plugins → Install plugins → upload `quizaccess_vemfocus.zip`
  (or unzip it to `mod/quiz/accessrule/vemfocus`), then run the upgrade. No settings at site level.
- **Off by default.** Installing it changes no quiz. Each teacher switches it on per quiz, under
  *Extra restrictions on attempts → Focus monitor*. Quizzes without it are untouched.
- **Modes:** Off · Record exits only · Record and warn · Record, warn and submit after *n* exits.
- **Students are told first.** The quiz page states the rule, and the student ticks "I understand"
  before starting. Teacher previews are not monitored.
- **Report:** teachers with `mod/quiz:viewreports` see, per attempt, the number of exits, time away
  and whether the monitor submitted it (link on the quiz page).
- **Privacy:** stores quiz, attempt, user, seconds away, action and time for each exit. Implements the
  Privacy API (export and delete with the quiz's data). No data leaves the Moodle site.
- **Backup:** the per-quiz setting is backed up and restored; exit records are not.
- **Uninstall:** removes both tables; quizzes keep working.
- **Limits:** a browser cannot see a second device or a phone beside the laptop. The monitor deters
  and gives evidence; for high-stakes tests, use Safe Exam Browser in a supervised room.

## Files

`rule.php` (the rule), `amd/` (page monitor), `classes/external/record_exit.php` (web service the
page calls, own attempt only), `report.php`, `classes/privacy/`, `backup/moodle2/`, `db/`.

Licence GPL v3 or later. © 2026 Virtual Minds Hub Enterprise.
