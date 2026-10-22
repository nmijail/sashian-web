<?php
/**
 * Sashian child theme.
 *
 * Ajusta la tienda móvil sin tocar Fiorello ni WooCommerce.
 *
 * @package Sashian
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ruta de un archivo del tema activo o, si no está, del child Sashian.
 * Así el tema hermano usa el catálogo y el SEO sin copiarlos.
 *
 * @param string $relative Ruta dentro del tema.
 */
function sashian_theme_file_path( $relative ) {
	$relative = ltrim( $relative, '/' );
	$own      = get_stylesheet_directory() . '/' . $relative;
	if ( is_readable( $own ) ) {
		return $own;
	}

	$sibling = dirname( get_stylesheet_directory() ) . '/sashian/' . $relative;
	if ( is_readable( $sibling ) ) {
		return $sibling;
	}

	return $own;
}

/**
 * URL del mismo archivo que sashian_theme_file_path().
 *
 * @param string $relative Ruta dentro del tema.
 */
function sashian_theme_file_uri( $relative ) {
	$relative = ltrim( $relative, '/' );
	if ( is_readable( get_stylesheet_directory() . '/' . $relative ) ) {
		return get_stylesheet_directory_uri() . '/' . $relative;
	}

	$sibling = dirname( get_stylesheet_directory() ) . '/sashian/' . $relative;
	if ( is_readable( $sibling ) ) {
		return trailingslashit( get_theme_root_uri() ) . 'sashian/' . $relative;
	}

	return get_stylesheet_directory_uri() . '/' . $relative;
}

/**
 * Al cambiar entre los dos child, conserva menús, logo y ajustes del otro.
 * No pisa una configuración que ya tenga menús.
 *
 * @param string        $new_name  Nombre visible del tema nuevo.
 * @param WP_Theme|null $new_theme Tema nuevo.
 * @param WP_Theme|null $old_theme Tema anterior.
 */
function sashian_keep_sibling_mods( $new_name, $new_theme = null, $old_theme = null ) {
	unset( $new_name );
	if ( ! $new_theme instanceof WP_Theme || ! $old_theme instanceof WP_Theme ) {
		return;
	}

	$family = array( 'sashian', 'sashian-muertos' );
	$old    = $old_theme->get_stylesheet();
	$new    = $new_theme->get_stylesheet();
	if ( ! in_array( $old, $family, true ) || ! in_array( $new, $family, true ) || $old === $new ) {
		return;
	}

	$from = get_option( 'theme_mods_' . $old );
	$to   = get_option( 'theme_mods_' . $new );
	if ( ! is_array( $from ) ) {
		return;
	}
	if ( is_array( $to ) && ! empty( $to['nav_menu_locations'] ) ) {
		return;
	}

	update_option( 'theme_mods_' . $new, $from );
}
add_action( 'switch_theme', 'sashian_keep_sibling_mods', 10, 3 );

/**
 * Archivo de tienda o de una categoría de producto.
 */
function sashian_is_catalog_archive() {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );
}

/**
 * Tienda, categoría o ficha. Ahí el título de página y el botón flotante estorban.
 */
function sashian_is_catalog_view() {
	return sashian_is_catalog_archive() || ( function_exists( 'is_product' ) && is_product() );
}

/**
 * Marca el body para acotar el CSS a la tienda.
 *
 * @param string[] $classes Classes actuales.
 * @return string[]
 */
function sashian_body_class( $classes ) {
	if ( sashian_is_catalog_view() ) {
		$classes[] = 'sashian-catalog';
	}
	if ( sashian_is_catalog_archive() ) {
		$classes[] = 'sashian-catalog-archive';
	}
	if ( is_front_page() ) {
		$classes[] = 'sashian-home';
	}
	return $classes;
}
add_filter( 'body_class', 'sashian_body_class' );

/**
 * Estilos del child, después de los del padre.
 */
