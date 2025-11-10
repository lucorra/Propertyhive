<?php
/**
 * Property Hive Template Override: bespoke property card layout.
 *
 * Copy this file to your theme at:
 * /wp-content/themes/your-theme/propertyhive/content-property.php
 *
 * @package PropertyHive\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $property, $propertyhive_loop;

if ( empty( $propertyhive_loop['loop'] ) ) {
    $propertyhive_loop['loop'] = 0;
}

if ( empty( $propertyhive_loop['columns'] ) ) {
    $propertyhive_loop['columns'] = apply_filters( 'loop_search_results_columns', 1 );
}

if ( ! $property ) {
    return;
}

++$propertyhive_loop['loop'];

$classes = array( 'ph-card-item', 'ph-search-card-item' );

if ( ( $propertyhive_loop['loop'] - 1 ) % $propertyhive_loop['columns'] === 0 || $propertyhive_loop['columns'] === 1 ) {
    $classes[] = 'first';
}

if ( $propertyhive_loop['loop'] % $propertyhive_loop['columns'] === 0 ) {
    $classes[] = 'last';
}

if ( isset( $property->featured ) && 'yes' === $property->featured ) {
    $classes[] = 'featured';
}

if ( ! function_exists( 'propertyhive_search_card_ensure_card_styles' ) ) {
    /**
     * Ensure the property card stylesheet or inline fallback is loaded once per request.
     *
     * @return void
     */
    function propertyhive_search_card_ensure_card_styles() {
        static $styles_enqueued = false;

        if ( $styles_enqueued ) {
            return;
        }

        $styles_enqueued = true;

        $stylesheets = array(
            'propertyhive-search-card' => array(
                'path'     => __DIR__ . '/property-content.css',
                'filter'   => 'propertyhive_search_card_stylesheet',
                'fallback' => 'propertyhive_search_card_default_css',
            ),
            'propertyhive-energy-label' => array(
                'path'     => __DIR__ . '/energy-label.css',
                'filter'   => 'propertyhive_energy_label_stylesheet',
                'fallback' => 'propertyhive_search_card_default_energy_css',
            ),
        );

        foreach ( $stylesheets as $handle => $data ) {
            propertyhive_search_card_enqueue_stylesheet_handle( $handle, $data['path'], $data['filter'], $data['fallback'] );
        }
    }
}

if ( ! function_exists( 'propertyhive_search_card_enqueue_stylesheet_handle' ) ) {
    /**
     * Attempt to enqueue a stylesheet or fall back to inline styles.
     *
     * @param string $handle            Stylesheet handle.
     * @param string $stylesheet_path   Absolute path to stylesheet file.
     * @param string $filter_hook       Optional filter hook for overriding the URI.
     * @param callable|string $fallback Callback used to provide default CSS when the file cannot be read.
     *
     * @return void
     */
    function propertyhive_search_card_enqueue_stylesheet_handle( $handle, $stylesheet_path, $filter_hook, $fallback ) {
        static $processed = array();

        if ( isset( $processed[ $handle ] ) ) {
            return;
        }

        $processed[ $handle ] = true;

        $stylesheet_path         = wp_normalize_path( $stylesheet_path );
        $stylesheet_uri          = propertyhive_search_card_resolve_stylesheet_uri( $stylesheet_path );
        $style_enqueue_available = function_exists( 'wp_enqueue_style' ) && function_exists( 'wp_style_is' );

        if ( $filter_hook ) {
            $stylesheet_uri = apply_filters( $filter_hook, $stylesheet_uri, $stylesheet_path );
        }

        if ( $stylesheet_uri && $style_enqueue_available && ! wp_style_is( $handle, 'enqueued' ) ) {
            $version = file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : null;
            wp_enqueue_style( $handle, $stylesheet_uri, array(), $version );

            return;
        }

        $inline_css = propertyhive_search_card_load_inline_css( $stylesheet_path, $fallback );

        if ( empty( $inline_css ) ) {
            return;
        }

        $inline_style_available = function_exists( 'wp_add_inline_style' )
            && function_exists( 'wp_enqueue_style' )
            && function_exists( 'wp_style_is' )
            && function_exists( 'wp_register_style' );

        if ( $inline_style_available ) {
            if ( ! wp_style_is( $handle, 'registered' ) ) {
                wp_register_style( $handle, false, array(), null );
            }

            if ( ! wp_style_is( $handle, 'enqueued' ) ) {
                wp_enqueue_style( $handle );
            }

            wp_add_inline_style( $handle, $inline_css );

            return;
        }

        echo '<style id="' . esc_attr( $handle ) . '">' . $inline_css . '</style>';
    }
}

