<?php
/**
 * Plugin Name: Instaread Audio Player
 * Plugin URI: https://www.instaread.co
 * Description: Advanced audio player injection with dual placement options and CLS prevention. Supports local and production script URLs for testing.
 * Version: 1.0.2
 * Author: Instaread
 * Author URI: https://www.instaread.co
 */

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://suvarna-sumanth.github.io/Halfbakedharvest-UpdateChecker/plugin.json',
    __FILE__,
    'instaread-audio-player'
);

// 1. Plugin Settings Registration
add_action('admin_init', function() {
    register_setting('instaread_settings', 'instaread_header_target', 'sanitize_text_field');
    register_setting('instaread_settings', 'instaread_header_position', 'sanitize_text_field');

    register_setting('instaread_settings', 'instaread_header_height', function($val) {
        return sanitize_text_field((string)(float) $val);
    });

    register_setting('instaread_settings', 'instaread_content_target', 'sanitize_text_field');

    register_setting('instaread_settings', 'instaread_content_height', function($val) {
        return sanitize_text_field((string)(int) $val);
    });

    register_setting('instaread_settings', 'instaread_publication', 'sanitize_text_field');
    register_setting('instaread_settings', 'instaread_exclude_slugs', 'sanitize_text_field');
});


// 2. Settings Page UI
add_action('admin_menu', function() {
    add_options_page(
        'Instaread Settings',
        'Instaread Player',
        'manage_options',
        'instaread-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>Instaread Audio Player Configuration</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('instaread_settings'); ?>
                    
                    <h2>Header Placement</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>Target Element</label></th>
                            <td>
                                <input type="text" name="instaread_header_target" 
                                    value="<?= esc_attr(get_option('instaread_header_target', '.page-header__content-actions')) ?>" 
                                    class="regular-text">
                                <p class="description">CSS selector for header container</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Insert Position</label></th>
                            <td>
                            <select name="instaread_header_position">
                        <option value="before" <?php selected(get_option('instaread_header_position'), 'before') ?>>Before element</option>
                        <option value="after" <?php selected(get_option('instaread_header_position'), 'after') ?>>After element</option>
                        <option value="inside_end" <?php selected(get_option('instaread_header_position'), 'inside_end') ?>>Inside (end)</option>
                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Player Height (px)</label></th>
                            <td>
                                <input type="number" name="instaread_header_height" 
                                    value="<?= esc_attr(get_option('instaread_header_height', 36.4)) ?>" 
                                    step="0.1" min="20">
                            </td>
                        </tr>
                    </table>

                    <h2>Content Placement</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>Fallback Target</label></th>
                            <td>
                                <input type="text" name="instaread_content_target" 
                                    value="<?= esc_attr(get_option('instaread_content_target', '.dpsp-pin-it-wrapper')) ?>" 
                                    class="regular-text">
                                <p class="description">CSS selector for content image</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Player Height (px)</label></th>
                            <td>
                                <input type="number" name="instaread_content_height" 
                                    value="<?= esc_attr(get_option('instaread_content_height', 85)) ?>" 
                                    min="50">
                            </td>
                        </tr>
                    </table>

                    <h2>General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th><label>Publication ID</label></th>
                            <td>
                                <input type="text" name="instaread_publication" 
                                    value="<?= esc_attr(get_option('instaread_publication', 'halfbakedharvest')) ?>" 
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Excluded Slugs</label></th>
                            <td>
                                <input type="text" name="instaread_exclude_slugs" 
                                    value="<?= esc_attr(get_option('instaread_exclude_slugs', 'about,home')) ?>" 
                                    class="regular-text">
                                <p class="description">Comma-separated list of slugs to exclude</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    );
});

