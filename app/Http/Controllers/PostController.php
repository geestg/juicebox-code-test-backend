<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        return response()->json(
            Post::orderByDesc('id')
                ->paginate((int) ($data['per_page'] ?? 15))
                ->withQueryString()
        );
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(['data' => $post]);
    }

    public function store(Request $request): JsonResponse
    {
        $post = new Post($request->validate($this->rules('required')));
        $post->user()->associate($request->user());
        $post->save();

        return response()->json(['data' => $post], 201);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);
        $post->update($request->validate($this->rules('sometimes')));

        return response()->json(['data' => $post]);
    }

    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return response()->noContent();
    }

    private function rules(string $presence): array
    {
        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'body' => [$presence, 'required', 'string', 'max:10000'],
        ];
    }
}
