#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Unispezi
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Puts a test Nextcloud with this app on a server over SSH, next to whatever already runs there:
# its own directory ~/cvt-test, its own compose project, and a port bound to 127.0.0.1 only.
# Reach it through a tunnel:  ssh -N -L 8034:127.0.0.1:8034 <ssh-host>  →  http://localhost:8034
#
#   tests/vps/deploy.sh <ssh-host> [nextcloud-major]
#
# Run it again to update the app from the working tree; the instance and its files stay.
# DOWN=1 removes the instance, its data and the directory.
#
# For phones: PUBLIC_HOST=<name> PROXY_NETWORK=<docker network of a reverse proxy> joins that network
# as "cvt-test", trusts the name and the proxy. The proxy itself (TLS, forwarding to cvt-test:80) is
# set up by hand, this script does not touch it.

set -euo pipefail

HOST="${1:?usage: deploy.sh <ssh-host> [nextcloud-major]}"
VERSION="${2:-34}"
PORT=$((8000 + VERSION))
APP=clientside_video_thumbnails
APP_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
PUBLIC_HOST="${PUBLIC_HOST:-}"
PROXY_NETWORK="${PROXY_NETWORK:-}"
[[ -z "$PUBLIC_HOST" || -n "$PROXY_NETWORK" ]] || { echo "PUBLIC_HOST needs PROXY_NETWORK"; exit 2; }

if [[ "${DOWN:-0}" == 1 ]]; then
	ssh "$HOST" 'cd ~/cvt-test 2>/dev/null && docker compose down -v; rm -rf ~/cvt-test'
	echo "Removed."
	exit 0
fi

public=""
if [[ -n "$PUBLIC_HOST" ]]; then
	subnet="$(ssh "$HOST" "docker network inspect -f '{{(index .IPAM.Config 0).Subnet}}' $PROXY_NETWORK")"
	public="
      OVERWRITECLIURL: https://$PUBLIC_HOST
      TRUSTED_PROXIES: $subnet
    networks:
      default: {}
      proxy:
        aliases: [cvt-test]
networks:
  proxy:
    name: $PROXY_NETWORK
    external: true"
fi

ssh "$HOST" 'mkdir -p ~/cvt-test && cat > ~/cvt-test/compose.yaml' <<EOF
# Written by tests/vps/deploy.sh of $APP. Test instance only.
name: cvt-test
volumes:
  html: {}
services:
  nextcloud:
    image: nextcloud:$VERSION-apache
    restart: unless-stopped
    ports:
      - "127.0.0.1:$PORT:80"
    mem_limit: 768m
    env_file: .env
    volumes:
      - html:/var/www/html
    environment:
      SQLITE_DATABASE: nc
      NEXTCLOUD_ADMIN_USER: admin
      NEXTCLOUD_TRUSTED_DOMAINS: localhost $PUBLIC_HOST
      PHP_UPLOAD_LIMIT: 2G$public
EOF

# The admin password is made once and stays in ~/cvt-test/.env on the server
ssh "$HOST" 'cd ~/cvt-test && if [ ! -f .env ]; then umask 077; echo "NEXTCLOUD_ADMIN_PASSWORD=$(head -c 18 /dev/urandom | base64 | tr -d "/+=")" > .env; fi
	docker compose up -d --quiet-pull'

echo "Waiting for Nextcloud $VERSION..."
ssh "$HOST" "for _ in \$(seq 1 150); do curl -sf http://localhost:$PORT/status.php | grep -q '\"installed\":true' && exit 0; sleep 2; done
	cd ~/cvt-test && docker compose logs --tail 30; exit 1"

# Files of the working tree, tracked or new but not ignored, without what the app does not need at runtime
(cd "$APP_DIR" && git ls-files --cached --others --exclude-standard | grep -vE '^(tests|docs|\.github)/' | tar -cf - -T -) |
	ssh "$HOST" "cd ~/cvt-test && docker compose exec -T nextcloud sh -c '
		rm -rf custom_apps/$APP && mkdir -p custom_apps/$APP &&
		tar -C custom_apps/$APP -xf - &&
		chown -R www-data:www-data custom_apps'"

# Browsers keep app scripts for months under a URL that only changes with the app version, so every
# deploy gets its own version (in the copy on the server only) and the upgrade that goes with it.
ssh "$HOST" "cd ~/cvt-test && docker compose exec -T -u www-data nextcloud sh -c '
	sed -i \"s#<version>\\([0-9.]*\\)</version>#<version>\\1.$(date +%s)</version>#\" custom_apps/$APP/appinfo/info.xml &&
	php occ upgrade -n | tail -1'"

ssh "$HOST" "cd ~/cvt-test && docker compose exec -T -u www-data nextcloud php occ app:enable $APP &&
	(docker compose exec -T -u www-data nextcloud php occ app:disable firstrunwizard >/dev/null 2>&1 || true)"

# The image only applies NEXTCLOUD_TRUSTED_DOMAINS on first install
if [[ -n "$PUBLIC_HOST" ]]; then
	ssh "$HOST" "cd ~/cvt-test && docker compose exec -T -u www-data nextcloud php occ config:system:set trusted_domains 1 --value=$PUBLIC_HOST >/dev/null"
fi

echo
[[ -z "$PUBLIC_HOST" ]] || echo "Public:   https://$PUBLIC_HOST"
echo "Tunnel:   ssh -N -L $PORT:127.0.0.1:$PORT $HOST"
echo "Browser:  http://localhost:$PORT  (admin / $(ssh "$HOST" 'sed -n "s/^NEXTCLOUD_ADMIN_PASSWORD=//p" ~/cvt-test/.env'))"