if ( ! function_exists( 'propertyhive_search_card_resolve_stylesheet_uri' ) ) {
    /**
     * Convert an absolute stylesheet path to a content URL when possible.
     *
     * @param string $stylesheet_path Absolute path to the CSS file.
     * @return string
     */
    function propertyhive_search_card_resolve_stylesheet_uri( $stylesheet_path ) {
        if ( empty( $stylesheet_path ) || ! file_exists( $stylesheet_path ) ) {
            return '';
        }

        $content_dir = wp_normalize_path( WP_CONTENT_DIR );
        $normalized  = wp_normalize_path( $stylesheet_path );

        if ( 0 === strpos( $normalized, $content_dir ) ) {
            $relative = ltrim( substr( $normalized, strlen( $content_dir ) ), '/' );

            return trailingslashit( content_url() ) . $relative;
        }

        return '';
    }
}

if ( ! function_exists( 'propertyhive_search_card_load_inline_css' ) ) {
    /**
     * Retrieve inline CSS fallback either from disk or the bundled default styles.
     *
     * @param string $stylesheet_path Absolute path to the CSS file.
     * @param callable|string $fallback Callback that returns default CSS when the file is unavailable.
     * @return string
     */
    function propertyhive_search_card_load_inline_css( $stylesheet_path, $fallback = 'propertyhive_search_card_default_css' ) {
        $css = '';

        if ( $stylesheet_path && is_readable( $stylesheet_path ) ) {
            $css = file_get_contents( $stylesheet_path );
        }

        if ( empty( $css ) && $fallback && is_callable( $fallback ) ) {
            $css = call_user_func( $fallback );
        }

        $css = is_string( $css ) ? trim( $css ) : '';

        return apply_filters( 'propertyhive_search_card_inline_css', $css, $stylesheet_path, $fallback );
    }
}

