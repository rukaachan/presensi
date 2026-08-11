<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function showClassroom(Request $request)
    {
        $classrooms = Classroom::query()->with(['department', 'homeroomTeacher'])
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword').'%';
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('name', 'like', $keyword)
                        ->orWhere('grade_level', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhereHas('department', static fn ($department) => $department->where('name', 'like', $keyword));
                });
            })
            ->when($request->filled('department_id'), static fn ($query) => $query->where('department_id', (int) $request->input('department_id')))
            ->when($request->filled('status'), static fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('grade_level')->orderBy('name')->paginate(25)->withQueryString();

        return view('administration.classrooms', [
            'classrooms' => $classrooms,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function detailClassroom(int $id)
    {
        $classroom = Classroom::query()->with(['department', 'homeroomTeacher', 'students'])->findOrFail($id);

        return view('administration.classroom-detail', [
            'classroom' => $classroom,
            'students' => $classroom->students,
            'officers' => $classroom->students()->whereIn('position', ['class_president', 'vice_president', 'secretary', 'treasurer'])->get(),
        ]);
    }

    public function createClassroom()
    {
        return view('administration.create-classroom', [
            'departments' => Department::query()->orderBy('name')->get(),
            'teachers' => Teacher::query()->orderBy('name')->get(),
        ]);
    }

    public function storeClassroom(StoreClassroomRequest $request)
    {
        Classroom::query()->create($request->validated() + ['created_by_label' => $this->actorLabel()]);

        return redirect()->route('administration.classrooms.index')->with('success', __('messages.created', ['item' => __('labels.classroom')]));
    }

    public function editClassroom(int $id)
    {
        return view('administration.edit-classroom', [
            'classroom' => Classroom::query()->findOrFail($id),
            'departments' => Department::query()->orderBy('name')->get(),
            'teachers' => Teacher::query()->orderBy('name')->get(),
        ]);
    }

    public function updateClassroom(UpdateClassroomRequest $request, int $id)
    {
        Classroom::query()->findOrFail($id)->update($request->validated() + ['created_by_label' => $this->actorLabel()]);

        return redirect()->route('administration.classrooms.index')->with('success', __('messages.updated', ['item' => __('labels.classroom')]));
    }

    public function destroyClassroom(Request $request)
    {
        Classroom::query()->findOrFail((int) $request->input('id'))->delete();

        return redirect()->route('administration.classrooms.index')->with('success', __('messages.deleted', ['item' => __('labels.classroom')]));
    }

    private function actorLabel(): string
    {
        $account = auth()->user();

        return $account instanceof \App\Models\Account
            ? (string) ($account->role?->name ?: 'System')
            : 'System';
    }
}
