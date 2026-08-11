<?php

namespace App\Http\Controllers\Administration;

use App\Authorization\RoleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Account;
use App\Models\Classroom;
use App\Models\CounselingTeacher;
use App\Models\DutyTeacher;
use App\Models\Role;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    public function showTeacher(Request $request)
    {
        $teachers = Teacher::query()->with(['account.role', 'dutyTeacher', 'counselingTeacher', 'homeroomClassrooms'])
            ->when($request->filled('keyword'), static fn ($query) => $query->where('name', 'like', '%'.$request->string('keyword').'%'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('administration.teachers', [
            'teachers' => $teachers,
            'dutyTeachers' => DutyTeacher::query()->with('teacher')->get(),
            'counselingTeachers' => CounselingTeacher::query()->with('teacher')->get(),
            'classrooms' => Classroom::query()->with('homeroomTeacher')->orderBy('name')->get(),
        ]);
    }

    public function detailTeacher(int $id)
    {
        $teacher = Teacher::query()->with(['account.role', 'dutyTeacher', 'counselingTeacher', 'homeroomClassrooms'])->findOrFail($id);

        return view('administration.teacher-detail', compact('teacher'));
    }

    public function createTeacher()
    {
        return view('administration.create-teacher');
    }

    public function storeTeacher(StoreTeacherRequest $request)
    {
        $data = $request->validated();
        $account = Account::query()->create([
            'role_id' => Role::query()->where('code', $data['role_code'])->valueOrFail('id'),
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);
        $photoPath = $request->file('photo')?->store('profiles/teachers', 'public');
        $teacher = Teacher::query()->create([
            'account_id' => $account->getKey(),
            'name' => $data['name'],
            'photo_path' => $photoPath ?: 'teacher.jpg',
            'created_by_label' => $this->actorLabel(),
        ]);

        $this->syncTeacherSpecialization($teacher, $data['role_code']);

        return redirect()->route('administration.teachers.index')->with('success', __('messages.created', ['item' => __('labels.teacher')]));
    }

    public function editTeacher(int $id)
    {
        return view('administration.edit-teacher', ['teacher' => Teacher::query()->with('account.role')->findOrFail($id)]);
    }

    public function updateTeacher(UpdateTeacherRequest $request, int $id)
    {
        $teacher = Teacher::query()->with('account')->findOrFail($id);
        $data = $request->validated();
        $teacher->update([
            'name' => $data['name'],
            'photo_path' => $request->file('photo')?->store('profiles/teachers', 'public') ?: $teacher->photo_path,
        ]);
        $teacher->account?->update(['username' => $data['username']]);

        return redirect()->route('administration.teachers.index')->with('success', __('messages.updated', ['item' => __('labels.teacher')]));
    }

    public function destroyTeacher(Request $request)
    {
        $teacher = Teacher::query()->findOrFail((int) $request->input('id'));
        if ($teacher->photo_path && $teacher->photo_path !== 'teacher.jpg') {
            Storage::disk('public')->delete($teacher->photo_path);
        }
        $teacher->delete();

        return redirect()->route('administration.teachers.index')->with('success', __('messages.deleted', ['item' => __('labels.teacher')]));
    }

    private function syncTeacherSpecialization(Teacher $teacher, string $roleCode): void
    {
        match ($roleCode) {
            RoleCode::DUTY_TEACHER->value => DutyTeacher::query()->updateOrCreate(['teacher_id' => $teacher->getKey()]),
            RoleCode::COUNSELING_TEACHER->value => CounselingTeacher::query()->updateOrCreate(['teacher_id' => $teacher->getKey()]),
            default => null,
        };
    }

    private function actorLabel(): string
    {
        $account = auth()->user();

        return $account instanceof \App\Models\Account
            ? (string) ($account->role?->name ?: 'System')
            : 'System';
    }
}
