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
	return $classes;
}
add_filter( 'body_class', 'sashian_body_class' );

/**
 * Estilos del child, después de los del padre.
 */
function sashian_enqueue_styles() {
	wp_enqueue_style(
		'sashian-catalog',
		get_stylesheet_directory_uri() . '/assets/catalog.css',
		array( 'fiorello-mikado-default-style' ),
		wp_get_theme()->get( 'Version' )
	);
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
