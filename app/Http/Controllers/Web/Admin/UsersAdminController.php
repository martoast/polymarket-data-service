<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::query()
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%"))
            ->withCount(['tokens'])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.users', compact('users', 'search'));
    }
}
