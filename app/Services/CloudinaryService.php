<?php

namespace App\Services;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialisation de Cloudinary avec l'URL de configuration
        $this->cloudinary = new Cloudinary(config('cloudinary.cloud_url'));
    }

    /**
     * Upload d'une image vers Cloudinary
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string|null $folder Dossier de destination
     * @return string URL sécurisée de l'image
     * @throws \Exception en cas d'erreur d'upload
     */
    public function uploadImage($file, $folder = 'images')
    {
        try {
            $uploadedFile = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
            ]);

            return $uploadedFile['secure_url'];
        } catch (\Exception $e) {
            throw new \Exception('Échec de l\'upload de l\'image : ' . $e->getMessage());
        }
    }

    /**
     * Supprime une image de Cloudinary
     *
     * @param string $publicId ID public de l'image sur Cloudinary
     * @return bool Statut de la suppression
     * @throws \Exception en cas d'erreur de suppression
     */
    public function deleteImage($publicId)
    {
        try {
            $this->cloudinary->uploadApi()->destroy($publicId);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Échec de la suppression de l\'image : ' . $e->getMessage());
        }
    }
}
