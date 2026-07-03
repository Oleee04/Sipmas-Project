<?php
if (session_status() === PHP_SESSION_NONE) {
    /*
    Catatan:
    Konfigurasi session ini sengaja dibuat belum sepenuhnya aman
    untuk kebutuhan pengujian Weak Session IDs level Medium.

    Session sudah digunakan, tetapi belum menerapkan konfigurasi cookie
    seperti HttpOnly, Secure, SameSite secara lengkap.
    */
    session_start();
}
?>