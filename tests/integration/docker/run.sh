#!/usr/bin/env bash
#
# One-shot end-to-end test harness for the Akeeba Release System.
#
# Provisions a throwaway Joomla site inside Docker (Apache + PHP-FPM + MySQL +
# Mailpit, all versions configurable) and runs the PHPUnit end-to-end suite
# against it over real HTTP. Every run starts from a completely clean slate, so
# a suite that fails or crashes mid-way never leaves a site behind in an unknown
# state.
#
# What it does, in order:
#   1. Scrub any existing containers, volumes and the previous web root.
#   2. Acquire the requested Joomla version (from ../inbox/ or by downloading).
#   3. Build and bring up the Dockerized stack.
#   4. Install Joomla using its installation/joomla.php CLI helper.
#   5. Point Joomla's mailer at Mailpit and normalise the site configuration.
#   6. Build ARS with `phing git` and install pkg_ars.
#   7. Create the release repository directory, then provision the ARS
#      fixtures and the ACL user matrix.
#   8. Run the PHPUnit end-to-end suite.
#
# Usage:  ./run.sh [options] [-- <extra PHPUnit args>]
#   -j, --joomla=VERSION   Override JOOMLA_VERSION (e.g. 6, 6.1, 6.1.2)
#   -p, --php=VERSION      Override PHP_VERSION (e.g. 8.1, 8.3, 8.5)
#       --matrix           Run every Joomla/PHP pair in JOOMLA_MATRIX, then exit
#   -f, --filter=NAME      Passed through to PHPUnit as --filter
#       --skip-build       Do not run `phing git`; use the newest package in release/
#       --down             Tear everything down and exit
#       --no-tests         Provision the site but do not run the suite
#       --keep-containers  Leave the stack running after the tests finish
#                          (default: the stack is brought down). Provisioning is
#                          the slow part, so this is how you iterate on a test.
#   -h, --help             Show this help
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Paths
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Absolute, so --help and --matrix keep working after the `cd` below. $BASH_SOURCE is whatever the
# caller typed, which stops resolving the moment we change directory.
SCRIPT_PATH="${SCRIPT_DIR}/$(basename "${BASH_SOURCE[0]}")"
INTEGRATION_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
REPO_ROOT="$(cd "${INTEGRATION_DIR}/../.." && pwd)"
RELEASE_DIR="${REPO_ROOT}/release"
INBOX_DIR="${INTEGRATION_DIR}/inbox"
WWW_DIR="${SCRIPT_DIR}/www"

cd "${SCRIPT_DIR}"

# ---------------------------------------------------------------------------
# Pretty logging
# ---------------------------------------------------------------------------
if [ -t 1 ]; then C_B='\033[0;34m'; C_G='\033[0;32m'; C_R='\033[0;31m'; C_Y='\033[0;33m'; C_0='\033[0m'; else C_B=; C_G=; C_R=; C_Y=; C_0=; fi
log()  { printf "${C_B}==>${C_0} %s\n" "$*"; }
ok()   { printf "${C_G}  ok${C_0} %s\n" "$*"; }
warn() { printf "${C_Y}  !!${C_0} %s\n" "$*"; }
die()  { printf "${C_R}ERROR:${C_0} %s\n" "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# docker compose wrapper (supports both `docker compose` and `docker-compose`)
# ---------------------------------------------------------------------------
if docker compose version >/dev/null 2>&1; then
	DC() { docker compose -f "${SCRIPT_DIR}/docker-compose.yml" "$@"; }
	DC_BIN="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
	DC() { docker-compose -f "${SCRIPT_DIR}/docker-compose.yml" "$@"; }
	DC_BIN="docker-compose"
else
	die "Docker Compose is not available. Install Docker Desktop or the compose plugin."
fi
command -v docker >/dev/null 2>&1 || die "Docker is not installed or not on PATH."

# ---------------------------------------------------------------------------
# Argument parsing
# ---------------------------------------------------------------------------
JOOMLA_OVERRIDE=""
PHP_OVERRIDE=""
SKIP_BUILD=0
ONLY_DOWN=0
RUN_TESTS=1
KEEP_CONTAINERS=0
RUN_MATRIX=0
PHPUNIT_ARGS=()

while [ $# -gt 0 ]; do
	case "$1" in
		-j|--joomla)     JOOMLA_OVERRIDE="$2"; shift 2;;
		--joomla=*)      JOOMLA_OVERRIDE="${1#*=}"; shift;;
		-p|--php)        PHP_OVERRIDE="$2"; shift 2;;
		--php=*)         PHP_OVERRIDE="${1#*=}"; shift;;
		--matrix)        RUN_MATRIX=1; shift;;
		-f|--filter)     PHPUNIT_ARGS+=("--filter" "$2"); shift 2;;
		--filter=*)      PHPUNIT_ARGS+=("--filter" "${1#*=}"); shift;;
		--skip-build)    SKIP_BUILD=1; shift;;
		--down)          ONLY_DOWN=1; shift;;
		--no-tests)      RUN_TESTS=0; shift;;
		--keep-containers) KEEP_CONTAINERS=1; shift;;
		-h|--help)       sed -n "2,32p" "${SCRIPT_PATH}" | sed 's/^# \{0,1\}//'; exit 0;;
		--)              shift; while [ $# -gt 0 ]; do PHPUNIT_ARGS+=("$1"); shift; done;;
		*)               die "Unknown option: $1 (use --help)";;
	esac
