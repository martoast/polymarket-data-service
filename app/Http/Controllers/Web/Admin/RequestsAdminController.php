<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestsAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $logs = DB::table('api_request_logs')
            ->join('users', 'users.id', '=', 'api_request_logs.user_id')
            ->select('api_request_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->when($search, fn ($q) => $q->where('users.email', 'ilike', "%{$search}%")
                ->orWhere('api_request_logs.path', 'ilike', "%{$search}%"))
            ->orderByDesc('api_request_logs.created_at')
            ->paginate(100)
            ->withQueryString();

        $stats = DB::table('api_request_logs')
            ->selectRaw("
                count(*) as total,
                count(case when created_at >= now() - interval '1 hour' then 1 end) as last_hour,
                count(case when created_at >= now() - interval '24 hours' then 1 end) as last_day
            ")
            ->first();

        return view('admin.requests', compact('logs', 'stats', 'search'));
    }
}
