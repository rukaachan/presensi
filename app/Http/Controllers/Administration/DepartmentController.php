<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function showDepartment(Request $request)
    {
        $departments = Department::query()
            ->when($request->filled('keyword'), static fn ($query) => $query->where('name', 'like', '%'.$request->string('keyword').'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('administration.departments', compact('departments'));
    }

    public function createDepartment()
    {
        return view('administration.create-department');
    }

    public function storeDepartment(StoreDepartmentRequest $request)
    {
        Department::query()->create([
            'name' => $request->string('name')->toString(),
            'created_by_label' => $this->actorLabel(),
        ]);

        return redirect()->route('administration.departments.index')->with('success', __('messages.created', ['item' => __('labels.department')]));
    }

    public function editDepartment(int $id)
    {
        return view('administration.edit-department', ['department' => Department::query()->findOrFail($id)]);
    }

    public function updateDepartment(UpdateDepartmentRequest $request, int $id)
    {
        Department::query()->findOrFail($id)->update([
            'name' => $request->string('name')->toString(),
            'created_by_label' => $this->actorLabel(),
        ]);

        return redirect()->route('administration.departments.index')->with('success', __('messages.updated', ['item' => __('labels.department')]));
    }

    public function destroyDepartment(Request $request)
    {
        Department::query()->findOrFail((int) $request->input('id'))->delete();

        return redirect()->route('administration.departments.index')->with('success', __('messages.deleted', ['item' => __('labels.department')]));
    }

    private function actorLabel(): string
    {
        $account = auth()->user();

        return $account instanceof \App\Models\Account
            ? (string) ($account->role?->name ?: 'System')
            : 'System';
    }
}