done

# ---------------------------------------------------------------------------
# Environment: create or top up .env from the committed template, then load it.
#
# The file may already exist and belong to an older harness. Rather than
# clobbering it (which would break whatever else reads it), we append the
# template's keys when the marker key is missing.
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
	cp env.dist .env
	log "Created .env from env.dist"
elif ! grep -qE '^JOOMLA_VERSION=' .env; then
	printf '\n# ---- appended by run.sh from env.dist ----\n' >> .env
	cat env.dist >> .env
	warn ".env predates this harness; appended the env.dist defaults to it"
fi

set -a; . ./.env; set +a
export PUID; PUID="$(id -u)"
export PGID; PGID="$(id -g)"
[ -n "${JOOMLA_OVERRIDE}" ] && export JOOMLA_VERSION="${JOOMLA_OVERRIDE}"
[ -n "${PHP_OVERRIDE}" ] && export PHP_VERSION="${PHP_OVERRIDE}"

: "${JOOMLA_VERSION:?JOOMLA_VERSION is not set}"
: "${JOOMLA_MATRIX:=5.4:8.1,8.5 6.0:8.3,8.5 6.1:8.3,8.5}"
: "${DB_PREFIX:=e2e_}"
: "${DB_NAME:=arse2e}"
: "${DB_ROOT_PASSWORD:=root}"
: "${HTTP_PORT:=8100}"
: "${DB_PORT:=33308}"
: "${MAILPIT_HTTP_PORT:=8135}"
: "${TEST_USER_PASSWORD:=test}"
: "${MAIL_FROM:=releases@example.test}"
: "${MAIL_FROM_NAME:=ARS Releases}"
: "${ARS_REPOSITORY:=arsrepo}"

# In-container CLI helpers ---------------------------------------------------
php_cli()   { DC exec -T php php "$@"; }
mysql_cli() { DC exec -T db mysql -uroot -p"${DB_ROOT_PASSWORD}" "$@"; }

