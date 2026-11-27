#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Unispezi
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Builds the App Store tarball from the committed state and signs it.
#
#   tools/release.sh            build/clientside_video_thumbnails-<version>.tar.gz and its signature
#   tools/release.sh --publish  also tag v<version>, push the tag and create the GitHub release
#
# Signing key: ~/.nextcloud/certificates/clientside_video_thumbnails.key, or KEY=<path>.
# It never goes into the repository.
# Left out of the tarball: see .gitattributes (export-ignore).

set -euo pipefail

APP=clientside_video_thumbnails
REPO=Unispezi/nextcloud-clientside-video-thumbnails
KEY="${KEY:-$HOME/.nextcloud/certificates/$APP.key}"
cd "$(dirname "$0")/.."

version="$(sed -n 's#.*<version>\(.*\)</version>.*#\1#p' appinfo/info.xml)"
[[ -n "$version" ]] || { echo "no <version> in appinfo/info.xml"; exit 1; }
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || { echo "commit your changes first: the tarball is built from HEAD"; exit 1; }
grep -qE "^## $version - [0-9]{4}-[0-9]{2}-[0-9]{2}" CHANGELOG.md || { echo "CHANGELOG.md needs a dated entry: ## $version - YYYY-MM-DD"; exit 1; }

mkdir -p build
tarball="build/$APP-$version.tar.gz"
git archive --format=tar.gz --prefix="$APP/" -o "$tarball" HEAD
echo "Built $tarball ($(du -h "$tarball" | cut -f1)):"
tar -tzf "$tarball" | grep -E "^$APP/[^/]+/?$" | sed 's/^/  /'

signature=""
if [[ -f "$KEY" ]]; then
	signature="$(openssl dgst -sha512 -sign "$KEY" "$tarball" | openssl base64 -A)"
	echo "$signature" > "$tarball.sig"
	echo "Signature in $tarball.sig"
else
	echo "No signing key at $KEY: tarball not signed"
fi

if [[ "${1:-}" == --publish ]]; then
	[[ -n "$signature" ]] || { echo "won't publish unsigned"; exit 1; }
	notes="$(awk -v v="$version" '$0 ~ "^## "v" " {f=1; next} f && /^## / {exit} f' CHANGELOG.md)"
	git tag -a "v$version" -m "Release $version"
	git push origin "v$version"
	gh release create "v$version" "$tarball" -R "$REPO" --title "$version" --notes "$notes"
	echo
	echo "App Store → Upload app release:"
	echo "  Download: https://github.com/$REPO/releases/download/v$version/$APP-$version.tar.gz"
	echo "  Signature: $signature"
fi
