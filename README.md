# Vemhere for Moodle

Connects a Moodle site to [Vemhere](https://vemhere.com), the attendance and enrolment platform.
Vemhere decides who is in each course; Moodle mirrors it. Results come back to Vemhere matched to
registration numbers.

## Plugins

| Plugin | For | Status |
|---|---|---|
| `local/vemhere` | The Moodle administrator: one install, one pairing key | 0.6.1: pairing, import, rosters, courses, grades, attendance |
| `mod/vemhere` | Teachers: the "Attendance (Vemhere)" activity, nothing to configure | 0.1.2 (course backup and restore) |
| `mod/quiz/accessrule/vemfocus` | Teachers: "Focus monitor" on a quiz, records and warns when a student leaves the page. Works without pairing | 0.1.0 (alpha) |

## Install (Moodle 4.2 or newer)

Download both zips from the latest release:
[local_vemhere.zip](https://github.com/gasmsyteacher/vemhere-moodle/releases/latest/download/local_vemhere.zip) and
[mod_vemhere.zip](https://github.com/gasmsyteacher/vemhere-moodle/releases/latest/download/mod_vemhere.zip).
The focus monitor is separate and optional:
[quizaccess_vemfocus.zip](https://github.com/gasmsyteacher/vemhere-moodle/releases/latest/download/quizaccess_vemfocus.zip)
(Site administration, Plugins, Install plugins; nothing to pair, off on every quiz until a teacher turns it on).


1. Site administration, Plugins, Install plugins: upload `local_vemhere.zip`, then `mod_vemhere.zip`.
2. In Vemhere, an administrator opens Moodle and presses **Make a pairing key**.
3. In Moodle: Site administration, Plugins, Local plugins, Vemhere. Paste the key, press Connect.

The plugin creates a `vemhere_service` user and a "Vemhere service" role holding only what
Vemhere calls, switches web services and REST on, and gives Vemhere a token for its own service.
Uninstalling tells Vemhere (so it stops syncing), then removes the token, the user and the role.

## Checks

```bash
./ci-local.sh         # php -l, moodle-cs, PHPUnit for all three plugins (needs the dev rig)
```

GitHub Actions are off on this account; these run locally before every release.

## Build a zip

```bash
./build.sh            # writes dist/local_vemhere.zip, dist/mod_vemhere.zip, dist/quizaccess_vemfocus.zip
```

## Licence

GNU GPL v3 or later. Copyright 2026 Virtual Minds Hub Enterprise.
