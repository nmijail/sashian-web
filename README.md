# Sashian — child theme

Child theme de **Fiorello** para [floreriasashian.com](https://floreriasashian.com). El tema padre no se edita.

Este repositorio es solo el tema. WordPress, WooCommerce, plugins y la base de datos siguen en Hostinger.

## Qué cambia

En la tienda, las categorías y la ficha, en móvil:

- Oculta la franja del título «Tienda» y conserva el `h1` para SEO.
- Muestra una fila con las categorías que tienen productos.
- Pone los productos en una columna, foto 4:5, precio visible y botón cuadrado.
- Sustituye el botón flotante de WhatsApp por un enlace en el encabezado, con el mismo número (`529516480419`).
- Quita del menú las categorías de producto vacías.

En las fichas, el precio principal usa 30 px en escritorio y 28 px en móvil, peso medio y color oscuro. No cambia los importes, las tarjetas del catálogo ni el checkout.

El escritorio sigue con la grilla de Fiorello. Las fotos del catálogo no se reemplazan.

## Activar, cuando se decida

1. Subir `main` a `github.com/nmijail/sashian-web`.
2. En Hostinger, el directorio de despliegue tiene que ser exactamente:

   `wp-content/themes/sashian`

   Un directorio vacío despliega sobre `public_html` y pisa WordPress. No activar el autodeploy hasta confirmar esa ruta y un backup.
3. El despliegue copia archivos. No activa el tema. En Apariencia → Temas, activar **Sashian**.
4. Purgar LiteSpeed Cache.
5. Para volver atrás: reactivar **Fiorello**. Los archivos del child pueden quedarse.

## No incluye

Precios, stock, envíos, Stripe, slugs ni imágenes generadas por Stitch.