# ---------------------------------------------------------------------------
# Matrix mode: re-invoke ourselves once per version and aggregate the result.
# Done before anything else touches Docker.
# ---------------------------------------------------------------------------
if [ "${RUN_MATRIX}" -eq 1 ]; then
	MATRIX_STATUS=0
	FAILED_VERSIONS=""
	FIRST=1
	# Each JOOMLA_MATRIX entry is either a bare Joomla version, which runs on the .env PHP_VERSION,
	# or VERSION:PHP[,PHP...] naming the PHP versions to pair it with. See env.dist for the policy
	# that decides which pairs are worth the wall-clock.
	for entry in ${JOOMLA_MATRIX}; do
		jver="${entry%%:*}"
		phps="${entry#*:}"
		[ "${phps}" = "${entry}" ] && phps="${PHP_VERSION}"

		for pver in ${phps//,/ }; do
			echo
			log "──────── matrix: Joomla ${jver} on PHP ${pver} ────────"
			# Build ARS once, on the first iteration only; the package does not change
			# between Joomla or PHP versions and `phing git` is not cheap.
			EXTRA=()
			if [ "${FIRST}" -eq 0 ] || [ "${SKIP_BUILD}" -eq 1 ]; then
				EXTRA+=("--skip-build")
			fi
			if [ "${#PHPUNIT_ARGS[@]}" -gt 0 ]; then
				"${SCRIPT_PATH}" --joomla="${jver}" --php="${pver}" ${EXTRA[@]+"${EXTRA[@]}"} -- "${PHPUNIT_ARGS[@]}" || {
					MATRIX_STATUS=1; FAILED_VERSIONS="${FAILED_VERSIONS} ${jver}/php${pver}"
				}
			else
				"${SCRIPT_PATH}" --joomla="${jver}" --php="${pver}" ${EXTRA[@]+"${EXTRA[@]}"} || {
					MATRIX_STATUS=1; FAILED_VERSIONS="${FAILED_VERSIONS} ${jver}/php${pver}"
				}
			fi
			FIRST=0
		done
	done
	echo
	if [ "${MATRIX_STATUS}" -eq 0 ]; then
		ok "Matrix green on: ${JOOMLA_MATRIX}"
	else
		die "Matrix FAILED on:${FAILED_VERSIONS}"
	fi
	exit "${MATRIX_STATUS}"
fi

# ---------------------------------------------------------------------------
# Teardown
# ---------------------------------------------------------------------------
scrub() {
	log "Scrubbing existing containers, volumes and web root"
	DC down --volumes --remove-orphans >/dev/null 2>&1 || true
	# The web root may contain files owned by the container user; remove via a
	# throwaway container to avoid host permission surprises.
	if [ -d "${WWW_DIR}" ]; then
		rm -rf "${WWW_DIR}" 2>/dev/null || \
			docker run --rm -v "${SCRIPT_DIR}:/work" alpine:3 rm -rf /work/www || true
	fi
	mkdir -p "${WWW_DIR}"
	ok "Clean slate"
}

teardown() {
	log "Bringing the Docker stack down"
	DC down --volumes --remove-orphans >/dev/null 2>&1 || true
	ok "Stack down"
}

if [ "${ONLY_DOWN}" -eq 1 ]; then
	scrub
	ok "Everything torn down."
	exit 0
fi

# ---------------------------------------------------------------------------
# Version helpers
# ---------------------------------------------------------------------------

# The oldest Joomla version ARS installs on. This MUST track $minimumJoomla in
# component/script.ars.php: below it, Joomla's installer refuses the package
# outright, so there is nothing a test could observe.
MIN_JOOMLA_VERSION="$(sed -nE "s/.*\\\$minimumJoomla[[:space:]]*=[[:space:]]*'([0-9.]+)'.*/\1/p" \
	"${REPO_ROOT}/component/script.ars.php" | head -1)"
: "${MIN_JOOMLA_VERSION:=5.4.0}"

# True (0) when $1 is strictly lower than $2, comparing dotted numeric versions.
version_lt() {
	[ "$1" = "$2" ] && return 1
	[ "$(printf '%s\n%s\n' "$1" "$2" | sort -V | head -1)" = "$1" ]
}

# Pad a version to three components. Without this, version_lt "8.1" "8.1.0" is TRUE under sort -V,
# so a perfectly valid `--php=8.1` would be rejected against a floor written as "8.1.0".
normalize_version() {
	local IFS=.
	# shellcheck disable=SC2086
	set -- $1
	printf '%s.%s.%s' "${1:-0}" "${2:-0}" "${3:-0}"
}

# The PHP range ARS declares, scraped from the installer script for the same reason as the Joomla
# floor above: one source of truth. The maximum is EXCLUSIVE, matching preflight().
MIN_PHP_VERSION="$(sed -nE "s/.*\\\$minimumPhp[[:space:]]*=[[:space:]]*'([0-9.]+)'.*/\1/p" \
	"${REPO_ROOT}/component/script.ars.php" | head -1)"
MAX_PHP_VERSION="$(sed -nE "s/.*\\\$maximumPhp[[:space:]]*=[[:space:]]*'([0-9.]+)'.*/\1/p" \
	"${REPO_ROOT}/component/script.ars.php" | head -1)"
: "${MIN_PHP_VERSION:=8.1.0}"
: "${MAX_PHP_VERSION:=8.7}"

# Reject a PHP version outside what ARS itself claims to support, before we spend minutes on it.
assert_php_in_ars_range() {
	local p n
	p="$1"
	n="$(normalize_version "${p}")"

	if version_lt "${n}" "$(normalize_version "${MIN_PHP_VERSION}")"; then
		die "PHP ${p} is below the minimum ARS supports (${MIN_PHP_VERSION}, from component/script.ars.php)."
	fi

	if ! version_lt "${n}" "$(normalize_version "${MAX_PHP_VERSION}")"; then
		die "PHP ${p} is at or above the exclusive maximum ARS supports (${MAX_PHP_VERSION}, from component/script.ars.php)."
	fi
}

# Joomla enforces its own PHP floor and refuses to install below it, so a pair like 6.1 on PHP 8.1
# would fail for a reason that has nothing to do with ARS. Read that floor out of the package we
# just extracted rather than hard-coding a table that would rot.
assert_php_supported_by_joomla() {
	local minPhp
	minPhp="$(sed -nE "s/.*define\\('JOOMLA_MINIMUM_PHP',[[:space:]]*'([0-9.]+)'\\).*/\1/p" \
		"${WWW_DIR}/index.php" 2>/dev/null | head -1)"

	[ -z "${minPhp}" ] && return 0

	if version_lt "$(normalize_version "${PHP_VERSION}")" "$(normalize_version "${minPhp}")"; then
		die "Joomla ${JOOMLA_RESOLVED} requires PHP ${minPhp} or later, but this run asked for PHP ${PHP_VERSION}."
	fi

	ok "PHP ${PHP_VERSION} satisfies Joomla ${JOOMLA_RESOLVED} (needs ${minPhp}+)"
}

# Reject a requested version that provably resolves below the minimum, before we
# scrub anything or hit the network. Works on full (5.4.3) and partial (5, 5.4)
# inputs; a bare major is allowed here because it resolves to the newest release
# on that major, and the authoritative check runs once the version is concrete.
precheck_requested_version() {
	local v="$1" major minMajor rest minor minMinor
	major="${v%%.*}"
	minMajor="${MIN_JOOMLA_VERSION%%.*}"
	case "${major}" in ''|*[!0-9]*) die "Invalid Joomla version '${v}'.";; esac

	if [ "${major}" -lt "${minMajor}" ]; then
		die "Joomla ${v} is not supported: ARS requires Joomla ${MIN_JOOMLA_VERSION} or later (component/script.ars.php)."
	fi

	if [ "${major}" -eq "${minMajor}" ]; then
		rest="${v#*.}"
		if [ "${rest}" != "${v}" ]; then
			minor="${rest%%.*}"
			minMinor="${MIN_JOOMLA_VERSION#*.}"; minMinor="${minMinor%%.*}"
			case "${minor}" in ''|*[!0-9]*) minor=0;; esac
			if [ "${minor}" -lt "${minMinor}" ]; then
				die "Joomla ${v} is not supported: ARS requires Joomla ${MIN_JOOMLA_VERSION} or later (component/script.ars.php)."
			fi
		fi
	fi
}

