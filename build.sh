#!/usr/bin/env bash
# Zip each plugin the way Moodle's installer expects: one top folder named after the plugin.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/local_vemhere.zip
(cd local && zip -qr ../dist/local_vemhere.zip vemhere -x '*/.*')
echo "dist/local_vemhere.zip"
