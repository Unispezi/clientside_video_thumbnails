#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 silversong88
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# End-to-end checks against a running Nextcloud with clientside_video_thumbnails enabled.
#
# Required environment:
#   NC_BASE    e.g. http://localhost:8080
#   NC_ADMIN   admin account name
#   NC_PASS    admin password
#   OCC        command prefix that runs occ, e.g. "docker exec -u www-data nc php occ"
# Optional:
#   WORKER=1   also run the worker (needs node and ffmpeg) against the server

set -euo pipefail

: "${NC_BASE:?}" "${NC_ADMIN:?}" "${NC_PASS:?}" "${OCC:?}"
HERE="$(cd "$(dirname "$0")" && pwd)"
FIX="$HERE/fixtures"
APP=clientside_video_thumbnails
API="$NC_BASE/index.php/apps/$APP/thumbnail"
DAV="$NC_BASE/remote.php/dav/files/$NC_ADMIN"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

failures=0
pass() { printf '  ok    %s\n' "$1"; }
fail() { printf '  FAIL  %s\n' "$1"; [[ -s "$TMP/last-body" ]] && printf '        body: %.300s\n' "$(cat "$TMP/last-body")"; failures=$((failures + 1)); }
check() { # check <description> <expected> <actual>
	if [[ "$3" == "$2" ]]; then pass "$1"; else fail "$1 (expected '$2', got '$3')"; fi
}

admin() { curl -s -u "$NC_ADMIN:$NC_PASS" -H 'OCS-APIRequest: true' "$@"; }
code() { admin -o "$TMP/last-body" -w '%{http_code}' "$@"; }
occ() { $OCC "$@"; }
has_preview() {
	admin -X PROPFIND -H 'Depth: 0' --data '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:prop><nc:has-preview/></d:prop></d:propfind>' "$DAV/$1" |
		grep -oE '<nc:has-preview>[a-z]+' | sed 's/.*>//'
}
file_id() {
	admin -X PROPFIND -H 'Depth: 0' --data '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>' "$DAV/$1" |
		grep -oE '<oc:fileid>[0-9]+' | sed 's/.*>//'
}

echo "== Nextcloud $(curl -s "$NC_BASE/status.php" | grep -oE '"versionstring":"[^"]+"' | cut -d'"' -f4)"

echo "== setup"
admin -X MKCOL "$DAV/vtr-test" -o /dev/null
check "upload sample video" 201 "$(code -T "$FIX/sample.mp4" "$DAV/vtr-test/sample.mp4")"
check "upload text file" 201 "$(code -T "$HERE/smoke.sh" "$DAV/vtr-test/notes.txt")"
check "video has no preview before upload" false "$(has_preview vtr-test/sample.mp4)"

echo "== capabilities"
caps="$(admin "$NC_BASE/ocs/v2.php/cloud/capabilities?format=json")"
check "capability advertised" 1 "$(grep -c "\"$APP\"" <<<"$caps")"

echo "== upload API"
check "JPEG upload accepted" 200 "$(code -F path=/vtr-test/sample.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
check "PNG rejected" 415 "$(code -F path=/vtr-test/sample.mp4 -F "thumbnail=@$FIX/not-jpeg.png" "$API/upload")"
check "non-video rejected" 404 "$(code -F path=/vtr-test/notes.txt -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
check "missing file rejected" 404 "$(code -F path=/vtr-test/nope.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
check "upload without file rejected" 400 "$(code -F path=/vtr-test/sample.mp4 "$API/upload")"

echo "== exists / batchExists"
check "exists true" '{"exists":true}' "$(admin "$API/exists?path=/vtr-test/sample.mp4")"
check "exists false for missing" '{"exists":false}' "$(admin "$API/exists?path=/vtr-test/nope.mp4")"
check "batchExists keys and values" \
	'{"status":"success","results":{"vtr-test\/sample.mp4":true,"vtr-test\/notes.txt":false}}' \
	"$(admin -H 'Content-Type: application/json' -d '{"paths":["/vtr-test/sample.mp4","/vtr-test/notes.txt"]}' "$API/batchExists")"

echo "== preview served by Nextcloud"
check "has-preview true" true "$(has_preview vtr-test/sample.mp4)"
fid="$(file_id vtr-test/sample.mp4)"
check "/core/preview returns JPEG" "200 image/jpeg" \
	"$(admin -o "$TMP/p.jpg" -w '%{http_code} %{content_type}' "$NC_BASE/index.php/core/preview?fileId=$fid&x=128&y=128&a=1")"

