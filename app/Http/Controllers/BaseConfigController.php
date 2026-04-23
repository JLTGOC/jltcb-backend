<?php

namespace App\Http\Controllers;

use App\Models\DetailsConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

abstract class BaseConfigController extends Controller
{
    abstract protected function model(): string;
    abstract protected function resource(): string;
    abstract protected function allowedTypes(): array;

    public function index()
    {
        $configs = $this->model()::all();

        $grouped = $configs->groupBy('type');

        $response = $grouped->map(function ($configs) {
            if ($this->model() === DetailsConfiguration::class) {
                $configs->load('dropdownOptions');
            } 

            return $this->resource()::collection(
                $configs->values()
            );
        });

        $message = $grouped ? 'Configurations fetched successfully' : 'No Configurations available';

        return $this->success($message, $response);
    }

    public function store(Request $request)
    {
        $model = $this->model();
        $table = (new $model)->getTable();

        $validated = $request->validate([
            'label' => "required|string|unique:{$table},label|max:255",
            'type'  => ['required', Rule::in($this->allowedTypes())],
        ]);

        $config = DB::transaction(function() use ($model, $validated) {
            return $model::create($validated);
        }); 

        return $this->success(
            $request->label . ' option added successfully',
            new ($this->resource())($config),
            201
        );
    }

    public function show($record)
    {
        return $this->success(
            'Configuration fetched successfully',
            new ($this->resource())($record)
        );
    }

    public function update(Request $request, $record)
    {
        $model = $this->model();
        $table = (new $model)->getTable();

        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'label')->ignore($record),
            ],
        ]);

        DB::transaction(function () use ($validated, $record) {
            $record->update($validated);
        });

        return $this->success(
            'Config updated successfully',
            new ($this->resource())($record)
        );
    }

    public function destroy($record)
    {
        $record->delete();

        return $this->success('Config deleted successfully');
    }
}