// 3. Player Injection Logic
add_action('wp_footer', function() {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;


    if (!is_singular() || !is_main_query()) return;

    global $post;
    $excluded_slugs = array_map('trim', explode(',', get_option('instaread_exclude_slugs', '')));
    if (in_array($post->post_name, $excluded_slugs, true)) return;

    $config = [
        'publication' => esc_js(get_option('instaread_publication', 'halfbakedharvest')),
        'header' => [
            'target' => esc_js(get_option('instaread_header_target', '.page-header__content-actions')),
            'position' => get_option('instaread_header_position', 'inside_end'),
            'height' => (float)get_option('instaread_header_height', 36.4)
        ],
        'content' => [
            'target' => esc_js(get_option('instaread_content_target', '.dpsp-pin-it-wrapper')),
            'height' => (int)get_option('instaread_content_height', 85)
        ]
    ];

    ?>
    <style>
    .instaread-player {
        visibility: hidden;
    }
    .page-header__recipe-save-link .instaread-player {
        width: 121.066px;
        height: <?= $config['header']['height'] ?>px;
    }
 
    @media only screen and (min-width: 1200px) {
        .instaread-content-wrapper{
            width:100%;
            min-width: 327px;
            height: 85px;
        }
    } 
    @media only screen and (max-width: 1199px) {
        .instaread-content-wrapper {
            width:100%;
            min-width: 327px;
            height: 85px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const version = Math.floor(Date.now() / 3.6e6) * 3.6e6;
        const publication = "<?= $config['publication'] ?>";
        console.log("Hello");

 function injectHeaderPlayer() {
    const contentActions = document.querySelector('<?= $config['header']['target'] ?>');
    console.log("Header target selector:", '<?= $config['header']['target'] ?>');
    console.log("Header target element:", contentActions);

    if (!contentActions) {
        console.warn("Header target not found!");
        return false;
    }

    let hasContentRecipe = false;
    const saveLinks = contentActions.querySelectorAll(".page-header__recipe-save-link a");
    const skipLinks = contentActions.querySelectorAll(".page-header__recipe-skip-link a");

    const hasSaveRecipe = Array.from(saveLinks).some((a) =>
        a.textContent.trim().includes("Save Recipe")
    );
    const hasSkipRecipe = Array.from(skipLinks).some((a) =>
        a.textContent.trim().includes("Skip to Recipe")
    );
    hasContentRecipe = hasSaveRecipe && hasSkipRecipe;

    console.log("Has Save Recipe:", hasSaveRecipe, "Has Skip Recipe:", hasSkipRecipe, "Combined:", hasContentRecipe);

    if (!hasContentRecipe) {
        console.warn("Required recipe links not found in header.");
        return false;
    }

    const wrapper = document.createElement('p');
    wrapper.className = 'page-header__recipe-save-link';
    wrapper.style.height = "34.4px";
    wrapper.style.width = "121.066px";

    const player = document.createElement('instaread-player');
    player.setAttribute('publication', publication);
    player.className = 'instaread-player';
    const iframe = document.createElement("iframe");
    iframe.id = "instaread_iframe";
    iframe.name = "instaread_playlist";
    iframe.seamless = true;
    iframe.width = "121.06px";
    iframe.height = "36.4px";
    iframe.scrolling = "no";
    iframe.setAttribute("horizontalscrolling", "no");
    iframe.setAttribute("verticalscrolling", "no");
    iframe.frameBorder = "0";
    iframe.marginWidth = "0";
    iframe.marginHeight = "0";
    iframe.setAttribute("mozallowfullscreen", "");
    iframe.setAttribute("webkitallowfullscreen", "");
    iframe.setAttribute("allowfullscreen", "");
    iframe.loading = "lazy";
    iframe.title = "Audio Article";
    iframe.allow = "accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture";
    iframe.style.display = "block";
    player.appendChild(iframe);
    wrapper.appendChild(player);

    const headerPosition = "<?= esc_js(get_option('instaread_header_position', 'inside_end')) ?>";
    console.log("Header position from settings:", headerPosition);

    if (headerPosition === 'before') {
        contentActions.parentNode.insertBefore(wrapper, contentActions);
    } else if (headerPosition === 'after') {
        contentActions.parentNode.insertBefore(wrapper, contentActions.nextSibling);
    } else if (headerPosition === 'inside_end') {
        contentActions.appendChild(wrapper);
    } else {
        return false;
    }

    // Check DOM after insertion
    setTimeout(() => {
    }, 100);

    return true;
}


function injectContentPlayer() {
    try {
        const mainContent = document.querySelector('.site-main .entry-content');
        if (!mainContent) {
            return false;
        }

        // Get ALL paragraphs in content
        const paragraphs = Array.from(mainContent.querySelectorAll('p'));
        let targetParagraph = null;

        // Find first valid paragraph
        for (const p of paragraphs) {
            const text = p.textContent.trim();
            
            // Skip if too short/empty
            if (!text || text.length < 100) continue;
            
            // Skip if contains only HTML tags
            if (p.innerHTML.trim().match(/^<[^>]+>$/)) continue;
            
            // Valid paragraph found
            targetParagraph = p;
            break;
        }

        if (!targetParagraph) {
            return false;
        }

        // Check if player already exists
        if (document.querySelector('.instaread-content-wrapper')) {
            return false;
        }

        const playerContainer = createPlayerContainer();
        const text = targetParagraph.textContent.trim();

        // Insert AFTER if contains "Nine Favorite Things"
        if (text.includes('Nine Favorite Things')) {
            targetParagraph.parentNode.insertBefore(playerContainer, targetParagraph.nextSibling);
        } 
        // Insert BEFORE for all other cases
        else {
            targetParagraph.parentNode.insertBefore(playerContainer, targetParagraph);
        }

        return true;
    } catch (error) {
        console.error("Error in injectContentPlayer:", error);
        return false;
    }
}



        function createPlayerContainer() {
            const playerContainer = document.createElement('div');
            playerContainer.className = 'instaread-content-wrapper';
            playerContainer.style.cssText = 'clear: both; margin: 2rem 0;';
            playerContainer.innerHTML = `
                <instaread-player publication="<?= $config['publication'] ?>" class="instaread-player" style="height: <?= $config['content']['height'] ?>px">
                    <iframe id="instaread_iframe" name="instaread_playlist" width="100%" height="100%"
                            scrolling="no" frameborder="0" loading="lazy" title="Audio Article"
                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                            style="display: block;" data-pin-nopin="true">
                    </iframe>
                </instaread-player>
            `;
            return playerContainer;
        }

        function loadPlayer() {
            const script = document.createElement('script');
            script.src = `https://instaread.co/js/instaread.${publication}.js?v=${version}`;
            console.log("Injecting Script")
            script.onload = () => {
                document.querySelectorAll('.instaread-player').forEach(el => {
                    el.style.visibility = 'visible';
                });
            };
            document.body.appendChild(script);
        }

       

        // Execute injection logic
        if (injectHeaderPlayer()) {
            loadPlayer();
        } else if (injectContentPlayer()) {
          console.log("hello")
            loadPlayer();
        } else {
            console.log("No suitable target found for player injection");
        }
    });
    </script>
    <?php
}, 5);

// 4. Resource Optimization
add_filter('wp_resource_hints', function($urls, $relation_type) {
    if (( 'dns-prefetch' === $relation_type || 'preconnect' === $relation_type ) && is_singular()) {
        $urls[] = 'https://instaread.co';
    }
    return $urls;
}, 10, 2);