if ( ! function_exists( 'propertyhive_search_card_default_css' ) ) {
    /**
     * Provide a minimal inline CSS fallback that mirrors the dedicated stylesheet.
     *
     * @return string
     */
    function propertyhive_search_card_default_css() {
        $css = <<<'CSS'
/* Property card fallback styles */
:root {
  --ph-card-font-sans: "Sora", "Montserrat", "Helvetica Neue", Arial, sans-serif;
  --ph-card-font-serif: "Playfair Display", "Georgia", serif;
  --ph-card-ink: #2f2a23;
  --ph-card-muted: #8d8375;
  --ph-card-shell: #f2e7d7;
  --ph-card-pill: rgba(47, 42, 35, 0.08);
  --ph-card-shadow: 0 18px 38px rgba(47, 42, 35, 0.14);
}
.ph-card-item.ph-search-card-item,
.ph-search-card-item { list-style: none; margin: 0 0 4.5rem; padding: 0 2.5rem 4.5rem; box-sizing: border-box; font-family: var(--ph-card-font-sans); color: var(--ph-card-ink); border: none !important; border-bottom: none !important; box-shadow: none !important; background: none; }
.ph-search-card { margin: 0 auto; max-width: 1200px; padding: 0; }
.ph-search-card__inner { display: flex; flex-direction: column; gap: 2rem; }
.ph-search-card__media { position: relative; width: 100%; max-width: 100%; aspect-ratio: 437 / 256; margin: 0; background: var(--ph-card-shell); overflow: hidden; }
.ph-search-card__media-link { display: block; line-height: 0; }
.ph-search-card__image { width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 0; }
.ph-search-card__placeholder { display: flex; align-items: center; justify-content: center; height: 100%; min-height: 0; font-size: 1.1rem; color: var(--ph-card-muted); background: rgba(255,255,255,0.6); }
.ph-search-card__badge { position: absolute; top: 1.4rem; left: 1.4rem; padding: 0.45rem 1.15rem; border-radius: 0; background: rgba(255,255,255,0.96); color: var(--ph-card-ink); font-size: 0.95rem; font-weight: 600; letter-spacing: 0.08em; border: 1px solid rgba(47,42,35,0.08); box-shadow: var(--ph-card-shadow); }
.ph-search-card__details { display: flex; flex-direction: column; gap: 1.6rem; padding: 0; width: 100%; max-width: 100%; margin: 0; }
.ph-search-card__location { margin: 0; font-size: clamp(1rem, 2.6vw, 1.125rem); letter-spacing: 0.02em; text-transform: none; font-family: "Montserrat", var(--ph-card-font-sans); color: var(--ph-card-muted); }
.ph-search-card__title { margin: 0; font-family: var(--ph-card-font-serif); font-size: 30px !important; line-height: 1.18; color: var(--ph-card-ink); }
.ph-search-card__title a { color: inherit; text-decoration: none; }
.ph-search-card__title a:hover, .ph-search-card__title a:focus { text-decoration: underline; }
.ph-search-card__stats { margin: 0; padding: 0; padding-left: 0 !important; margin-left: 0 !important; margin-inline-start: 0 !important; padding-inline-start: 0 !important; list-style: none; display: flex; flex-wrap: wrap; justify-content: flex-start; align-items: center; gap: 0 3rem; }
.ph-search-card__stats > li { margin: 0; padding: 0; border: none !important; border-bottom: none !important; box-shadow: none !important; background: none !important; }
.ph-search-card__stat { display: inline-flex; align-items: center; gap: 0.75rem; font-weight: 600; font-size: clamp(1rem, 2.6vw, 1.125rem); font-family: "Montserrat", var(--ph-card-font-sans); color: var(--ph-card-ink); border: none !important; border-bottom: none !important; box-shadow: none !important; background: none; padding: 0; }
.ph-search-card__stat--price,
.ph-search-card__stat--price-poa,
.ph-search-card__stat--bedrooms,
.ph-search-card__stat--energy,
.ph-search-card__stat.energie_label { padding: 0 !important; }
.ph-search-card__stat--price { font-family: var(--ph-card-font-serif); font-size: clamp(1.3rem, 3.6vw, 1.5625rem); gap: 1.1rem; color: var(--ph-card-ink) !important; }
.ph-search-card__stat--price-poa .ph-search-card__price-value { font-size: clamp(1.2rem, 3vw, 1.5rem); font-weight: 500; color: var(--ph-card-muted); }
.ph-search-card__price-value { font-size: clamp(1.3rem, 3.6vw, 1.5625rem); line-height: 1; font-weight: 600; color: var(--ph-card-ink) !important; }
.ph-search-card__price-tag { font-family: var(--ph-card-font-sans); font-size: clamp(0.95rem, 2.2vw, 1.125rem); text-transform: uppercase; letter-spacing: 0.14em; padding: 0; border-radius: 0; background: none; color: var(--ph-card-muted); }
.ph-search-card__stat-icon { width: 28px; height: 28px; display: inline-block; background: currentColor; opacity: 0.78; mask-repeat: no-repeat; mask-position: center; mask-size: contain; }
.ph-search-card__stat-text { line-height: 1.15; }
.ph-search-card__stat--area .ph-search-card__stat-icon { mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3.5' y='3.5' width='17' height='17' rx='2'/%3E%3Cpath d='M3.5 9.5h3M3.5 14.5h3M17.5 20.5v-3M12.5 20.5v-3M20.5 14.5h-3M20.5 9.5h-3M6.5 3.5v3M11.5 3.5v3'/%3E%3C/svg%3E"); }
.ph-search-card__stat--bed .ph-search-card__stat-icon, .ph-search-card__stat--bedrooms .ph-search-card__stat-icon { mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M2 12h20a2 2 0 0 1 2 2v6H0v-6a2 2 0 0 1 2-2Z'/%3E%3Cpath d='M4 12V9a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3'/%3E%3Cpath d='M2 20v2'/%3E%3Cpath d='M22 20v2'/%3E%3C/svg%3E"); }
.ph-search-card__stat--energy .ph-search-card__stat-icon { mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M13 2 5 14h6l-1 8 8-12h-6l1-8Z'/%3E%3C/svg%3E"); }
.screen-reader-text { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
@media (max-width: 599.98px) { .ph-search-card__inner { gap: 1.5rem; } .ph-search-card__media, .ph-search-card__details { max-width: 100%; } .ph-search-card__media { aspect-ratio: 437 / 256; } .ph-search-card__placeholder { min-height: 0; } .ph-search-card__title { font-size: 22px !important; } .ph-search-card__stats { gap: 0.75rem 1.4rem; } .ph-search-card__stat { font-size: 1.05rem; } .ph-search-card__stat--price, .ph-search-card__price-value { font-size: 1.45rem; } }
CSS;

        return apply_filters( 'propertyhive_search_card_default_css', trim( $css ) );
    }
}

if ( ! function_exists( 'propertyhive_search_card_default_energy_css' ) ) {
    /**
     * Provide inline CSS for the energy label palette when the stylesheet cannot be loaded.
     *
     * @return string
     */
    function propertyhive_search_card_default_energy_css() {
        $css = <<<'CSS'
/* Energy label fallback styles */
.wrap_energie_label,
.energie_label {
  display: inline-flex;
  align-items: center;
  font-size: inherit;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  gap: 0.25rem;
}

.energie_label {
  --energie-badge-bg: #00923f;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
  position: relative;
  list-style: none;
  margin: 0;
  padding: 0;
  border-radius: 0;
  background: var(--energie-badge-bg);
  color: var(--energie-value-color);
  line-height: 1.1;
  font-family: "Montserrat", "Sora", "Helvetica Neue", Arial, sans-serif;
}

.energie_label::after {
  display: none;
}

.energie_label .energie_label_label {
  color: var(--energie-label-color);
  white-space: nowrap;
}

.energie_label .energie_label_value {
  color: var(--energie-value-color);
}

.wrap_energie_label .rem-single-field-value[data-value="A+++"] { background: #00923f; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="A+++"]::after { border-left-color: #00923f; }

.energie_label[data-value="A+++"] {
  --energie-badge-bg: #00923f;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="A++"] { background: #00923f; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="A++"]::after { border-left-color: #00923f; }

.energie_label[data-value="A++"] {
  --energie-badge-bg: #00923f;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="A+"] { background: #00923f; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="A+"]::after { border-left-color: #00923f; }

.energie_label[data-value="A+"] {
  --energie-badge-bg: #00923f;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="A"] { background: #00923f; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="A"]::after { border-left-color: #00923f; }

.energie_label[data-value="A"] {
  --energie-badge-bg: #00923f;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="B"] { background: #36b34a; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="B"]::after { border-left-color: #36b34a; }

.energie_label[data-value="B"] {
  --energie-badge-bg: #36b34a;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="C"] { background: #84c225; color: #2f2a23; }
.wrap_energie_label .rem-single-field-value[data-value="C"]::after { border-left-color: #84c225; }

.energie_label[data-value="C"] {
  --energie-badge-bg: #84c225;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="D"] { background: #fbe122; color: #000; }
.wrap_energie_label .rem-single-field-value[data-value="D"]::after { border-left-color: #fbe122; }

.energie_label[data-value="D"] {
  --energie-badge-bg: #fbe122;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="E"] { background: #f6a500; color: #2f2a23; }
.wrap_energie_label .rem-single-field-value[data-value="E"]::after { border-left-color: #f6a500; }

.energie_label[data-value="E"] {
  --energie-badge-bg: #f6a500;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="F"] { background: #eb5c2b; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="F"]::after { border-left-color: #eb5c2b; }

.energie_label[data-value="F"] {
  --energie-badge-bg: #eb5c2b;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}

.wrap_energie_label .rem-single-field-value[data-value="G"] { background: #e51c20; color: #fff; }
.wrap_energie_label .rem-single-field-value[data-value="G"]::after { border-left-color: #e51c20; }

.energie_label[data-value="G"] {
  --energie-badge-bg: #e51c20;
  --energie-value-color: #2f2a23;
  --energie-label-color: #2f2a23;
}
CSS;

        return apply_filters( 'propertyhive_search_card_default_energy_css', trim( $css ) );
    }
}

propertyhive_search_card_ensure_card_styles();

if ( ! function_exists( 'propertyhive_search_card_format_price' ) ) {
    /**
     * Return a formatted price string with the appropriate currency symbol.
     *
     * @param int|string $value    Raw price value.
     * @param string     $currency Optional ISO currency code.
     * @return string
     */
    function propertyhive_search_card_format_price( $value, $currency = 'GBP' ) {
        if ( ! is_numeric( $value ) ) {
            return $value;
        }

        $symbol = '£';

        switch ( strtoupper( $currency ) ) {
            case 'USD':
                $symbol = '$';
                break;
            case 'EUR':
                $symbol = '€';
                break;
            case 'GBP':
            default:
                $symbol = '£';
        }

        return sprintf( '%1$s%2$s', $symbol, number_format_i18n( $value ) );
    }
}

if ( ! function_exists( 'propertyhive_search_card_humanize_label' ) ) {
    /**
     * Convert stored meta strings (snake case, lowercase) into a readable label.
     *
     * @param string $value Raw label value from meta.
     * @return string
     */
    function propertyhive_search_card_humanize_label( $value ) {
        if ( empty( $value ) || ! is_string( $value ) ) {
            return '';
        }

        $value = str_replace( array( '-', '_' ), ' ', strtolower( $value ) );

        return ucwords( $value );
    }
}

if ( ! function_exists( 'propertyhive_search_card_sentence_case' ) ) {
    /**
     * Convert a string to sentence case (first letter uppercase, rest lowercase).
     *
     * @param string $value Input string.
     * @return string
     */
    function propertyhive_search_card_sentence_case( $value ) {
        if ( empty( $value ) || ! is_string( $value ) ) {
            return '';
        }

        $encoding = function_exists( 'mb_internal_encoding' ) ? mb_internal_encoding() : 'UTF-8';
        $lower    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, $encoding ) : strtolower( $value );

        if ( '' === $lower ) {
            return '';
        }

        $first = function_exists( 'mb_substr' ) ? mb_substr( $lower, 0, 1, $encoding ) : substr( $lower, 0, 1 );
        $rest  = function_exists( 'mb_substr' ) ? mb_substr( $lower, 1, null, $encoding ) : substr( $lower, 1 );

        $first = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first, $encoding ) : strtoupper( $first );

        return $first . $rest;
    }
}

if ( ! function_exists( 'propertyhive_search_card_prepare_value' ) ) {
    /**
     * Normalise mixed values into trimmed strings.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    function propertyhive_search_card_prepare_value( $value ) {
        if ( is_array( $value ) ) {
            $value = implode( ', ', array_filter( array_map( 'trim', array_map( 'strval', $value ) ) ) );
        }

        if ( is_string( $value ) ) {
            $value = trim( $value );
        } elseif ( is_numeric( $value ) ) {
            $value = (string) $value;
        } else {
            $value = '';
        }

        return $value;
    }
}

if ( ! function_exists( 'propertyhive_search_card_get_property_value' ) ) {
    /**
     * Safely retrieve a property object's field value, triggering magic accessors when available.
     *
     * @param object $property Property object.
     * @param string $field    Field name.
     * @return mixed|null
     */
    function propertyhive_search_card_get_property_value( $property, $field ) {
        if ( ! is_object( $property ) || '' === $field ) {
            return null;
        }

        if ( isset( $property->{$field} ) ) {
            return $property->{$field};
        }

        if ( property_exists( $property, $field ) ) {
            return $property->{$field};
        }

        if ( method_exists( $property, '__get' ) ) {
            return $property->{$field};
        }

        return null;
    }
}

if ( ! function_exists( 'propertyhive_search_card_get_first_value' ) ) {
    /**
     * Fetch the first non-empty value from a list of property fields or meta keys.
     *
     * @param PH_Property|object $property   Property object.
     * @param int                $post_id    Property ID.
     * @param array              $fields     Object property names to check.
     * @param array              $meta_keys  Meta keys to check.
     * @return string
     */
    function propertyhive_search_card_get_first_value( $property, $post_id, $fields = array(), $meta_keys = array() ) {
        foreach ( (array) $fields as $field ) {
            $raw_value = propertyhive_search_card_get_property_value( $property, $field );

            if ( null !== $raw_value ) {
                $value = propertyhive_search_card_prepare_value( $raw_value );

                if ( '' !== $value ) {
                    return $value;
                }
            }
        }

        foreach ( (array) $meta_keys as $key ) {
            $value = propertyhive_search_card_prepare_value( get_post_meta( $post_id, $key, true ) );

            if ( '' !== $value ) {
                return $value;
            }
        }

        return '';
    }
}

if ( ! function_exists( 'propertyhive_search_card_parse_number' ) ) {
    /**
     * Attempt to convert a mixed value into a floating point number.
     *
     * @param mixed $value Raw value.
     * @return float|null
     */
    function propertyhive_search_card_parse_number( $value ) {
        $value = propertyhive_search_card_prepare_value( $value );

        if ( '' === $value ) {
            return null;
        }

        if ( is_numeric( $value ) ) {
            return (float) $value;
        }

        $clean = preg_replace( '/[^0-9\.,]/', '', $value );

        if ( '' === $clean ) {
            return null;
        }

        $last_comma = strrpos( $clean, ',' );
        $last_dot   = strrpos( $clean, '.' );
        $decimal    = null;

        if ( false !== $last_comma && false !== $last_dot ) {
            $decimal = $last_comma > $last_dot ? ',' : '.';
        } elseif ( false !== $last_comma ) {
            $decimal = ',';
        } elseif ( false !== $last_dot ) {
            $decimal = '.';
        }

        if ( ',' === $decimal ) {
            $clean = str_replace( '.', '', $clean );
            $clean = str_replace( ',', '.', $clean );
        } else {
            $clean = str_replace( ',', '', $clean );
        }

        return is_numeric( $clean ) ? (float) $clean : null;
    }
}

if ( ! function_exists( 'propertyhive_search_card_format_area_value' ) ) {
    /**
     * Format a floor area value with sensible defaults.
     *
     * @param mixed $raw_value   Raw area value.
     * @param int   $property_id Property ID.
     * @return string
     */
    function propertyhive_search_card_format_area_value( $raw_value, $property_id ) {
        $prepared = propertyhive_search_card_prepare_value( $raw_value );

        if ( '' === $prepared ) {
            return '';
        }

        $numeric = propertyhive_search_card_parse_number( $prepared );

        if ( null !== $numeric && $numeric > 0 ) {
            $rounded = round( $numeric );
            $display = sprintf( '%s m²', number_format_i18n( $rounded ) );

            return apply_filters( 'propertyhive_search_card_formatted_area', $display, $prepared, $rounded, $property_id );
        }

        return apply_filters( 'propertyhive_search_card_formatted_area', $prepared, $prepared, null, $property_id );
    }
}

$property_id   = get_the_ID();
$raw_price     = get_post_meta( $property_id, '_price', true );
$price_value   = propertyhive_search_card_prepare_value( $raw_price );
$currency      = get_post_meta( $property_id, '_currency', true );
$price_numeric = propertyhive_search_card_parse_number( $price_value );
$has_price     = null !== $price_numeric;
$price_string  = $has_price ? propertyhive_search_card_format_price( $price_numeric, $currency ) : __( 'Price on application', 'propertyhive' );
$price_qual    = propertyhive_search_card_humanize_label( get_post_meta( $property_id, '_price_qualifier', true ) );
$bedrooms      = propertyhive_search_card_prepare_value( get_post_meta( $property_id, '_bedrooms', true ) );
$availability = get_post_meta( $property_id, '_availability', true );
$status_terms = wp_get_post_terms( $property_id, 'availability' );
$department   = '';
$energy_label_value = propertyhive_search_card_prepare_value(
    propertyhive_search_card_get_property_value( $property, 'energie_label' )
);

if ( '' === $energy_label_value ) {
    $energy_terms = wp_get_post_terms( $property_id, 'energy_label' );

    if ( ! empty( $energy_terms ) && ! is_wp_error( $energy_terms ) ) {
        $energy_label_value = propertyhive_search_card_prepare_value( $energy_terms[0]->name );
    }
}

if ( '' !== $energy_label_value ) {
    $energy_label_value = strtoupper( $energy_label_value );
}

if ( isset( $property->department ) && $property->department ) {
    $department = (string) $property->department;
}

if ( ! $department ) {
    $department = get_post_meta( $property_id, '_department', true );
}

if ( ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ) {
    $availability = $status_terms[0]->name;
}

$availability = propertyhive_search_card_sentence_case( $availability );

$price_suffix = $price_qual;
$is_lettings  = false !== stripos( (string) $department, 'letting' ) || false !== stripos( (string) $department, 'rent' );

if ( ! $price_suffix ) {
    $price_suffix = $is_lettings ? __( 'p/m', 'propertyhive' ) : '';
}

$price_suffix = apply_filters( 'propertyhive_search_card_price_suffix', $price_suffix, $property_id, $department );

if ( ! $has_price ) {
    $price_suffix = '';
}

$location_postcode = propertyhive_search_card_get_first_value(
    $property,
    $property_id,
    array( 'postcode', 'post_code' ),
    array( '_postcode', '_post_code', '_address_postcode', '_address_post_code' )
);

$location_city = propertyhive_search_card_get_first_value(
    $property,
    $property_id,
    array( 'town', 'city', 'address_town', 'address_city' ),
    array( '_town', '_city', '_address_town', '_address_city' )
);

$location_parts = array_filter(
    array(
        $location_postcode,
        $location_city,
    ),
    'strlen'
);

$location_line = implode( ' ', $location_parts );

$address_line = propertyhive_search_card_get_first_value(
    $property,
    $property_id,
    array( 'display_address', 'address_display', 'address_1', 'address_line_1', 'address' ),
    array( '_address_display', '_display_address', '_address_1', '_address_line_1', '_address' )
);

if ( ! $address_line ) {
    $address_line = get_the_title();
}

$raw_area = propertyhive_search_card_get_first_value(
    $property,
    $property_id,
    array( 'floor_area', 'floor_area_from', 'floor_area_to', 'floor_area_total', 'internal_area' ),
    array( '_floor_area', '_floor_area_from', '_floor_area_to', '_floor_area_total', '_internal_area', '_internal_floor_area' )
);

$area_display = propertyhive_search_card_format_area_value( $raw_area, $property_id );

$bedrooms_display = '';

if ( '' !== $bedrooms ) {
    $numeric_bedrooms = propertyhive_search_card_parse_number( $bedrooms );

    if ( null !== $numeric_bedrooms && $numeric_bedrooms > 0 ) {
        $bedrooms_display = number_format_i18n( (int) round( $numeric_bedrooms ) );
    } else {
        $bedrooms_display = $bedrooms;
    }
}

$price_stat_classes = array( 'ph-search-card__stat', 'ph-search-card__stat--price' );

if ( ! $has_price ) {
    $price_stat_classes[] = 'ph-search-card__stat--price-poa';
}

$price_stat_class_attr = trim( implode( ' ', array_unique( $price_stat_classes ) ) );
?>

<li <?php post_class( $classes ); ?>>

    <?php do_action( 'propertyhive_before_search_results_loop_item' ); ?>

    <article class="ph-search-card">
        <div class="ph-search-card__inner">
            <div class="ph-search-card__media">
                <a class="ph-search-card__media-link" href="<?php the_permalink(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'propertyhive-large', array( 'class' => 'ph-search-card__image', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <span class="ph-search-card__placeholder" aria-hidden="true">
                            <?php esc_html_e( 'Image coming soon', 'propertyhive' ); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ( ! empty( $availability ) ) : ?>
                    <span class="ph-search-card__badge"><?php echo esc_html( $availability ); ?></span>
                <?php endif; ?>
            </div>

            <div class="ph-search-card__details">
                <?php if ( ! empty( $location_line ) ) : ?>
                    <p class="ph-search-card__location"><?php echo esc_html( $location_line ); ?></p>
                <?php endif; ?>

                <h3 class="ph-search-card__title">
                    <a href="<?php the_permalink(); ?>"><?php echo esc_html( $address_line ); ?></a>
                </h3>

                <ul class="ph-search-card__stats">
                    <li class="<?php echo esc_attr( $price_stat_class_attr ); ?>">
                        <span class="ph-search-card__price-value"><?php echo esc_html( $price_string ); ?></span>
                        <?php if ( ! empty( $price_suffix ) ) : ?>
                            <span class="ph-search-card__price-tag"><?php echo esc_html( $price_suffix ); ?></span>
                        <?php endif; ?>
                    </li>

                    <?php if ( ! empty( $area_display ) ) : ?>
                        <li class="ph-search-card__stat ph-search-card__stat--area">
                            <span class="ph-search-card__stat-icon" aria-hidden="true"></span>
                            <span class="ph-search-card__stat-text"><?php echo esc_html( $area_display ); ?></span>
                        </li>
                    <?php endif; ?>

                    <?php if ( ! empty( $bedrooms_display ) ) : ?>
                        <li class="ph-search-card__stat ph-search-card__stat--bedrooms">
                            <span class="ph-search-card__stat-icon" aria-hidden="true"></span>
                            <span class="ph-search-card__stat-text"><?php echo esc_html( $bedrooms_display ); ?></span>
                        </li>
                    <?php endif; ?>

                    <?php if ( ! empty( $energy_label_value ) ) : ?>
                        <li class="ph-search-card__stat ph-search-card__stat--energy energie_label" data-value="<?php echo esc_attr( $energy_label_value ); ?>">
                            <span class="ph-search-card__stat-icon" aria-hidden="true"></span>
                            <span class="energie_label_label screen-reader-text"><?php esc_html_e( 'Energy label', 'propertyhive' ); ?>:</span>
                            <span class="energie_label_value"><?php echo esc_html( $energy_label_value ); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </article>

    <?php do_action( 'propertyhive_after_search_results_loop_item' ); ?>

</li>
