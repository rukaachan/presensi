<?php

namespace App\Http\Controllers\Administration;

use App\Authorization\RoleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Account;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function showStudent(Request $request)
    {
        $students = Student::query()->with(['classroom.department', 'account'])
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword').'%';
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('name', 'like', $keyword)
                        ->orWhere('student_number', 'like', $keyword)
                        ->orWhereHas('classroom', function ($classroom) use ($keyword): void {
                            $classroom->where('name', 'like', $keyword)
                                ->orWhereHas('department', static fn ($department) => $department->where('name', 'like', $keyword));
                        });
                });
            })
            ->when($request->filled('classroom_id'), static fn ($query) => $query->where('classroom_id', (int) $request->input('classroom_id')))
            ->when($request->filled('status'), static fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('administration.students', [
            'students' => $students,
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function detailStudent(int $id)
    {
        $student = Student::query()->with(['classroom.department', 'account', 'classOfficers'])->findOrFail($id);

        return view('administration.student-detail', compact('student'));
    }

    public function createStudent()
    {
        return view('administration.create-student', [
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function storeStudent(StoreStudentRequest $request)
    {
        $data = $request->validated();
        $account = Account::query()->create([
            'role_id' => Role::query()->where('code', RoleCode::STUDENT->value)->firstOrFail()->getKey(),
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);
        $student = Student::query()->create([
            'account_id' => $account->getKey(),
            'classroom_id' => $data['classroom_id'],
            'student_number' => $data['student_number'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'gender' => $data['gender'],
            'admission_year' => $data['admission_year'],
            'status' => 'active',
            'position' => 'student',
            'photo_path' => $request->file('photo')?->store('profiles/students', 'public') ?: 'student.jpg',
            'created_by_label' => $this->actorLabel(),
        ]);
        $student->refresh();

        return redirect()->route('administration.students.index')->with('success', __('messages.created', ['item' => __('labels.student')]));
    }

    public function editStudent(int $id)
    {
        return view('administration.edit-student', [
            'student' => Student::query()->with('account')->findOrFail($id),
            'classrooms' => Classroom::query()->with('department')->orderBy('name')->get(),
        ]);
    }

    public function updateStudent(UpdateStudentRequest $request, int $id)
    {
        $student = Student::query()->with('account')->findOrFail($id);
        $data = $request->validated();
        $student->update([
            'classroom_id' => $data['classroom_id'],
            'student_number' => $data['student_number'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'gender' => $data['gender'],
            'admission_year' => $data['admission_year'],
            'photo_path' => $request->file('photo')?->store('profiles/students', 'public') ?: $student->photo_path,
        ]);
        $student->account?->update(['username' => $data['username']]);

        return redirect()->route('administration.students.index')->with('success', __('messages.updated', ['item' => __('labels.student')]));
    }

    public function destroyStudent(Request $request)
    {
        $student = Student::query()->findOrFail((int) $request->input('id'));
        if ($student->photo_path && $student->photo_path !== 'student.jpg') {
            Storage::disk('public')->delete($student->photo_path);
        }
        $student->delete();

        return redirect()->route('administration.students.index')->with('success', __('messages.deleted', ['item' => __('labels.student')]));
    }

    private function actorLabel(): string
    {
        $account = auth()->user();

        return $account instanceof \App\Models\Account
            ? (string) ($account->role?->name ?: 'System')
            : 'System';
    }
}
