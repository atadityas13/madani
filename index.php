<?php

/**
 * Pintu masuk untuk document root cPanel yang mengarah ke akar proyek.
 * cPanel sering menimpa .htaccess; file PHP ini tidak ikut ditimpa.
 */
require __DIR__.'/public/index.php';
