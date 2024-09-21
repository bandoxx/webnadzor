<?php

namespace App\Service\Crypto\PNG;

class Encrypt
{

    public function __construct(
        private string $publicKeyPath,
        private string $aesKeyPath,
    )
    {}

    public function encrypt(string $inputFilePath, string $outputFilePath): void
    {
        $publicKey = file_get_contents($this->publicKeyPath);
        $publicKeyResource = openssl_get_publickey($publicKey);
        if (!$publicKeyResource) {
            throw new \RuntimeException('Invalid public key.');
        }

        // 2. Load the encrypted AES key and IV from the JSON file
        $encryptedKeyData = json_decode(file_get_contents($this->aesKeyPath), true);
        if ($encryptedKeyData === null) {
            throw new \RuntimeException('Failed to load encrypted key data.');
        }

        $universalKey = hex2bin($encryptedKeyData['universal_key']);
        $iv           = openssl_random_pseudo_bytes(16);

        $imageData = file_get_contents($inputFilePath);
        if ($imageData === false) {
            throw new \RuntimeException('Unable to read image file.');
        }

        $encryptedData = openssl_encrypt($imageData, 'AES-256-CBC', $universalKey, OPENSSL_RAW_DATA, $iv);
        if ($encryptedData === false) {
            throw new \RuntimeException('Image encryption failed.');
        }

        $encryptedPackage = base64_encode($iv . $encryptedData);

        file_put_contents($outputFilePath, $encryptedPackage);
        if (!file_exists($outputFilePath)) {
            throw new \RuntimeException('Failed to write the encrypted image file.');
        }
    }
}