assert_supported_version() {
	if version_lt "$1" "${MIN_JOOMLA_VERSION}"; then
		die "Joomla $1 is not supported: ARS requires Joomla ${MIN_JOOMLA_VERSION} or later (component/script.ars.php)."
	fi
}

# ---------------------------------------------------------------------------
# Joomla acquisition: prefer inbox/, otherwise resolve + download.
# ---------------------------------------------------------------------------

# The list of published Joomla versions and their download URLs, served
# (gzip-compressed) by our own Panopticon checksums service.
#   Docs: https://getpanopticon.com/checksums/index.html
SOURCES_URL="https://getpanopticon.com/checksums/sources.json.gz"

resolve_joomla() {
	local want="$1"
	command -v jq     >/dev/null 2>&1 || die "jq is required to resolve the Joomla version."
	command -v gunzip >/dev/null 2>&1 || die "gunzip is required to resolve the Joomla version."

	local gz; gz="$(mktemp)"
	curl -fsSL "${SOURCES_URL}" -o "${gz}" || { rm -f "${gz}"; die "Could not download ${SOURCES_URL}"; }

	local result
	result="$(gunzip -c "${gz}" | jq -r --arg want "${want}" '
		[ .[]
		  | select(.cms == "joomla")
		  | select(.version | test("^[0-9]+\\.[0-9]+\\.[0-9]+$"))
		  | select(.version == $want or (.version | startswith($want + "."))) ]
		| sort_by(.version | split(".") | map(tonumber))
		| if length == 0 then empty else (last | "\(.version)\t\(.url)") end
	')"
	rm -f "${gz}"
	printf '%s' "${result}"
}

