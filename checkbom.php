<?php
/* ================================================
   checkbom.php — FIȘIER DE TEST ELIMINAT
   Accesul public la acest fișier a fost blocat.
   ================================================ */
session_start();
if (!isset($_SESSION['authenticated']) || empty($_SESSION['is_admin'])) {
    http_response_code(404);
    exit;
}
echo "<p style='font-family:sans-serif;color:#5d4037;'>Fișierul de test a fost securizat. Conținutul original a fost eliminat.</p>";