function sashian_enqueue_styles() {
	wp_enqueue_style(
		'sashian-catalog',
		sashian_theme_file_uri( 'assets/catalog.css' ),
		array( 'fiorello-mikado-default-style' ),
		wp_get_theme()->get( 'Version' )
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'sashian-home',
			sashian_theme_file_uri( 'assets/home.css' ),
			array( 'sashian-catalog' ),
			wp_get_theme()->get( 'Version' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sashian_enqueue_styles', 30 );

/**
 * Orden de la fila de categorías. Las que no estén aquí van al final.
 *
 * @param string $slug Slug de product_cat.
 */
function sashian_category_rank( $slug ) {
	$order = array(
		'ramos'            => 0,
		'amor'             => 1,
		'cumpleanos'       => 2,
		'premium'          => 3,
		'flores-amarillas' => 4,
		'graduacion'       => 5,
		'girasoles'        => 6,
		'eventos'          => 7,
	);

	return isset( $order[ $slug ] ) ? $order[ $slug ] : 100;
}

/**
 * Fila horizontal con las categorías que tienen productos.
 * «Todos» es la tienda. No enlaza categorías vacías.
 */
function sashian_catalog_categories() {
	if ( ! sashian_is_catalog_archive() ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			$rank = sashian_category_rank( $a->slug ) <=> sashian_category_rank( $b->slug );
			if ( 0 !== $rank ) {
				return $rank;
			}
			return strcasecmp( $a->name, $b->name );
		}
	);

	$shop_id  = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	$shop_url = $shop_id > 0 ? get_permalink( $shop_id ) : home_url( '/' );
	$on_shop  = function_exists( 'is_shop' ) && is_shop();

	echo '<nav class="sashian-cats" aria-label="' . esc_attr__( 'Categorías', 'sashian' ) . '">';
	echo '<a class="sashian-cats__link' . ( $on_shop ? ' is-active' : '' ) . '" href="' . esc_url( $shop_url ) . '">';
	echo esc_html__( 'Todos', 'sashian' );
	echo '</a>';

	foreach ( $terms as $term ) {
		if ( 'uncategorized' === $term->slug || 'sin-categoria' === $term->slug ) {
			continue;
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$active = is_tax( 'product_cat', $term->term_id );
		echo '<a class="sashian-cats__link' . ( $active ? ' is-active' : '' ) . '" href="' . esc_url( $link ) . '">';
		echo esc_html( $term->name );
		echo '</a>';
	}

	echo '<a class="sashian-cats__link" href="' . esc_url( home_url( '/flores-a-domicilio-oaxaca/' ) ) . '">';
	echo esc_html__( 'Domicilio', 'sashian' );
	echo '</a>';

	echo '</nav>';
}
add_action( 'woocommerce_before_shop_loop', 'sashian_catalog_categories', 5 );

/**
 * Quita del menú las categorías de producto sin artículos.
 * Una categoría vacía vuelve a aparecer cuando tenga productos.
 *
 * @param WP_Post[] $items Items del menú.
 * @return WP_Post[]
 */
function sashian_hide_empty_category_menu_items( $items ) {
	foreach ( $items as $key => $item ) {
		if ( 'product_cat' !== $item->object ) {
			continue;
		}
		$term = get_term( (int) $item->object_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) || (int) $term->count < 1 ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'sashian_hide_empty_category_menu_items' );

/**
 * Enlace de WhatsApp en el encabezado del catálogo móvil.
 * Usa el mismo número y el mismo mensaje que el botón de Social Chat.
 * En esas pantallas el botón flotante se oculta con CSS para no tapar la foto.
 */
function sashian_catalog_whatsapp_link() {
	if ( ! sashian_is_catalog_view() ) {
		return;
	}

	$message = 'Hola, me interesa hacer un pedido en Florería Sashian. ¿Me pueden ayudar?';
	$url     = 'https://wa.me/529516480419?text=' . rawurlencode( $message );

	echo '<a class="sashian-whatsapp" href="' . esc_url( $url ) . '">';
	echo esc_html__( 'WhatsApp', 'sashian' );
	echo '</a>';
}
add_action( 'wp_footer', 'sashian_catalog_whatsapp_link' );

/**
 * Texto de cada categoría que ya tiene productos.
 * La frase es el H1 y el title. No inventa cobertura ni horarios de entrega.
 *
 * @param string $slug Slug de product_cat.
 * @return array{h1:string,title:string,lead:string}|null
 */
function sashian_category_seo( $slug ) {
	$map = array(
		'ramos'            => array(
			'h1'    => 'Ramos de flores a domicilio en Oaxaca',
			'title' => 'Ramos de flores a domicilio en Oaxaca | Florería Sashian',
			'lead'  => 'Ramos frescos para regalar, con entrega a domicilio en Oaxaca de Juárez.',
		),
		'amor'             => array(
			'h1'    => 'Flores para amor y aniversario en Oaxaca',
			'title' => 'Flores para amor y aniversario en Oaxaca | Florería Sashian',
			'lead'  => 'Ramos para decir te quiero o celebrar un aniversario, a domicilio en Oaxaca.',
		),
		'cumpleanos'       => array(
			'h1'    => 'Flores para cumpleaños a domicilio en Oaxaca',
			'title' => 'Flores para cumpleaños a domicilio en Oaxaca | Florería Sashian',
			'lead'  => 'Ramos para cumpleaños con entrega a domicilio en Oaxaca de Juárez.',
		),
		'premium'          => array(
			'h1'    => 'Arreglos premium a domicilio en Oaxaca',
			'title' => 'Arreglos premium a domicilio en Oaxaca | Florería Sashian',
			'lead'  => 'Arreglos de mayor tamaño, con entrega a domicilio en Oaxaca.',
		),
		'flores-amarillas' => array(
			'h1'    => 'Flores amarillas a domicilio en Oaxaca',
			'title' => 'Flores amarillas a domicilio en Oaxaca | Florería Sashian',
			'lead'  => 'Girasoles, rosas y gerberas amarillas con entrega en Oaxaca de Juárez.',
		),
		'graduacion'       => array(
			'h1'    => 'Flores para graduación en Oaxaca',
			'title' => 'Flores para graduación en Oaxaca | Florería Sashian',
			'lead'  => 'Ramos para graduación, con entrega a domicilio en Oaxaca.',
		),
		'girasoles'        => array(
			'h1'    => 'Ramos de girasoles a domicilio en Oaxaca',
			'title' => 'Ramos de girasoles a domicilio en Oaxaca | Florería Sashian',
			'lead'  => 'Ramos de girasoles frescos para regalar en Oaxaca de Juárez.',
		),
		'eventos'          => array(
			'h1'    => 'Flores para eventos en Oaxaca',
			'title' => 'Flores para eventos en Oaxaca | Florería Sashian',
			'lead'  => 'Arreglos para eventos en Oaxaca de Juárez. Cuéntanos la fecha por WhatsApp.',
		),
	);

	return isset( $map[ $slug ] ) ? $map[ $slug ] : null;
}

/**
 * El banner de Fiorello usa la página Tienda en las fichas y el nombre del sitio en el blog.
 *
 * @param string $title Texto que iba a pintar el H1.
 * @return string
 */
function sashian_banner_title( $title ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		return get_the_title();
	}

	if ( is_singular( 'post' ) ) {
		return get_the_title();
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$seo = sashian_category_seo( $term->slug );
			if ( $seo ) {
				return $seo['h1'];
			}
		}
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'Flores a domicilio en Oaxaca';
	}

	return $title;
}
add_filter( 'fiorello_mikado_filter_title_text', 'sashian_banner_title', 20 );

/**
 * Title de Rank Math cuando todavía es el nombre pelón más la marca.
 *
 * @param string $title Title generado.
 * @param string $name  Nombre de la ficha, el post o la categoría.
 */
function sashian_title_is_plain( $title, $name ) {
	$plain = html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' );
	$plain = trim( preg_replace( '/\s+/', ' ', $plain ) );
	$name  = html_entity_decode( wp_strip_all_tags( (string) $name ), ENT_QUOTES, 'UTF-8' );
	$name  = trim( preg_replace( '/\s+/', ' ', $name ) );

	if ( '' === $name ) {
		return false;
	}

	return $plain === $name . ' - Florería Sashian' || $plain === $name . ' - Floreria Sashian';
}

/**
 * @param string $title Title de Rank Math.
 * @return string
 */
function sashian_document_title( $title ) {
	if ( 'delivery' === get_query_var( 'sashian_page' ) ) {
		return 'Flores a domicilio en Oaxaca | Florería Sashian';
	}

	if ( function_exists( 'is_shop' ) && is_shop() && sashian_title_is_plain( $title, 'Tienda' ) ) {
		return 'Flores a domicilio en Oaxaca | Florería Sashian';
	}

	if ( function_exists( 'is_product' ) && is_product() && sashian_title_is_plain( $title, get_the_title() ) ) {
		return get_the_title() . ' | Flores a domicilio en Oaxaca';
	}

	if ( is_singular( 'post' ) && sashian_title_is_plain( $title, get_the_title() ) ) {
		return get_the_title() . ' | Florería Sashian';
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && sashian_title_is_plain( $title, $term->name ) ) {
			$seo = sashian_category_seo( $term->slug );
			if ( $seo ) {
				return $seo['title'];
			}
		}
	}

	return $title;
}
add_filter( 'rank_math/frontend/title', 'sashian_document_title' );

/**
 * @param string $description Meta description.
 * @return string
 */
function sashian_document_description( $description ) {
	if ( 'delivery' === get_query_var( 'sashian_page' ) ) {
		return 'Envío de ramos y arreglos a domicilio en Oaxaca de Juárez y la zona metropolitana. Lunes a sábado de 9:00 a 19:00, domingos de 10:00 a 15:00.';
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$seo = sashian_category_seo( $term->slug );
			if ( $seo && '' === trim( wp_strip_all_tags( (string) $description ) ) ) {
				return $seo['lead'];
			}
		}
	}

	return $description;
}
add_filter( 'rank_math/frontend/description', 'sashian_document_description' );

