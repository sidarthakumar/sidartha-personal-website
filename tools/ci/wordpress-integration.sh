#!/usr/bin/env bash
# GitHub-hosted CI only. Never operates on the saved development/hosting environment.
set -euo pipefail
set +x
exec 3>&1
[[ ${GITHUB_ACTIONS:-} == true && ${RUNNER_ENVIRONMENT:-} == github-hosted ]]
[[ -n ${RUNNER_TEMP:-} && -n ${GITHUB_WORKSPACE:-} ]]
ci_root=$(mktemp -d "$RUNNER_TEMP/sidartha-ci.XXXXXXXX")
ci_id="sidartha-ci-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
ci_network="$ci_id-network"
ci_database="$ci_id-db"
ci_cli_image=wordpress:cli-php8.3
ci_db_image=mariadb:11.4
cleanup() {
  # Only this job's newly created database and network; never existing resources.
  docker rm -fv "$ci_database" >/dev/null 2>&1 || true
  docker network rm "$ci_network" >/dev/null 2>&1 || true
  rm -rf -- "$ci_root"
}
trap cleanup EXIT
ci_stage='dependency retrieval'
report_failure() {
  local ci_status=$?
  echo "FAIL: synthetic CI stage $ci_stage (exit $ci_status)" >&3
  if [[ -f $ci_root/setup.log ]] && grep -Eqi '(mysql|mariadb).*not found' "$ci_root/setup.log"; then
    echo 'FAIL: WP-CLI configuration dependency mysql/mariadb executable unavailable' >&3
  fi
  exit "$ci_status"
}
trap report_failure ERR
mkdir "$ci_root/site"
chmod 700 "$ci_root"
ci_password=$(openssl rand -hex 32)
ci_root_password=$(openssl rand -hex 32)
ci_admin_password=$(openssl rand -hex 32)
printf '::add-mask::%s\n' "$ci_password" "$ci_root_password" "$ci_admin_password"
umask 077
printf 'MARIADB_DATABASE=wordpress_ci\nMARIADB_USER=wordpress_ci\nMARIADB_PASSWORD=%s\nMARIADB_ROOT_PASSWORD=%s\n' "$ci_password" "$ci_root_password" > "$ci_root/database.env"
# Dependency retrieval precedes isolation. No application execution on this network.
docker pull "$ci_cli_image" >/dev/null
docker pull "$ci_db_image" >/dev/null
ci_uid="$(id -u):$(id -g)"
docker run --rm --entrypoint wp --user "$ci_uid" --volume "$ci_root/site:/var/www/html" \
  "$ci_cli_image" core download --version=6.6.2 --locale=en_US --quiet
docker run --rm --entrypoint wp --user "$ci_uid" --volume "$ci_root/site:/var/www/html" \
  "$ci_cli_image" core verify-checksums --version=6.6.2 --locale=en_US
cp -R "$GITHUB_WORKSPACE/wp-content/themes/sidartha" "$ci_root/site/wp-content/themes/"
cp -R "$GITHUB_WORKSPACE/wp-content/plugins/sidartha-portfolio" "$ci_root/site/wp-content/plugins/"
docker network create --internal "$ci_network" >/dev/null
docker run -d --name "$ci_database" --network "$ci_network" --network-alias ci-db \
  --env-file "$ci_root/database.env" "$ci_db_image" >/dev/null
ci_ready=false
for ((ci_attempt=0; ci_attempt<60; ci_attempt++)); do
  if docker exec "$ci_database" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then
    ci_ready=true
    break
  fi
  sleep 1
done
[[ $ci_ready == true ]] || { echo 'FAIL: synthetic database startup'; exit 1; }
wp_ci() {
  docker run --rm -i --entrypoint wp --user "$ci_uid" --network "$ci_network" \
    --volume "$ci_root/site:/var/www/html" \
    --volume "$GITHUB_WORKSPACE/tools:/ci-tools:ro" \
    "$ci_cli_image" "$@"
}
# Salts and configuration are generated privately, not fetched from WordPress.org.
ci_stage='configuration creation'
wp_ci config create --dbname=wordpress_ci --dbuser=wordpress_ci --dbpass="$ci_password" \
  --dbhost=ci-db --skip-check --skip-salts --extra-php > "$ci_root/setup.log" 2>&1 <<'PHP'
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'DISABLE_WP_CRON', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
PHP
ci_stage='private salt generation'
for ci_key in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
  wp_ci config set "$ci_key" "$(openssl rand -hex 32)" >> "$ci_root/setup.log" 2>&1
done
ci_stage='synthetic WordPress installation'
wp_ci core install --url=http://127.0.0.1:8080 --title='Synthetic CI site' \
  --admin_user=ci-admin --admin_password="$ci_admin_password" \
  --admin_email=ci@example.invalid --skip-email >> "$ci_root/setup.log" 2>&1
ci_stage='synthetic component activation and seed'
wp_ci plugin activate sidartha-portfolio >> "$ci_root/setup.log" 2>&1
wp_ci theme activate sidartha >> "$ci_root/setup.log" 2>&1
wp_ci --user=ci-admin skp seed --publish-local >> "$ci_root/setup.log" 2>&1
echo 'PASS: disposable WordPress with internal-only database and synthetic administrator'
# Output only fixed test labels; raw errors/setup/configuration remain private.
run_suite() {
  local ci_suite="$1"
  if ! wp_ci --user=ci-admin eval-file "/ci-tools/$ci_suite" > "$ci_root/test.log" 2>&1; then
    sed -n '/^Error: FAIL:/p' "$ci_root/test.log"
    echo "FAIL: $ci_suite (other diagnostics withheld)"
    return 1
  fi
  sed -n '/^PASS:/p; /^Success:/p' "$ci_root/test.log"
  grep -q '^Success:' "$ci_root/test.log"
}
run_suite dev/test-website.php
run_suite ci/test-wordpress-seo.php
for ci_plugin_mode in WPSEO_VERSION RANK_MATH_VERSION AIOSEO_VERSION; do
  if ! wp_ci --user=ci-admin eval-file /ci-tools/ci/test-wordpress-seo.php "$ci_plugin_mode" > "$ci_root/test.log" 2>&1; then
    echo "FAIL: native SEO ownership $ci_plugin_mode"
    exit 1
  fi
  sed -n '/^PASS:/p; /^Success:/p' "$ci_root/test.log"
  grep -q '^Success:' "$ci_root/test.log"
done
