<?php

namespace App\Http\Controllers;

use App\Models\JournalAudit;
use Illuminate\Http\Request;

/**
 * Journal d'audit du service : consultation en lecture seule.
 * Chaque service n'expose que son propre journal (jamais fusionnés).
 */
class JournalAuditController extends Controller
{
    public function index(Request $request)
    {
        $journal = JournalAudit::query()
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'ilike', "%{$action}%"))
            ->orderByDesc('date')
            ->limit(min((int) $request->query('limit', 100), 200))
            ->get(['id', 'utilisateur_id', 'action', 'objet', 'detail', 'date']);

        return ['data' => $journal];
    }
}