/**
 * @param array<string,string> $robots
 * @return array<string,string>
 */
function sashian_document_robots( $robots ) {
	if ( 'delivery' !== get_query_var( 'sashian_page' ) ) {
		return $robots;
	}

	$robots['index']  = 'index';
	$robots['follow'] = 'follow';
	unset( $robots['noindex'], $robots['nofollow'] );

	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'sashian_document_robots' );

/**
 * @param string $canonical
 * @return string
 */
function sashian_document_canonical( $canonical ) {
	if ( 'delivery' === get_query_var( 'sashian_page' ) ) {
		return home_url( '/flores-a-domicilio-oaxaca/' );
	}
	return $canonical;
}
add_filter( 'rank_math/frontend/canonical', 'sashian_document_canonical' );

/**
 * Rank Math descarta product_cat antes del filtro de exclusión si la opción está apagada.
 *
 * @param mixed $value
 * @return mixed
 */
function sashian_enable_product_cat_sitemap_setting( $value ) {
	return $value ? $value : 'on';
}
add_filter( 'rank_math/settings/sitemap.tax_product_cat_sitemap', 'sashian_enable_product_cat_sitemap_setting' );

/**
 * La comprobación de Rank Math lee la opción antes de los filtros del sitemap.
 *
 * @param mixed $value
 * @return mixed
 */
