<?php

namespace App\Service\Crypto\PNG;

class Decrypt
{

    public function __construct(
        private string $privateKeyPath,
        private string $aesKeyPath
    ) {}

    public function decrypt(string $cryptFilePath, string $outputFilePath): void
    {
        $privateKey = file_get_contents($this->privateKeyPath);
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if (!$privateKeyResource) {
            throw new \RuntimeException('Invalid private key.');
        }

        $encryptedKeyData = json_decode(file_get_contents($this->aesKeyPath), true);
        if ($encryptedKeyData === null) {
            throw new \RuntimeException('Failed to load encrypted key data.');
        }

        $encryptedPackage = base64_decode(file_get_contents($cryptFilePath));
        if ($encryptedPackage === false) {
            throw new \RuntimeException("Unable to read encrypted image file.");
        }

        $encryptedKey = hex2bin($encryptedKeyData['universal_key']);
        $iv = substr($encryptedPackage, 0, 16);
        $encryptedData = substr($encryptedPackage, 16);

        $decryptedImageData = openssl_decrypt($encryptedData, 'AES-256-CBC', $encryptedKey, OPENSSL_RAW_DATA, $iv);

        if ($decryptedImageData === false) {
            throw new \RuntimeException("Failed to decrypt image data.");
        }

        file_put_contents($outputFilePath, $decryptedImageData);
    }

}