echo "== secret"
secret="$(admin -X POST "$NC_BASE/index.php/apps/$APP/settings/secret" | sed -E 's/.*"secret":"([^"]+)".*/\1/')"
check "secret generated" 48 "${#secret}"
check "request without secret rejected" 401 "$(code "$API/exists?path=/vtr-test/sample.mp4")"
check "request with secret accepted" 200 "$(code -H "X-ClientsideVideoThumbnails-Secret: $secret" "$API/exists?path=/vtr-test/sample.mp4")"

echo "== browser session (Files app script)"
jar="$TMP/cookies"
# Sign in through the login form like a browser. Nextcloud rejects login posts without an Origin header.
login_token="$(curl -sL -c "$jar" -b "$jar" "$NC_BASE/index.php/login" | grep -oE 'data-requesttoken="[^"]+"' | head -1 | cut -d'"' -f2 || true)"
login_result="$(curl -s -c "$jar" -b "$jar" -o /dev/null -w '%{http_code} %{redirect_url}' -X POST -H "Origin: $NC_BASE" \
	--data-urlencode "user=$NC_ADMIN" --data-urlencode "password=$NC_PASS" --data-urlencode "requesttoken=$login_token" \
	--data-urlencode "timezone=UTC" --data-urlencode "timezone_offset=0" "$NC_BASE/index.php/login" || true)"
session_token="$(curl -s -c "$jar" -b "$jar" "$NC_BASE/index.php/csrftoken" | sed -nE 's/.*"token":"([^"]+)".*/\1/p' || true)"
session_answer="$(curl -s -c "$jar" -b "$jar" -H 'OCS-APIRequest: true' -w ' HTTP %{http_code}' "$NC_BASE/ocs/v2.php/cloud/user?format=json" || true)"
session_user="$(grep -oE '"id":"[^"]+"' <<<"$session_answer" | head -1 | cut -d'"' -f4 || true)"
check "cookie session signed in" "$NC_ADMIN" "$session_user"
if [[ "$session_user" != "$NC_ADMIN" ]]; then
	echo "        login token: ${#login_token} chars, login answer: $login_result"
	echo "        csrftoken: ${#session_token} chars, cookies: $(sed 's/^#HttpOnly_//' "$jar" | grep -v '^#' | awk 'NF {print $6}' | tr '\n' ' ' || true)"
	printf '        answer: %.300s\n' "$session_answer"
fi
browser() { curl -s -b "$jar" -o "$TMP/last-body" -w '%{http_code}' "$@"; }
check "Files app loads the browser script" 1 \
	"$(curl -s -b "$jar" "$NC_BASE/index.php/apps/files/" | grep -q "$APP/js/files-thumbs" && echo 1 || echo 0)"
check "session upload without secret but with request token accepted" 200 \
	"$(browser -H "requesttoken: $session_token" -F path=/vtr-test/sample.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
check "personal settings page loads the find-and-fix script" 1 \
	"$(curl -s -b "$jar" "$NC_BASE/index.php/settings/user/$APP" | grep -q "$APP/js/personal" && echo 1 || echo 0)"
# Same query as js/personal.js
search_body='<?xml version="1.0" encoding="UTF-8"?><d:searchrequest xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns"><d:basicsearch><d:select><d:prop><oc:fileid/><nc:has-preview/></d:prop></d:select><d:from><d:scope><d:href>/files/'"$NC_ADMIN"'</d:href><d:depth>infinity</d:depth></d:scope></d:from><d:where><d:like><d:prop><d:getcontenttype/></d:prop><d:literal>video/%</d:literal></d:like></d:where><d:orderby/></d:basicsearch></d:searchrequest>'
search_out="$(curl -s -b "$jar" -X SEARCH -H "requesttoken: $session_token" -H 'Content-Type: text/xml; charset=utf-8' --data "$search_body" "$NC_BASE/remote.php/dav/")"
check "video search finds sample.mp4 with preview" 1 \
	"$(grep -q 'vtr-test/sample.mp4' <<<"$search_out" && grep -q '<nc:has-preview>true' <<<"$search_out" && echo 1 || echo 0)"
check "video search leaves out other files" 0 "$(grep -c 'notes.txt' <<<"$search_out" || true)"
check "session upload without request token rejected" 401 \
	"$(browser -F path=/vtr-test/sample.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
occ security:bruteforce:reset 127.0.0.1 >/dev/null 2>&1 || true

check "secret removed" 200 "$(code -X DELETE "$NC_BASE/index.php/apps/$APP/settings/secret")"
occ security:bruteforce:reset 127.0.0.1 >/dev/null 2>&1 || true
check "request without secret accepted again" 200 "$(code "$API/exists?path=/vtr-test/sample.mp4")"

echo "== admin-only routes"
bob_pass="$(head -c 18 /dev/urandom | base64 | tr -d '/+=')Aa1!"
admin -X POST "$NC_BASE/ocs/v2.php/cloud/users" -d userid=vtr-bob --data-urlencode "password=$bob_pass" -o /dev/null
bob() { curl -s -o /dev/null -w '%{http_code}' -u "vtr-bob:$bob_pass" -H 'OCS-APIRequest: true' "$@"; }
check "non-admin cannot deleteAll" 403 "$(bob -X POST "$API/deleteAll")"
check "non-admin cannot set secret" 403 "$(bob -X POST "$NC_BASE/index.php/apps/$APP/settings/secret")"
check "non-admin cannot upload for admin's file" 404 "$(bob -F path=/vtr-test/sample.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
admin -X DELETE "$NC_BASE/ocs/v2.php/cloud/users/vtr-bob" -o /dev/null

echo "== orphan cleanup job"
job="$(occ background-job:list --class='OCA\ClientsideVideoThumbnails\BackgroundJob\CleanupOrphans' --output=json | grep -oE '"id":"?[0-9]+' | head -1 | grep -oE '[0-9]+$')"
check "cleanup job registered" 1 "$([[ -n "$job" ]] && echo 1 || echo 0)"
admin -T "$FIX/sample.mp4" "$DAV/vtr-test/doomed.mp4" -o /dev/null
check "thumbnail for doomed.mp4 stored" 200 "$(code -F path=/vtr-test/doomed.mp4 -F "thumbnail=@$FIX/thumb.jpg" "$API/upload")"
doomed_id="$(file_id vtr-test/doomed.mp4)"
admin -X DELETE "$DAV/vtr-test/doomed.mp4" -o /dev/null
occ background-job:execute --force-execute "$job" >/dev/null
appdata_thumb() { occ files:get "appdata_$(occ config:system:get instanceid)/$APP/thumbs/$(printf %02d $((doomed_id % 100)))/$(printf %02d $(((doomed_id / 100) % 100)))/$doomed_id.jpg" /dev/null >/dev/null 2>&1 && echo kept || echo gone; }
check "thumbnail kept while video is in trash" kept "$(appdata_thumb)"
occ trashbin:cleanup "$NC_ADMIN" >/dev/null
occ background-job:execute --force-execute "$job" >/dev/null
check "thumbnail removed after trash emptied" gone "$(appdata_thumb)"

if [[ "${WORKER:-0}" == 1 ]]; then
	echo "== worker"
	admin -T "$FIX/sample.mp4" "$DAV/vtr-test/worker.mp4" -o /dev/null
	cat >"$TMP/.env" <<-EOF
		NC_URL=$DAV/
		NC_USER=$NC_ADMIN
		NC_PASS=$NC_PASS
		NC_STRICT_TLS=false
		TEMP_DIR=$TMP/w
		FOLDER_CACHE=$TMP/folder_cache.csv
		THUMB_CACHE=$TMP/thumb_cache.csv
		FAIL_CACHE=$TMP/fail_cache.csv
		FFMPEG_THREADS=2
	EOF
	out="$(node "$HERE/../../worker/index.js" --env "$TMP/.env" 2>&1)" || true
	check "worker reports no failures" 1 "$(grep -c 'Failed:   0' <<<"$out")"
	check "worker thumbnail visible as preview" true "$(has_preview vtr-test/worker.mp4)"
	[[ $failures -eq 0 ]] || echo "$out" | tail -30
fi

echo "== cleanup"
admin -X DELETE "$DAV/vtr-test" -o /dev/null
occ trashbin:cleanup "$NC_ADMIN" >/dev/null || true

echo
if [[ $failures -gt 0 ]]; then
	echo "$failures check(s) failed"
	exit 1
fi
echo "all checks passed"
