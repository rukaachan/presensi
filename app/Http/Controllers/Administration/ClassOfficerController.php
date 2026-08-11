<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\ClassOfficer;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassOfficerController extends Controller
{
    public function showClassOfficers(Request $request)
    {
        $officers = ClassOfficer::query()->with('student.classroom.department')
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword').'%';
                $query->whereHas('student', static fn ($student) => $student->where('name', 'like', $keyword)
                    ->orWhereHas('classroom', static fn ($classroom) => $classroom->where('name', 'like', $keyword)));
            })
            ->when($request->filled('classroom_id'), static fn ($query) => $query->whereHas('student', static fn ($student) => $student->where('classroom_id', (int) request()->input('classroom_id'))))
            ->orderBy('id')->paginate(25)->withQueryString();

        return view('administration.class-officers', [
            'officers' => $officers,
            'classrooms' => Classroom::query()->orderBy('name')->get(),
        ]);
    }

    public function detailClassOfficer(int $id)
    {
        return view('administration.class-officer-detail', [
            'officer' => ClassOfficer::query()->with('student.classroom.department')->findOrFail($id),
        ]);
    }

    public function createClassOfficer(Request $request)
    {
        return view('administration.create-class-officer', [
            'students' => Student::query()->where('status', 'active')->where('position', 'student')
                ->when($request->filled('classroom_id'), static fn ($query) => $query->where('classroom_id', (int) $request->input('classroom_id')))
                ->orderBy('name')->get(),
            'classrooms' => Classroom::query()->orderBy('name')->get(),
        ]);
    }

    public function storeClassOfficer(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'position' => ['required', Rule::in(['class_president', 'vice_president', 'secretary', 'treasurer', 'class_officer'])],
        ]);
        ClassOfficer::query()->updateOrCreate(
            ['student_id' => $data['student_id']],
            ['position' => $data['position'], 'created_by_label' => $this->actorLabel()],
        );
        Student::query()->whereKey($data['student_id'])->update(['position' => $data['position']]);

        return redirect()->route('administration.class-officers.index')->with('success', __('messages.created', ['item' => __('labels.class_officer')]));
    }

    public function editClassOfficer(int $id)
    {
        return view('administration.edit-class-officer', [
            'officer' => ClassOfficer::query()->with('student')->findOrFail($id),
            'students' => Student::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function updateClassOfficer(Request $request, int $id)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'position' => ['required', Rule::in(['class_president', 'vice_president', 'secretary', 'treasurer', 'class_officer'])],
        ]);
        $officer = ClassOfficer::query()->findOrFail($id);
        $officer->update($data + ['created_by_label' => $this->actorLabel()]);
        Student::query()->whereKey($data['student_id'])->update(['position' => $data['position']]);

        return redirect()->route('administration.class-officers.index')->with('success', __('messages.updated', ['item' => __('labels.class_officer')]));
    }

    public function destroyClassOfficer(Request $request)
    {
        $officer = ClassOfficer::query()->with('student')->findOrFail((int) $request->input('id'));
        $officer->student?->update(['position' => 'student']);
        $officer->delete();

        return redirect()->route('administration.class-officers.index')->with('success', __('messages.deleted', ['item' => __('labels.class_officer')]));
    }

    private function actorLabel(): string
    {
        $account = auth()->user();

        return $account instanceof \App\Models\Account
            ? (string) ($account->role?->name ?: 'System')
            : 'System';
    }
}
