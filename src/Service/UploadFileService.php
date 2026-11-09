<?php

namespace App\Service;

use Mithridatem\Routing\Http\FileRequestInterface;

class UploadFileService
{
    /**
     *
     * @param string $uploadDirectory Répertoire de destination configuré pour les futurs uploads.
     */
    public function __construct(private string $uploadDirectory) {}

    /**
     * Point d'extension pour le dépôt de fichiers ; aucune sauvegarde n'est encore implémentée.
     * @param FileRequestInterface $fileRequestInterface Fichier reçu dans la requête HTTP.
     * @return void
     */
    public function uploadFile(FileRequestInterface $fileRequestInterface) {}
}
