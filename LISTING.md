# moodle.org plugins directory: what to submit

Two plugins, submitted one after the other at <https://moodle.org/plugins/registerplugin.php>
(a moodle.org account is needed; the plugin is listed under it). Submit `local_vemhere` first:
`mod_vemhere` depends on it.

## local_vemhere

- **Name:** Vemhere
- **Component:** local_vemhere
- **Short description:** Pair Moodle with Vemhere in one key: Vemhere manages who is enrolled, and results come back matched to registration numbers.
- **Description:**
  Vemhere is an attendance and enrolment platform for institutions. This plugin connects a Moodle site to it.
  Installing it creates a dedicated service user and a role holding only what Vemhere calls, switches web
  services and REST on, and declares the Vemhere service. The administrator pastes a one-time pairing key made
  in Vemhere; nothing else is configured by hand.
  Once paired, Vemhere imports the site once (categories, courses, teachers, category managers, active
  enrolments), then decides who is in each course: it mirrors rosters by ID number, switches self-enrolment
  off on the courses it manages, creates the courses a head of department makes, reads results into its
  gradebook, and writes attendance into the "Attendance (Vemhere)" activity.
  Uninstalling tells Vemhere first, then removes the token, the user and the role.
- **Supported Moodle:** 4.2, 4.3, 4.4, 4.5 (tested on 4.5.15+)
- **Maturity:** Alpha
- **Licence:** GNU GPL v3 or later
- **Source control:** https://github.com/gasmsyteacher/vemhere-moodle (folder `local/vemhere`)
- **Issues:** https://github.com/gasmsyteacher/vemhere-moodle/issues
- **Privacy:** declares Vemhere as an external location receiving names, email, ID number, enrolments and grades.
- **Zip:** https://github.com/gasmsyteacher/vemhere-moodle/releases/latest/download/local_vemhere.zip

## mod_vemhere

- **Name:** Attendance (Vemhere)
- **Component:** mod_vemhere
- **Short description:** A teacher adds it to a course: it shows the next session and sends people to Vemhere to check in; its grade column holds each student's attendance rate.
- **Description:**
  Nothing to configure: a name, and a grade column out of 100 that Vemhere fills with each student's
  attendance rate. Its page shows whether a session is open now or when the next one is, with "Open in
  Vemhere" for teachers and "Check in" / "My attendance" for students. Course backup and restore keep it.
  Requires local_vemhere and a paired Vemhere institution.
- **Supported Moodle, maturity, licence, source, issues:** as above (folder `mod/vemhere`)
- **Privacy:** stores no personal data; its grades are Moodle's own gradebook records.
- **Screenshot:** `docs/screenshots/attendance-activity.png`
- **Zip:** https://github.com/gasmsyteacher/vemhere-moodle/releases/latest/download/mod_vemhere.zip

## Checks run before submitting

`./ci-local.sh`: php -l, moodle-cs (`--standard=moodle`, zero errors and warnings), PHPUnit for both plugins.
Not yet: Behat scenarios, and the directory's own precheck (run by moodle.org after submission).
