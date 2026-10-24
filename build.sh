#!/usr/bin/env bash
# Zip each plugin the way Moodle's installer expects: one top folder named after the plugin.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/local_vemhere.zip dist/mod_vemhere.zip dist/quizaccess_vemfocus.zip
(cd local && zip -qr ../dist/local_vemhere.zip vemhere -x '*/.*')
(cd mod && zip -qr ../dist/mod_vemhere.zip vemhere -x '*/.*')
(cd mod/quiz/accessrule && zip -qr ../../../dist/quizaccess_vemfocus.zip vemfocus -x '*/.*')
ls dist/*.zip