find_inbox_package() {
	local prefix="$1" best="" bestver="" f ver
	shopt -s nullglob
	for f in "${INBOX_DIR}"/Joomla_"${prefix}"*Full_Package.zip; do
		[ -f "${f}" ] || continue
		ver="$(basename "${f}" | sed -E 's/^Joomla_([0-9]+\.[0-9]+\.[0-9]+).*/\1/')"
		# Guard against "5.4" matching "5.4.3" but also "5.40.0": require the
		# requested string to be the whole version or a dot-delimited prefix.
		case "${ver}" in
			"${prefix}"|"${prefix}".*) ;;
			*) continue;;
		esac
		if [ -z "${bestver}" ] || [ "$(printf '%s\n%s\n' "${bestver}" "${ver}" | sort -V | tail -1)" = "${ver}" ]; then
			best="${f}"; bestver="${ver}"
		fi
	done
	shopt -u nullglob
	[ -n "${best}" ] && { echo "${best}"; return 0; }
	return 1
}

JOOMLA_ZIP=""
JOOMLA_RESOLVED=""
acquire_joomla() {
	log "Acquiring Joomla ${JOOMLA_VERSION}"
	if JOOMLA_ZIP="$(find_inbox_package "${JOOMLA_VERSION}")"; then
		JOOMLA_RESOLVED="$(basename "${JOOMLA_ZIP}" | sed -E 's/^Joomla_([0-9]+\.[0-9]+\.[0-9]+).*/\1/')"
		ok "Using inbox package $(basename "${JOOMLA_ZIP}")"
		return 0
	fi
	local resolved; resolved="$(resolve_joomla "${JOOMLA_VERSION}")"
	[ -n "${resolved}" ] || die "Could not resolve a stable Joomla version for '${JOOMLA_VERSION}'."
	local url
	JOOMLA_RESOLVED="${resolved%%$'\t'*}"
	url="${resolved#*$'\t'}"
	local fname="Joomla_${JOOMLA_RESOLVED}-Stable-Full_Package.zip"
	JOOMLA_ZIP="${INBOX_DIR}/${fname}"
	log "Downloading Joomla ${JOOMLA_RESOLVED}"
	curl -fL --progress-bar -o "${JOOMLA_ZIP}" "${url}" \
		|| { rm -f "${JOOMLA_ZIP}"; die "Download failed: ${url}"; }
	ok "Downloaded ${fname} to inbox/"
}

extract_joomla() {
	log "Extracting Joomla into the web root"
	command -v unzip >/dev/null 2>&1 || die "unzip is required to extract the Joomla package."
	unzip -q -o "${JOOMLA_ZIP}" -d "${WWW_DIR}"
	ok "Joomla ${JOOMLA_RESOLVED} extracted"
}

