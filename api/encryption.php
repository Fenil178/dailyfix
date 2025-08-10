<?php

// --- SECURITY WARNING ---
// This key MUST be a securely generated, random 32-character string.
// Do not use this default key in a production environment.
// You can generate a new key using: openssl_random_pseudo_bytes(32)
define('ENCRYPTION_KEY', 'p4s8v/B?E(H+KbPeShVmYq3t6w9z$C&F');

// Define the encryption method
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Encrypts data using a secure, randomized Initialization Vector (IV).
 * The IV is prepended to the ciphertext, ensuring that identical plaintext
 * strings will produce different ciphertexts each time they are encrypted.
 *
 * @param string $data The plaintext data to encrypt.
 * @return string The base64 encoded string containing both the IV and the ciphertext.
 */
function encrypt_id($data)
{
    // Get the length of the IV for the specified cipher method
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    
    // Generate a cryptographically secure random IV
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    // Encrypt the data
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    
    // Prepend the IV to the encrypted data and encode it in base64
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data that was encrypted with the encrypt_id function.
 * It separates the IV from the ciphertext and uses it for decryption.
 *
 * @param string $encrypted The base64 encoded string (IV + ciphertext).
 * @return string|false The original plaintext data, or false on failure.
 */
function decrypt_id($encrypted)
{
    // Decode the base64 string
    $data = base64_decode($encrypted);
    if ($data === false) {
        return false;
    }

    // Get the IV length
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    
    // Extract the IV from the beginning of the data
    $iv = substr($data, 0, $iv_length);
    
    // Extract the ciphertext
    $ciphertext = substr($data, $iv_length);
    
    // Decrypt the ciphertext and return the original plaintext
    return openssl_decrypt($ciphertext, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
?>