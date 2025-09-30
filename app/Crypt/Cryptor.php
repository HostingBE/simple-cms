<?php

/**
* @author Constan van Suchtelen van de Haere <constan.vansuchtelenvandehaere@hostingbe.com>
* @copyright 2024 - 2025 HostingBE
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
* files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
* is furnished to do so, subject to the following conditions:

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
* OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
*/

namespace App\Crypt;

use function bin2hex;
use function hex2bin;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;

/**
 * Cryptor class
 *
 * The encrypt/decrypt methods are largely taken from here:
 *
 * @link https://stackoverflow.com/a/46872528/2532203
 */
class Cryptor {
    /**
     * Holds the encryption algorithm to use
     */
    protected const ENCRYPTION_ALGORITHM = 'AES-256-CBC';

    /**
     * Holds the hash algorithm to use
     */
    protected const HASHING_ALGORITHM = 'sha256';

    /**
     * Holds the application encryption secret
     *
     * @var string
     */
    protected $secret;

    /**
     * Cryptor constructor
     *
     * @param string $secret application encryption secret
     */
    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Decrypts a string using the application secret.
     *
     * @param string $input hex representation of the cipher text
     *
     * @return string UTF-8 string containing the plain text input
     */
    public function decrypt(string $input): string  {
        // prevent decrypt failing when $input is not hex or has odd length
        // added via suggestion from @markusand, see comment:
        // https://gist.github.com/Radiergummi/b326219f55edb33759791a78a1c134c3#gistcomment-2803128
        if (strlen($input) % 2 || ! ctype_xdigit($input)) {
            return '';
        }

        // we'll need the binary cipher
        $binaryInput = hex2bin($input);
        $iv = substr($binaryInput, 0, 16);
        $hash = substr($binaryInput, 16, 32);
        $cipherText = substr($binaryInput, 48);
        $key = hash(self::HASHING_ALGORITHM, $this->secret, true);

        // if the HMAC hash doesn't match the hash string, something has gone wrong
        if (hash_hmac(self::HASHING_ALGORITHM, $cipherText, $key, true) !== $hash) {
            return '';
        }

        return openssl_decrypt(
            $cipherText,
            self::ENCRYPTION_ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Encrypts a string using the application secret. This returns a hex representation of the binary cipher text
     *
     * @param string $input plain text input to encrypt
     *
     * @return string hex representation of the binary cipher text
     * @throws \Exception
     */
    public function encrypt(string $input): string {
        $key = hash(self::HASHING_ALGORITHM, $this->secret, true);
        $iv = random_bytes(16);

        $cipherText = openssl_encrypt(
            $input,
            self::ENCRYPTION_ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $hash = hash_hmac(self::HASHING_ALGORITHM, $cipherText, $key, true);

        return bin2hex($iv . $hash . $cipherText);
    }
}