function sashian_sitemap_option_include_product_cat( $value ) {
	if ( ! is_array( $value ) ) {
		$value = array();
	}
	$value['tax_product_cat_sitemap'] = 'on';
	return $value;
}
add_filter( 'option_rank-math-options-sitemap', 'sashian_sitemap_option_include_product_cat' );

/**
 * El índice del sitemap queda en caché de Rank Math y no ve la opción nueva.
 */
add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );

/**
 * Rank Math no registra product_cat si la opción del sitemap está apagada.
 * Estas dos funciones agregan el índice y las URLs con productos.
 *
 * @param string $index XML extra del índice.
 * @return string
 */
function sashian_product_cat_sitemap_index( $index ) {
	if ( false !== strpos( (string) $index, 'product_cat-sitemap.xml' ) ) {
		return $index;
	}

	$index .= "\t<sitemap>\n";
	$index .= "\t\t<loc>" . esc_url( home_url( '/product_cat-sitemap.xml' ) ) . "</loc>\n";
	$index .= "\t</sitemap>\n";
	return $index;
}
add_filter( 'rank_math/sitemap/index', 'sashian_product_cat_sitemap_index' );

/**
 * @param string $content XML del sitemap de categorías.
 * @return string
 */
function sashian_product_cat_sitemap_content( $content ) {
	if ( '' !== $content ) {
		return $content;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $content;
	}

	$xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, array( 'uncategorized', 'sin-categoria' ), true ) ) {
			continue;
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$xml .= "\t<url>\n\t\t<loc>" . esc_url( $link ) . "</loc>\n\t</url>\n";
	}
	$xml .= '</urlset>';

	return $xml;
}
add_filter( 'rank_math/sitemap/product_cat/content', 'sashian_product_cat_sitemap_content' );

/**
 * Las categorías de producto existen, pero Rank Math no las manda al sitemap.
 * Las vacías siguen fuera.
 *
 * @param bool   $exclude
 * @param string $type
 * @return bool
 */
function sashian_include_product_categories_in_sitemap( $exclude, $type ) {
	if ( 'product_cat' === $type ) {
		return false;
	}
	return $exclude;
}
add_filter( 'rank_math/sitemap/exclude_taxonomy', 'sashian_include_product_categories_in_sitemap', 20, 2 );

/**
 * @param bool     $exclude
 * @param string[] $taxonomy_names
 * @return bool
 */
function sashian_sitemap_skip_empty_categories( $exclude, $taxonomy_names ) {
	if ( is_array( $taxonomy_names ) && in_array( 'product_cat', $taxonomy_names, true ) ) {
		return true;
	}
	return $exclude;
}
add_filter( 'rank_math/sitemap/exclude_empty_terms', 'sashian_sitemap_skip_empty_categories', 20, 2 );

