<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function showAuditLogs(Request $request)
    {
        $audits = AuditEvent::query()
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('keyword').'%';
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('action', 'like', $keyword)
                        ->orWhere('actor_type', 'like', $keyword)
                        ->orWhere('subject_type', 'like', $keyword)
                        ->orWhere('occurred_at', 'like', $keyword);
                });
            })
            ->when($request->filled('action'), static fn ($query) => $query->where('action', $request->string('action')))
            ->latest('occurred_at')
            ->simplePaginate(25)
            ->withQueryString();

        return view('administration.audits', compact('audits'));
    }

    public function deleteAuditEvents()
    {
        abort(405, __('audit.append_only'));
    }
}
