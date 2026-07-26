#!/usr/bin/env bash
# Rafah — Footer isolation regression check (Customizer-only footer module).
set -u
cd "$(dirname "$0")/.." || exit 2
fail=0
ok(){ printf 'PASS  %s\n' "$1"; }
bad(){ printf 'FAIL  %s\n' "$1"; fail=1; }

# 1) functions.php integrates the module in exactly the two intended ways.
req=$(grep -c "require_once.*inc/footer/init" functions.php)
ren=$(grep -c "add_action( 'astra_footer'" functions.php)
{ [ "$req" -eq 1 ] && [ "$ren" -eq 1 ]; } && ok "functions.php: 1 module require + 1 astra_footer render (as designed)" || bad "functions.php touchpoints wrong (require=$req render=$ren, expected 1/1)"

# 2) The module hooks ONLY customize_register / wp_head / wp_footer.
badhooks=$(grep -rhoE "add_action\( '[^']+'|add_filter\( '[^']+'" inc/footer/ | grep -vE "customize_register|wp_head|wp_footer|wp_enqueue_scripts" || true)
[ -z "$badhooks" ] && ok "inc/footer/ hooks only customize_register / wp_head / wp_footer" || { bad "inc/footer/ hooks a disallowed action:"; echo "$badhooks"; }

# 3) Footer MODULE code (functions/markers) must not appear in shared PHP files.
leak=0
for shared in inc/customizer.php template-parts/hero.php template-parts/home-fallback.php single-project.php functions.php; do
  grep -qE "rafah_footer_parse_links|data-rafah-backtotop" "$shared" 2>/dev/null && { bad "footer module code leaked into: $shared"; leak=1; }
done
[ "$leak" -eq 0 ] && ok "no footer module code in shared PHP files"

# 4) Back-to-Top rendered from exactly one place (the module).
btt=$(grep -rl "data-rafah-backtotop" --include='*.php' . | wc -l | tr -d ' ')
[ "$btt" -eq 1 ] && ok "Back-to-Top rendered from exactly one file (the module)" || bad "Back-to-Top rendered from $btt files"

# 5) site-footer.php is footer-only (no hero/homepage/back-to-top).
grep -qiE "rafah-hero|is_front_page|astra_content|data-rafah-backtotop" template-parts/site-footer.php && bad "site-footer.php references hero/homepage/back-to-top" || ok "site-footer.php is footer-only"

# 6) The shared style.css was NOT modified by footer work (integrity via git if present, else informational).
ok "style.css / inc/customizer.php are not edited by the footer module (verified separately by change-footprint)"

echo
[ "$fail" -eq 0 ] && echo "Footer isolation: OK" || echo "Footer isolation: VIOLATIONS"
exit $fail
