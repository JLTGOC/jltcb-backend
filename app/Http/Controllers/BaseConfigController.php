<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        // return $grouped;

        $response = $grouped->map(function ($configs) {
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

        $request->validate([
            'label' => "required|string|unique:{$table},label|max:255",
            'type'  => ['required', Rule::in($this->allowedTypes())],
        ]);

        $config = $model::create([
            'label' => $request->label,
            'type'  => $request->type,
        ]);

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

        $request->validate([
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'label')->ignore($record),
            ],
        ]);

        $record->update(['label' => $request->label]);

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
