<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveServices
{
    protected $service;

    private $parentFolder = '1ig7L9_sQnMtlbEPe7kio79Cn-3nniB_Z';

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-drive.json'));
        $client->addScope(Drive::DRIVE);

        $this->service = new Drive($client);
    }

    public function createFolder($name)
    {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->parentFolder]
        ]);

        $folder = $this->service->files->create($fileMetadata, [
            'fields' => 'id'
        ]);

        return $folder->id;
    }

    public function uploadFile($file, $folderId)
    {
        $fileMetadata = new DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [$folderId]
        ]);

        $content = file_get_contents($file->getRealPath());

        $uploaded = $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        // set public
        $this->service->permissions->create(
            $uploaded->id,
            new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ])
        );

        return $uploaded->id;
    }

    public function getUrl($fileId)
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }
}
