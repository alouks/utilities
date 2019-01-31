<?php

/**
 * Encryption
 *
 * @description     For arbitrary encryption and decryption of data
 * @author          Aaron Louks
 *
 * USAGE:
 *
 *    // Instantiate the class with your choice of cipher suites:
 *    // aes-256-ctr, aes-128-ctr, aes-256-cbc, aes-128-cbc
 *    $e = new Encryption('aes-256-cbc');
 *
 *    // Define your plaintext payload and your password or passphrase
 *    // The output will be an array with the field 'output'. $test['output'] for this example.
 *    $test = $e->encrypt($text_to_encrypt, 'asdf123asdf');
 *
 *    // Pass the decrypt method the block you need to decrypt along with the password / passphrase used to initially encrypt.
 *    $test2 = $e->decrypt($block_to_decrypt, 'asdf123asdf');
 *
 *    NOTE: In order to decrypt a block, you must instantiate the Encryption class with the cipher suite originally
 *          used to encrypt the block prior to attempting to decrypt.
 *
 */

class Encryption {

    protected $cipher;
    protected $iv_length;
    protected $iv;

    /**
     * Encryption constructor.
     * @param $cipher - define which cipher suite will be used
     */
    public function __construct($cipher) {

        $this->cipher = $cipher;
        $this->iv_length = openssl_cipher_iv_length($this->cipher);
        $strong = false;
        $this->iv = openssl_random_pseudo_bytes($this->iv_length, $strong);

    }

    /**
     * @param $plaintext
     * @param $key
     * @return array
     */
    public function encrypt($plaintext, $key) {

        $key = substr(hash('sha256', $key), 0, 32);

        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $key, OPENSSL_RAW_DATA, $this->iv);

        $output = array( 'output' => base64_encode($this->iv . $ciphertext));

        return $output;

    }

    /**
     * @param $ciphertext - your encrypted block
     * @param $key - the key to decrypt the block
     * @param $iv - the stored initialization vector
     * @return string
     */
    public function decrypt($ciphertext, $key) {

        $key = substr(hash('sha256', $key), 0, 32);

        $ciphertext = base64_decode($ciphertext);

        $iv = substr($ciphertext, 0, $this->iv_length);

        $plaintext = openssl_decrypt(substr($ciphertext, $this->iv_length), $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

        return $plaintext;

    }

}