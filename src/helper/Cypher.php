<?php
namespace indura\helpers;

/**
 * Class for encrypting and decrypting data using AES-256-CBC.
 *
 * This class provides methods to encrypt and decrypt data using the AES-256-CBC encryption algorithm.
 * The key and initialization vector (IV) must be provided in hexadecimal format.
 */
class Cypher {
    /**
     * @var string Encryption algorithm used.
     */
    private $cipher;

    /**
     * @var string Encryption key in binary format.
     */
    private $key;

    /**
     * @var string Initialization vector (IV) in binary format.
     */
    private $iv;

    /**
     * Class constructor.
     *
     * @param string $keyHex Encryption key in hexadecimal format.
     * @param string $ivHex Initialization vector in hexadecimal format.
     */
    public function __construct(string $keyHex, string $ivHex) {
        $this->cipher = "aes-256-cbc";
        $this->key = hex2bin(string: $keyHex);
        $this->iv = hex2bin(string: $ivHex);
    }

    /**
     * Decrypts encrypted text.
     *
     * @param string $encrypted Encrypted text in base64.
     * @return string|null Decrypted text or null if the operation fails.
     */
    public function decrypt(string $encrypted): ?string {
        $encrypted = base64_decode(string: $encrypted);
        $decrypted = openssl_decrypt(data: $encrypted, cipher_algo: $this->cipher, passphrase: $this->key, options: 0, iv: $this->iv);
        
        return $decrypted;
    }

    /**
     * Encrypts plain text.
     *
     * @param string $plaintext Plain text to encrypt.
     * @return string Encrypted text in base64.
     */
    public function encrypt(string $plaintext): string {
        $encrypted = openssl_encrypt(data: $plaintext, cipher_algo: $this->cipher, passphrase: $this->key, options: 0, iv: $this->iv);
        
        return base64_encode(string: $encrypted);
    }
}