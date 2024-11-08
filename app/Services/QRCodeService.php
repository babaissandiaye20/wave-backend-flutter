<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    public function generateQRCode(array $data, string $directory = 'qrcodes'): array
    {
        // Convertir les données en JSON
        $json = json_encode($data);

        // Créer le QR code
        $qrCode = QrCode::create($json);
        $writer = new PngWriter();
        
        // Générer le QR codea
        $result = $writer->write($qrCode);
        
        // Obtenir l'image en base64
        $base64 = base64_encode($result->getString());
        
        // Créer un nom de fichier unique
        $fileName = uniqid('qrcode_') . '.png';
        
        // S'assurer que le dossier existe dans public storage
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Définir le chemin complet
        $fullPath = storage_path("app/public/{$directory}/{$fileName}");
        
        // Sauvegarder le QR code
        $result->saveToFile($fullPath);

        // Retourner à la fois le chemin et la version base64
        return [
            'path' => "{$directory}/{$fileName}",
            'base64' => $base64
        ];
    }
}