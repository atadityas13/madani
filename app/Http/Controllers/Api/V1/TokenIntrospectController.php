<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\TokenIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenIntrospectController extends Controller
{
    public function __invoke(Request $request, TokenIntrospector $introspector): JsonResponse
    {
        $expected = (string) config('services.madani_introspect.secret', '');
        if ($expected === '' || ! hash_equals($expected, (string) $request->header('X-Madani-Introspect-Secret', ''))) {
            return response()->json([
                'active' => false,
                'message' => 'Introspect tidak diotorisasi.',
            ], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        return response()->json($introspector->inspect($data['token']));
    }
}