/**
 * Una línea visible bajo las categorías. El H1 del banner queda oculto en el catálogo.
 */
function sashian_category_lead() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || (int) $term->count < 1 ) {
		return;
	}

	$seo = sashian_category_seo( $term->slug );
	if ( ! $seo ) {
		return;
	}

	echo '<p class="sashian-cat-lead">' . esc_html( $seo['lead'] ) . '</p>';
}
add_action( 'woocommerce_before_shop_loop', 'sashian_category_lead', 6 );

/**
 * Favoritos de la portada. Precios y fotos salen de WooCommerce.
 *
 * @return WC_Product[]
 */
function sashian_home_favorite_products() {
	$slugs    = array(
		'arreglo-de-gerberas-color-y-vida',
		'ramo-de-lirios-amarillos',
		'ramo-de-girasoles-y-alstroemerias-blancas',
	);
	$products = array();

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $products;
	}

	foreach ( $slugs as $slug ) {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
		if ( ! $posts ) {
			continue;
		}
		$product = wc_get_product( $posts[0] );
		if ( $product instanceof WC_Product && $product->is_visible() ) {
			$products[] = $product;
		}
	}

	return $products;
}

/**
 * Respaldo si la portada vuelve al contenido de la página 189.
 * front-page.php ya imprime su propio H1 y no llama a the_content.
 *
 * @param string $content
 * @return string
 */
function sashian_front_page_heading( $content ) {
	if ( ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( locate_template( 'front-page.php', false, false ) ) {
		return $content;
	}
	if ( false !== stripos( $content, '<h1' ) ) {
		return $content;
	}

	$html  = '<h1 class="sashian-home-title">Florería en Oaxaca de Juárez</h1>';
	$html .= '<p class="sashian-home-lead">Ramos y flores a domicilio en Oaxaca y la zona metropolitana. ';
	$html .= '<a href="' . esc_url( home_url( '/flores-a-domicilio-oaxaca/' ) ) . '">Ver envíos</a>.</p>';

	return $html . $content;
}
add_filter( 'the_content', 'sashian_front_page_heading', 8 );

/**
 * Quiénes somos trae un segundo H1 dentro del contenido. El banner ya es el H1.
 *
 * @param string $content
 * @return string
 */
function sashian_demote_section_h1( $content ) {
	if ( ! is_singular( 'page' ) || is_front_page() ) {
		return $content;
	}

	$replaced = preg_replace_callback(
		'/<h1(\s[^>]*class="[^"]*\bmkdf-st-title\b[^"]*"[^>]*)>(.*?)<\/h1>/is',
		static function ( $matches ) {
			return '<h2' . $matches[1] . '>' . $matches[2] . '</h2>';
		},
		$content
	);

	return is_string( $replaced ) ? $replaced : $content;
}
add_filter( 'the_content', 'sashian_demote_section_h1', 12 );

/**
 * Página de domicilio. Solo dice lo que ya está en Contacto: zona metropolitana y horario.
 */
function sashian_register_delivery_page() {
	add_rewrite_rule( '^flores-a-domicilio-oaxaca/?$', 'index.php?sashian_page=delivery', 'top' );
}
add_action( 'init', 'sashian_register_delivery_page' );

/**
 * @param string[] $vars
 * @return string[]
 */
function sashian_delivery_query_var( $vars ) {
	$vars[] = 'sashian_page';
	return $vars;
}
add_filter( 'query_vars', 'sashian_delivery_query_var' );

function sashian_flush_delivery_rewrite() {
	if ( '1' === get_option( 'sashian_delivery_rewrite' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'sashian_delivery_rewrite', '1', false );
}
add_action( 'init', 'sashian_flush_delivery_rewrite', 99 );

/**
 * @param bool     $preempt
 * @param WP_Query $wp_query
 * @return bool
 */
function sashian_delivery_not_404( $preempt, $wp_query ) {
	if ( 'delivery' !== get_query_var( 'sashian_page' ) ) {
		return $preempt;
	}

	$wp_query->is_404 = false;
	status_header( 200 );
	return true;
}
add_filter( 'pre_handle_404', 'sashian_delivery_not_404', 10, 2 );

/**
 * @param string $template
 * @return string
 */
function sashian_delivery_template( $template ) {
	if ( 'delivery' !== get_query_var( 'sashian_page' ) ) {
		return $template;
	}

	$custom = sashian_theme_file_path( 'delivery.php' );
	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'sashian_delivery_template' );
