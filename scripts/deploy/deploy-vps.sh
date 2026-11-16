#!/usr/bin/env bash
set -Eeuo pipefail
umask 0027

app_root="${1:?Application root required}"
release_id="${2:?Release ID required}"
archive="${3:?Archive path required}"
health_url="${4:?Health URL required}"
php_bin="${5:-php}"
fpm_service="${6:-}"
web_service="${7:-}"

[[ "$app_root" =~ ^/[a-zA-Z0-9/_-]+$ && "$app_root" != / ]] || { echo 'Invalid application root'; exit 1; }
[[ "$release_id" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]] || { echo 'Invalid release ID'; exit 1; }
[[ "$archive" == "/tmp/erpgarment-${release_id}.tar.gz" && -s "$archive" ]] || { echo 'Release archive missing'; exit 1; }
[[ "$health_url" =~ ^https?:// ]] || { echo 'Health URL must be HTTP(S)'; exit 1; }
[[ -z "$fpm_service" || "$fpm_service" =~ ^php[0-9.]+-fpm$ ]] || { echo 'Invalid FPM service'; exit 1; }
[[ -z "$web_service" || "$web_service" == apache2 ]] || { echo 'Invalid web server service'; exit 1; }
for cmd in flock tar curl composer; do command -v "$cmd" >/dev/null; done
command -v "$php_bin" >/dev/null
[[ -s "$app_root/shared/.env" ]] || { echo 'Provision shared/.env on the VPS first'; exit 1; }
mkdir -p "$app_root/releases"
exec 9>"$app_root/.deploy.lock"
flock -w 900 9 || { echo 'Another deployment is active'; exit 1; }

release="$app_root/releases/$release_id"
previous=''
if [[ -L "$app_root/current" ]]; then
    previous="$(readlink -f "$app_root/current")"
    [[ "$previous" == "$app_root/releases/"* && -f "$previous/artisan" ]] || { echo 'Unexpected current release'; exit 1; }
elif [[ -e "$app_root/current" ]]; then
    echo 'current must be a release symlink, not a directory'; exit 1
fi
[[ ! -e "$release" ]] || { echo 'Release directory already exists'; exit 1; }
maintenance=0
switched=0

reload_services() {
    if [[ -n "$fpm_service" ]]; then sudo -n systemctl reload "$fpm_service"; fi
    if [[ -n "$web_service" ]]; then sudo -n systemctl reload "$web_service"; fi
}

on_failure() {
    result=$?
    if (( result == 0 )); then result=1; fi
    trap - ERR INT TERM
    set +e
    if (( switched )) && [[ -n "$previous" ]]; then
        ln -s "$previous" "$app_root/.rollback-$release_id"
        mv -Tf "$app_root/.rollback-$release_id" "$app_root/current"
        reload_services
        "$php_bin" "$previous/artisan" queue:restart
        echo 'Previous code release restored. Database migrations were NOT rolled back.'
    elif (( switched )); then
        "$php_bin" "$release/artisan" down --retry=60
        echo 'First deployment failed health check; maintenance remains enabled for investigation.'
    fi
    if (( maintenance )) && { (( ! switched )) || [[ -n "$previous" ]]; }; then
        "$php_bin" "${previous:-$release}/artisan" up
    fi
    rm -f "$archive"
    echo "Deployment failed; inspect release $release"
    exit "${result:-1}"
}
trap on_failure ERR INT TERM

mkdir -p "$release"
tar -xzf "$archive" -C "$release"
[[ "$(cat "$release/REVISION")" == "${release_id:0:40}" ]] || { echo 'Artifact revision mismatch'; false; }
[[ -f "$release/vendor/autoload.php" && -f "$release/public/build/manifest.json" ]] || { echo 'Artifact incomplete'; false; }
mkdir -p "$app_root/shared/storage/app/public" "$app_root/shared/storage/app/private" \
    "$app_root/shared/storage/framework/cache/data" "$app_root/shared/storage/framework/sessions" \
    "$app_root/shared/storage/framework/views" "$app_root/shared/storage/logs"
ln -s "$app_root/shared/.env" "$release/.env"
ln -s "$app_root/shared/storage" "$release/storage"
mkdir -p "$release/bootstrap/cache"
chmod -R g+rwX "$release/bootstrap/cache" "$app_root/shared/storage"
cd "$release"
composer check-platform-reqs --no-dev
"$php_bin" artisan package:discover --ansi
"$php_bin" artisan storage:link
"$php_bin" artisan config:cache
"$php_bin" artisan route:cache
"$php_bin" artisan view:cache

# Maintenance is shared with the previous release. Never seed or reset production data.
"$php_bin" artisan down --retry=60
maintenance=1
"$php_bin" artisan migrate --force --no-interaction
ln -s "$release" "$app_root/.current-$release_id"
mv -Tf "$app_root/.current-$release_id" "$app_root/current"
switched=1
reload_services
"$php_bin" artisan queue:restart
"$php_bin" artisan up
# Keep maintenance=1 until the health check passes, so the failure trap can restore availability.
http_code="$(curl --fail --silent --show-error --connect-timeout 10 --max-time 30 \
    --retry 5 --retry-delay 3 --retry-all-errors --output /dev/null --write-out '%{http_code}' "$health_url")"
[[ "$http_code" == 200 ]] || { echo "Health check returned HTTP $http_code, expected 200"; false; }
maintenance=0
trap - ERR INT TERM
rm -f "$archive"
echo "Deployment successful: $release_id"
