<?php
/**
 * Portada. La foto es DSC01937; los ramos y precios salen de WooCommerce.
 * La página 189 se conserva: borrar este archivo vuelve al slider.
 *
 * @package Sashian
 */

defined( 'ABSPATH' ) || exit;

get_header();

$whatsapp_message = 'Hola, me interesa hacer un pedido en Florería Sashian. ¿Me pueden ayudar?';
$whatsapp         = 'https://wa.me/529516480419?text=' . rawurlencode( $whatsapp_message );
$custom_message   = 'Hola, quiero armar un ramo a mi manera. ¿Me pueden orientar?';
$custom_whatsapp  = 'https://wa.me/529516480419?text=' . rawurlencode( $custom_message );
$hero             = get_stylesheet_directory_uri() . '/assets/hero-rosas.jpg';
$occasions        = array(
	'amor'       => 'Amor',
	'cumpleanos' => 'Cumpleaños',
	'ramos'      => 'Ramos',
	'premium'    => 'Premium',
);
$favorites        = sashian_home_favorite_products();
?>
<main class="sashian-front">
	<section class="sashian-hero">
		<img
			class="sashian-hero__photo"
			src="<?php echo esc_url( $hero ); ?>"
			width="1066"
			height="1600"
			alt="Ramo de rosas durazno y lisianthus, Florería Sashian"
			fetchpriority="high"
			decoding="async"
		>
		<div class="sashian-hero__copy">
			<h1>Regala flores que acarician el <span>alma</span></h1>
			<p>Ramos y flores a domicilio en Oaxaca de Juárez.</p>
			<a class="sashian-home-button" href="<?php echo esc_url( home_url( '/tienda/' ) ); ?>">Ver la tienda</a>
			<a class="sashian-home-textlink" href="<?php echo esc_url( $whatsapp ); ?>">Pedir por WhatsApp</a>
		</div>
	</section>

	<section class="sashian-home-section">
		<h2>Para cada ocasión</h2>
		<nav class="sashian-home-occasions" aria-label="Ocasiones">
			<?php foreach ( $occasions as $slug => $label ) : ?>
				<?php
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				$url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/categoria-producto/' . $slug . '/' );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
	</section>

	<?php if ( $favorites ) : ?>
		<section class="sashian-home-section">
			<h2>Favoritos de Sashian</h2>
			<ul class="sashian-home-favs">
				<?php foreach ( $favorites as $favorite ) : ?>
					<?php
					$GLOBALS['product'] = $favorite;
					$link               = $favorite->get_permalink();
					$image_id           = $favorite->get_image_id();
					?>
					<li>
						<a class="sashian-home-card__media" href="<?php echo esc_url( $link ); ?>">
							<?php
							if ( $image_id ) {
								echo wp_get_attachment_image(
									$image_id,
									'large',
									false,
									array(
										'class' => 'sashian-home-card__photo',
										'alt'   => $favorite->get_name(),
									)
								);
							}
							?>
						</a>
						<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $favorite->get_name() ); ?></a></h3>
						<p class="sashian-home-card__price"><?php echo wp_kses_post( $favorite->get_price_html() ); ?></p>
						<?php woocommerce_template_loop_add_to_cart(); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php wp_reset_postdata(); ?>
	<?php endif; ?>

	<section class="sashian-home-band">
		<p class="sashian-home-kicker">Taller artesanal · Oaxaca</p>
		<h2>Nuestra esencia</h2>
		<p>Flores para acompañar los momentos que importan.</p>
	</section>

	<section class="sashian-home-section sashian-home-custom">
		<h2>Tu ramo, a tu manera</h2>
		<p>Cuéntanos la ocasión y lo armamos contigo.</p>
		<a class="sashian-home-button" href="<?php echo esc_url( $custom_whatsapp ); ?>">Personalizar</a>
	</section>

	<section class="sashian-home-section">
		<h2>Comprar es sencillo</h2>
		<ol class="sashian-home-steps">
			<li>Elige un ramo.</li>
			<li>Agrégalo al carrito.</li>
			<li>Confirma tu pedido.</li>
		</ol>
	</section>

	<section class="sashian-home-section sashian-home-place">
		<h2>Hecho en Oaxaca</h2>
		<p>Oaxaca de Juárez, México.</p>
		<p><a href="<?php echo esc_url( home_url( '/flores-a-domicilio-oaxaca/' ) ); ?>">Ver envíos</a></p>
	</section>
</main>
<?php
get_footer();
