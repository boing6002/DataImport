<?php

namespace LaravelEnso\DataImport\App\Http\Services;

use Illuminate\Http\Request;
use LaravelEnso\DataImport\app\Classes\Importers\Importer;
use LaravelEnso\DataImport\app\Enums\ImportTypes;
use LaravelEnso\DataImport\app\Models\DataImport;
use LaravelEnso\FileManager\Classes\FileManager;

class DataImportService
{
    private $request;
    private $fileManager;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->fileManager = new FileManager(
            config('laravel-enso.paths.imports'),
            config('laravel-enso.paths.temp')
        );

        $this->fileManager->setValidExtensions(['xls', 'xlsx']);
    }

    public function index()
    {
        $importTypes = json_encode((new ImportTypes())->getData());

        return view('laravel-enso/data-import::dataImport.index', compact('importTypes'));
    }

    public function getSummary(DataImport $dataImport)
    {
        return json_encode($dataImport->summary);
    }

    public function store(string $type) //fixme. We need a class to handle the upload / import process.
    {
        $importer = null;

        $this->fileManager->startUpload($this->request->allFiles());
        $uploadedFile = $this->fileManager->getUploadedFiles()->first();
        $importer = new Importer($type, $uploadedFile);
        $importer->run();

        if ($importer->fails() || $importer->getSummary()->successful === 0) {
            $this->fileManager->deleteTempFiles();

            return $importer->getSummary();
        }

        $dataImport = new DataImport($uploadedFile);
        $dataImport->type = $type;
        $dataImport->summary = $importer->getSummary();
        $dataImport->save();
        $this->fileManager->commitUpload();

        return $importer->getSummary();
    }

    public function download(DataImport $dataImport)
    {
        return $this->fileManager->download($dataImport->original_name, $dataImport->saved_name);
    }

    public function destroy(DataImport $dataImport)
    {
        \DB::transaction(function () use ($dataImport) {
            $dataImport->delete();
            $this->fileManager->delete($dataImport->saved_name);
        });

        return ['message' => __(config('labels.successfulOperation'))];
    }
}
