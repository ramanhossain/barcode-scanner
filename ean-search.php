<?php
// Laad WordPress en WooCommerce functionaliteiten
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Controleer of de gebruiker WordPress bereikt
if (!defined('ABSPATH')) {
    exit; // Stop direct als WordPress niet geladen is
}

// Controleer of de gebruiker is ingelogd
if (!is_user_logged_in()) {
    // Stuur niet-ingelogde gebruikers naar de inlogpagina
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Zoekfunctie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 8px;
            width: 300px;
            margin-right: 10px;
        }
        button {
            padding: 8px 15px;
            cursor: pointer;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .error {
            color: red;
        }
        /* Dim-effect en centrale "Loading..." tekst */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-overlay span {
            font-size: 24px;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>Zoek een product</h1>

<form method="POST" action="" id="searchForm">
    <label for="search">Voer zoekterm in:</label>
    <input type="text" id="search" name="search" placeholder="Naam, SKU, EAN, beschrijving..." required>
    <button type="submit">Zoek</button>
</form>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <span>Loading...</span>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_term = sanitize_text_field($_POST['search']);
    
    global $wpdb;

    // Zoek naar producten op basis van naam, beschrijving, SKU, of een aangepaste meta (zoals EAN)
    $query = $wpdb->prepare("
        SELECT DISTINCT p.ID
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product' 
          AND p.post_status = 'publish'
          AND (
              p.post_title LIKE %s
              OR p.post_content LIKE %s
              OR pm.meta_value LIKE %s
          )
    ", "%{$search_term}%", "%{$search_term}%", "%{$search_term}%");

    $product_ids = $wpdb->get_col($query);

    if (!empty($product_ids)) {
        echo "<div class='result'>";
        echo "<h3>Gevonden producten:</h3>";
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            $product_name = $product->get_name();
            $product_price = $product->get_price();
            $product_url = get_permalink($product_id);

            echo "<p>";
            echo "<strong>Product Naam:</strong> <a href='{$product_url}'>{$product_name}</a><br>";
            echo "<strong>Prijs:</strong> &euro;{$product_price}<br>";
            echo "</p>";
        }
        echo "</div>";
    } else {
        echo "<p class='error'>Geen producten gevonden met zoekterm: {$search_term}</p>";
    }
}
?>

<!-- JavaScript voor focus en loading -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchField = document.getElementById('search');
        const searchForm = document.getElementById('searchForm');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Zet de focus automatisch op het zoekveld
        if (searchField) {
            searchField.focus();
        }

        // Toon de loading overlay tijdens het zoeken
        searchForm.addEventListener('submit', function () {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex'; // Toon de overlay
            }
        });
    });
</script>

</body>
</html>
