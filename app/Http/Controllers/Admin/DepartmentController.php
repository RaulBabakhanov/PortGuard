<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $editId = $request->integer('edit') ?: null;

        return view('admin.departments.index', [
            'departments' => Department::query()->withCount(['users', 'assets'])->latest('id')->paginate(10),
            'editing' => $editId ? Department::query()->find($editId) : null,
        ]);
    }

    public function store(Request $request, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:departments,code'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dept = Department::query()->create([...$data, 'is_active' => true]);
        $logger->log('department.created', "Birim eklendi: {$dept->name}", $dept);

        return redirect()->route('admin.departments.index')->with('status', 'Birim eklendi.');
    }

    public function update(Request $request, Department $department, AdminActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('departments', 'code')->ignore($department->id)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $department->update($data);
        $logger->log('department.updated', "Birim güncellendi: {$department->name}", $department);

        return redirect()->route('admin.departments.index')->with('status', 'Birim güncellendi.');
    }

    public function destroy(Department $department, AdminActivityLogger $logger): RedirectResponse
    {
        $name = $department->name;
        $department->delete();
        $logger->log('department.deleted', "Birim silindi: {$name}");

        return back()->with('status', 'Birim silindi.');
    }
}
