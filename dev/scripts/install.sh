#!/bin/sh
# Installs the local multisite, activates the plugins and creates test content.
# Runs inside the cli container: docker compose run --rm cli sh /scripts/install.sh
set -e
cd /var/www/html

until wp db check >/dev/null 2>&1; do echo "waiting for the database..."; sleep 3; done

if ! wp core is-installed --network 2>/dev/null; then
	wp core multisite-install --url=localhost:8888 --title="Local network" \
		--admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email=admin@example.test --skip-email
fi

# Rewrite rules for a subdirectory multisite.
cat > .htaccess <<'HT'
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
HT

if [ -d wp-content/plugins/elementor ]; then
	wp plugin activate elementor --network
fi
wp plugin activate a11y-content-checker --network

SITE=http://localhost:8888/demo/
wp site list --field=url | grep -q "$SITE" || wp site create --slug=demo --title="Demo site"
W="wp --url=$SITE"
$W rewrite structure '/%postname%/' >/dev/null

if [ -z "$($W post list --post_type=any --meta_key=_a11ycc_fixture --format=ids)" ]; then
	$W post create --post_status=publish --post_title="Notice with issues" --meta_input='{"_a11ycc_fixture":"1"}' --post_content='
<!-- wp:heading --><h2>Scheduled maintenance</h2><!-- /wp:heading -->
<!-- wp:heading {"level":4} --><h4>Details</h4><!-- /wp:heading -->
<!-- wp:paragraph --><p><strong>When it happens</strong></p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>O SERVIDOR DE ARQUIVOS FICARÁ FORA DO AR NESTA QUINTA-FEIRA À NOITE</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Read the <a href="/wp-content/uploads/manual.pdf">user guide</a>. To open a ticket, <a href="/tickets">click here</a>.</p><!-- /wp:paragraph -->
<!-- wp:html --><img src="/wp-includes/images/w-logo-blue.png"><!-- /wp:html -->'

	$W post create --post_status=publish --post_title="News item without issues" --meta_input='{"_a11ycc_fixture":"1"}' --post_content='
<!-- wp:paragraph --><p>The wireless network now covers every classroom on campus. The change answers a long-standing request from students and teachers, who reported frequent connection drops during classes.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>How to connect</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Use the eduroam network with your institutional account. See the <a href="/wp-content/uploads/guide.pdf">connection guide (PDF, 1 MB)</a>.</p><!-- /wp:paragraph -->'

	if [ -d wp-content/plugins/elementor ]; then
		# Elementor page: H4 right after the H1, generic link and image without alt.
		ID=$($W post create --porcelain --post_type=page --post_status=publish --post_title="Services (Elementor)" --meta_input='{"_a11ycc_fixture":"1"}')
		$W post meta update "$ID" _elementor_edit_mode builder
		$W post meta update "$ID" _elementor_template_type wp-page
		$W post meta update "$ID" _elementor_data '[{"id":"c0a1b2c","elType":"container","settings":{},"elements":[
			{"id":"w1a2b3c","elType":"widget","widgetType":"heading","settings":{"title":"Services","header_size":"h1"},"elements":[]},
			{"id":"w2a2b3c","elType":"widget","widgetType":"heading","settings":{"title":"Network and internet","header_size":"h4"},"elements":[]},
			{"id":"w3a2b3c","elType":"widget","widgetType":"text-editor","settings":{"editor":"<p>Request VPN access on the portal. <a href=\"/vpn\">Learn more</a></p>"},"elements":[]},
			{"id":"w4a2b3c","elType":"widget","widgetType":"html","settings":{"html":"<img src=\"/wp-includes/images/w-logo-blue.png\">"},"elements":[]}
		]}]'
	fi
fi

echo "Initial check of the test content:"
$W a11ycc check
echo "Ready: $SITE (dashboard: ${SITE}wp-admin/)"