# ---------------------------------------------------------------------------
# Provisioning steps
# ---------------------------------------------------------------------------
bring_up_stack() {
	log "Building and starting the stack (PHP ${PHP_VERSION}, Apache ${APACHE_VERSION}, MySQL ${MYSQL_VERSION}, Mailpit ${MAILPIT_VERSION})"
	DC build >/dev/null
	DC up -d

	log "Waiting for MySQL"
	local tries=0
	until mysql_cli -e "SELECT 1;" >/dev/null 2>&1; do
		tries=$((tries + 1))
		[ "${tries}" -gt 60 ] && die "MySQL did not become ready in time."
		sleep 2
	done
	ok "Database is up"

	log "Waiting for Mailpit"
	tries=0
	until curl -fsS -o /dev/null "http://localhost:${MAILPIT_HTTP_PORT}/api/v1/info" 2>/dev/null; do
		tries=$((tries + 1))
		[ "${tries}" -gt 30 ] && die "Mailpit did not become ready on port ${MAILPIT_HTTP_PORT}."
		sleep 2
	done
	ok "Mailpit is up"

	log "Waiting for the web front-end"
	tries=0
	until curl -fsS -o /dev/null "http://localhost:${HTTP_PORT}/index.php" 2>/dev/null; do
		tries=$((tries + 1))
		[ "${tries}" -gt 30 ] && die "Apache did not become ready on port ${HTTP_PORT}."
		sleep 2
	done
	ok "Apache is up"
}

install_joomla() {
	log "Installing Joomla via installation/joomla.php CLI"
	# Install with a strong temporary admin password (Joomla enforces a policy),
	# then reset it to the short test password below via the console app.
	local tmp_pw="End2End-Test-Passw0rd"
	local -a opts=(
		installation/joomla.php install
		--site-name "${SITE_NAME}"
		--admin-user "${ADMIN_NAME}"
		--admin-username "${ADMIN_USERNAME}"
		--admin-password "${tmp_pw}"
		--admin-email "${ADMIN_EMAIL}"
		--db-type mysqli
		--db-host db
		--db-user "${DB_USER}"
		--db-pass "${DB_PASSWORD}"
		--db-name "${DB_NAME}"
		--db-prefix "${DB_PREFIX}"
		--db-encryption 0
	)
	if ! version_lt "${JOOMLA_RESOLVED}" "5.0.0"; then
		opts+=(--public-folder "")
	fi
	php_cli "${opts[@]}" || die "Joomla CLI installation failed."
	# Joomla refuses to run while the installation directory exists.
	rm -rf "${WWW_DIR}/installation"
	ok "Joomla installed"
}

# Set a property in configuration.php, inserting it if absent.
#
# We patch the file rather than using `cli/joomla.php config:set` because that
# command validates every key against the keys already present in the freshly
# written configuration.php, and which mail keys the installer writes has varied
# across the 5.4 → 6.1 range. Patching is version-proof.
#
#   set_config <name> <PHP literal>      e.g. set_config mailer "'smtp'"
set_config() {
	local key="$1" value="$2" cfg="${WWW_DIR}/configuration.php"

	[ -f "${cfg}" ] || die "configuration.php not found; did the installation succeed?"

	if grep -qE "public[[:space:]]+\\\$${key}[[:space:]]*=" "${cfg}"; then
		awk -v key="${key}" -v val="${value}" '
			$0 ~ ("public[ \t]+\\$" key "[ \t]*=") { print "\tpublic $" key " = " val ";"; next }
			{ print }
		' "${cfg}" > "${cfg}.tmp" && mv "${cfg}.tmp" "${cfg}"
	else
		awk -v key="${key}" -v val="${value}" '
			{ print }
			/class[[:space:]]+JConfig/ && !inserted { print "\tpublic $" key " = " val ";"; inserted = 1 }
		' "${cfg}" > "${cfg}.tmp" && mv "${cfg}.tmp" "${cfg}"
	fi
}

