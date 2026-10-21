<?php
/**
 * Flores a domicilio. El texto repite Contacto: no promete mismo día ni lista colonias.
 *
 * @package Sashian
 */

defined( 'ABSPATH' ) || exit;

get_header();

$message = 'Hola, quiero enviar flores a domicilio en Oaxaca. ¿Me pueden orientar?';
$whatsapp = 'https://wa.me/529516480419?text=' . rawurlencode( $message );
?>
<main class="sashian-delivery">
	<h1>Flores a domicilio en Oaxaca</h1>
	<p>En Florería Sashian llevamos ramos y arreglos a domicilio en Oaxaca de Juárez y la zona metropolitana.</p>

	<h2>Horario</h2>
	<p>Lunes a sábado, de 9:00 a 19:00. Domingos, de 10:00 a 15:00.</p>

	<h2>Cómo pedir</h2>
	<p>Escríbenos por WhatsApp y te confirmamos disponibilidad y envío antes de preparar el ramo.</p>
	<p><a href="<?php echo esc_url( $whatsapp ); ?>">Pedir por WhatsApp</a></p>
	<p><a href="<?php echo esc_url( home_url( '/tienda/' ) ); ?>">Ver la tienda</a></p>
</main>
<?php
get_footer();
