<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $users = User::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->paginate((int) ($data['per_page'] ?? 15))
            ->withQueryString();

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->only('id', 'name')]);
    }
}