configure_site() {
	log "Pointing Joomla's mailer at Mailpit and normalising the configuration"

	# Real SMTP to the Mailpit sink. This is the whole point of using Mailpit
	# rather than stubbing Mail::Send(): everything from mailer configuration
	# through PHPMailer, the SMTP dialogue, MIME encoding and multi-recipient
	# handling is exercised for real, and inspected afterwards over the REST API.
	set_config mailonline "true"
	set_config mailer     "'smtp'"
	set_config smtphost   "'mailpit'"
	set_config smtpport   "1025"
	set_config smtpsecure "'none'"
	set_config smtpauth   "false"
	set_config smtpuser   "''"
	set_config smtppass   "''"
	set_config mailfrom   "'${MAIL_FROM}'"
	set_config fromname   "'${MAIL_FROM_NAME}'"

	# SEF off. Tests address the site as index.php?option=com_ars&…, and asserting
	# on a raw Location header is far clearer when Joomla has not rewritten the
	# URL on the way out.
	set_config sef         "false"
	set_config sef_rewrite "false"
	set_config sef_suffix  "false"

	# Deterministic, silent, and fast.
	set_config debug     "false"
	set_config caching   "0"
	set_config gzip      "false"
	set_config error_reporting "'none'"
	set_config offset    "'UTC'"
	set_config log_path  "'/var/www/html/administrator/logs'"
	set_config tmp_path  "'/var/www/html/tmp'"

	ok "Configuration written"

	log "Setting the Super User password"
	php_cli cli/joomla.php user:reset-password \
		--username "${ADMIN_USERNAME}" --password "${ADMIN_PASSWORD}" \
		|| die "Could not set the Super User's password."
	ok "Super User ${ADMIN_USERNAME} ready (password '${ADMIN_PASSWORD}')"

	# Allow self-registration and put new users in Registered. Several fixtures
	# assume ordinary accounts exist and can log in.
	log "Normalising com_users parameters"
	local comusers='{"allowUserRegistration":"1","new_usertype":"2","guest_usergroup":"9","sendpassword":"1","useractivation":"0","mail_to_admin":"0","captcha":"","frontend_userparams":"1","site_language":"0","change_login_name":"0","reset_count":"10","reset_time":"1","minimum_length":"4","minimum_integers":"0","minimum_symbols":"0","minimum_uppercase":"0","save_history":"1","history_limit":5,"mailSubjectPrefix":"","mailBodySuffix":""}'
	mysql_cli "${DB_NAME}" <<SQL || die "com_users normalisation failed."
UPDATE \`${DB_PREFIX}extensions\` SET params = '${comusers}' WHERE element = 'com_users' AND type = 'component';
UPDATE \`${DB_PREFIX}extensions\` SET enabled = 0 WHERE element = 'debug';
SQL
	ok "com_users normalised"
}

build_ars() {
	if [ "${SKIP_BUILD}" -eq 1 ]; then
		warn "Skipping build (--skip-build); using the existing package in release/"
		return 0
	fi
	command -v phing >/dev/null 2>&1 || die "phing is not on PATH (needed to build ARS). Use --skip-build to reuse an existing package."
	log "Building ARS with 'phing git'"
	( cd "${REPO_ROOT}" && phing git ) || die "phing git failed."
	ok "ARS built"
}

pick_package() {
	local pkg
	pkg="$(ls -t "${RELEASE_DIR}"/pkg_ars-*.zip 2>/dev/null | head -1 || true)"
	[ -n "${pkg}" ] || die "No package found in ${RELEASE_DIR}. Build first (drop --skip-build)."
	echo "${pkg}"
}

install_ars() {
	local pkg; pkg="$(pick_package)"
	log "Installing $(basename "${pkg}") via cli/joomla.php extension:install"
	cp "${pkg}" "${WWW_DIR}/ars-install-package.zip"
	php_cli cli/joomla.php extension:install --path=/var/www/html/ars-install-package.zip \
		|| { rm -f "${WWW_DIR}/ars-install-package.zip"; die "ARS installation failed."; }
	rm -f "${WWW_DIR}/ars-install-package.zip"
	ok "ARS installed"
}

create_repository_dir() {
	# ARS resolves each category's `directory` column relative to JPATH_ROOT and
	# serves release files from it, but nothing in the installer creates that
	# directory — it is expected to already exist. Create it now, before the
	# fixture provisioner (which writes release files under it) runs.
	log "Creating the release repository directory (${ARS_REPOSITORY})"
	mkdir -p "${WWW_DIR}/${ARS_REPOSITORY}"
	ok "Repository directory ready"
}

write_test_config() {
	log "Writing tests/integration/config.php"
	cat > "${INTEGRATION_DIR}/config.php" <<PHP
<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * GENERATED by tests/integration/docker/run.sh to match docker/.env and the
 * provisioned Joomla version. Not tracked in git; config.dist.php holds the same
 * defaults and is used when this file is absent.
 */

defined('_JEXEC') or die;

return [
	'site'    => [
		// The Apache front-end, as seen from the host running PHPUnit.
		'url'         => 'http://localhost:${HTTP_PORT}',
		// Reachable from inside the compose network (server-side fetches).
		'internalUrl' => 'http://web',
		'root'        => __DIR__ . '/docker/www',
	],
	'db'      => [
		'host'   => '127.0.0.1',
		'port'   => ${DB_PORT},
		'name'   => '${DB_NAME}',
		'user'   => '${DB_USER}',
		'pass'   => '${DB_PASSWORD}',
		'prefix' => '${DB_PREFIX}',
	],
	'mailpit' => [
		'url' => 'http://localhost:${MAILPIT_HTTP_PORT}',
	],
	'docker'  => [
		'composeBin'  => '${DC_BIN}',
		'composeFile' => __DIR__ . '/docker/docker-compose.yml',
		'phpService'  => 'php',
	],
	'users'   => [
		'adminUsername' => '${ADMIN_USERNAME}',
		'adminPassword' => '${ADMIN_PASSWORD}',
		'password'      => '${TEST_USER_PASSWORD}',
	],
	'mail'    => [
		'from'     => '${MAIL_FROM}',
		'fromName' => '${MAIL_FROM_NAME}',
	],
	'joomlaVersion' => '${JOOMLA_RESOLVED}',
	'repository'    => '${ARS_REPOSITORY:-arsrepo}',
];
PHP
	ok "config.php written"
}

provision_fixtures() {
	log "Provisioning ARS fixtures and the ACL user matrix"
	php "${INTEGRATION_DIR}/provision.php" || die "Fixture provisioning failed."
	ok "Fixtures provisioned"
}

run_tests() {
	[ "${RUN_TESTS}" -eq 1 ] || { warn "Skipping tests (--no-tests). Site is up at http://localhost:${HTTP_PORT}"; return 0; }
	command -v phpunit >/dev/null 2>&1 || die "phpunit is not on PATH."
	log "Running the end-to-end suite"
	( cd "${REPO_ROOT}" && phpunit -c phpunit-integration.xml ${PHPUNIT_ARGS[@]+"${PHPUNIT_ARGS[@]}"} )
}

# Surface the PHP error log when something went wrong — a fatal inside a request
# is otherwise invisible, since display_errors is off.
dump_php_errors() {
	local logfile="${WWW_DIR}/php-errors.log"
	[ -s "${logfile}" ] || return 0
	echo
	warn "PHP error log (${logfile}), last 40 lines:"
	tail -40 "${logfile}"
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
log "ARS end-to-end harness — Joomla ${JOOMLA_VERSION} on PHP ${PHP_VERSION}"
precheck_requested_version "${JOOMLA_VERSION}"
assert_php_in_ars_range "${PHP_VERSION}"
scrub
acquire_joomla
assert_supported_version "${JOOMLA_RESOLVED}"
extract_joomla
assert_php_supported_by_joomla
bring_up_stack
install_joomla
configure_site
build_ars
install_ars
create_repository_dir
write_test_config
provision_fixtures

# Run the suite but keep going even if it fails, so the stack is still torn down
# afterwards. The suite's exit status is preserved and re-emitted at the very end.
TEST_STATUS=0
run_tests || TEST_STATUS=$?
[ "${TEST_STATUS}" -eq 0 ] || dump_php_errors

echo
if [ "${KEEP_CONTAINERS}" -eq 1 ] || [ "${RUN_TESTS}" -eq 0 ]; then
	ok "Done. Site remains up for iterating on individual tests:"
	echo "     Apache:  http://localhost:${HTTP_PORT}"
	echo "     Mailpit: http://localhost:${MAILPIT_HTTP_PORT}"
	echo "     phpunit -c phpunit-integration.xml --filter SomeTest"
	echo "     ${SCRIPT_DIR}/run.sh --down   # to tear it all down"
else
	teardown
	ok "Done. Re-run with --keep-containers to leave the site up for troubleshooting."
fi

exit "${TEST_STATUS}"
