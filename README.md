# Vemhere for Moodle

Connects a Moodle site to [Vemhere](https://vemhere.com), the attendance and enrolment platform.
Vemhere decides who is in each course; Moodle mirrors it. Results come back to Vemhere matched to
registration numbers.

## Plugins

| Plugin | For | Status |
|---|---|---|
| `local/vemhere` | The Moodle administrator: one install, one pairing key | 0.3.0, pairing, import, rosters |
| `mod/vemhere` | Teachers: the "Attendance (Vemhere)" activity | planned |

## Install (Moodle 4.2 or newer)

1. Site administration, Plugins, Install plugins: upload `local_vemhere.zip`.
2. In Vemhere, an administrator opens Moodle and presses **Make a pairing key**.
3. In Moodle: Site administration, Plugins, Local plugins, Vemhere. Paste the key, press Connect.

The plugin creates a `vemhere_service` user and a "Vemhere service" role holding only what
Vemhere calls, switches web services and REST on, and gives Vemhere a token for its own service.
Uninstalling removes the token, the user and the role.

## Build a zip

```bash
./build.sh            # writes dist/local_vemhere.zip
```

## Licence

GNU GPL v3 or later. Copyright 2026 Virtual Minds Hub Enterprise.
