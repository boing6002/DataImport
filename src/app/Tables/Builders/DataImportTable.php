<?php

namespace LaravelEnso\DataImport\app\Tables\Builders;

use LaravelEnso\VueDatatable\app\Classes\Table;
use LaravelEnso\DataImport\app\Models\DataImport;

class DataImportTable extends Table
{
    protected $templatePath = __DIR__.'/../Templates/dataImports.json';

    public function query()
    {
        return DataImport::select(\DB::raw('data_imports.id, data_imports.id as "dtRowId",
                data_imports.type, data_imports.name, data_imports.created_at,
                people.name as createdBy'))
            ->join('users', 'data_imports.created_by', '=', 'users.id')
            ->join('people', 'users.person_id', '=', 'people.id');
    }
}
